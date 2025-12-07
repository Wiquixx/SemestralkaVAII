<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\DB\Connection;

/**
 * Class AdminController
 *
 * This controller manages admin-related actions within the application.It extends the base controller functionality
 * provided by BaseController.
 *
 * @package App\Controllers
 */
class AdminController extends BaseController
{
    /**
     * Authorizes actions in this controller.
     *
     * This method checks if the user is logged in, allowing or denying access to specific actions based
     * on the authentication state.
     *
     * @param string $action The name of the action to authorize.
     * @return bool Returns true if the user is logged in; false otherwise.
     */
    public function authorize(Request $request, string $action): bool
    {
        return $this->user->isLoggedIn();
    }

    /**
     * Displays the index page of the admin panel.
     *
     * This action requires authorization. It returns an HTML response for the admin dashboard or main page.
     * It now fetches plants belonging to the current user and passes them to the view as $plants.
     *
     * @return Response Returns a response object containing the rendered HTML.
     */
    public function index(Request $request): Response
    {
        $plants = [];
        if ($this->user->isLoggedIn()) {
            $userId = $this->user->getId();
            $db = Connection::getInstance();

            // Determine sorting from request (whitelisted). Default is name_asc
            $sort = (string)($request->value('sort') ?? 'name_asc');
            $orderBy = 'p.common_name ASC';
            switch ($sort) {
                case 'name_asc':
                    $orderBy = 'p.common_name ASC';
                    break;
                case 'name_desc':
                    $orderBy = 'p.common_name DESC';
                    break;
                case 'date_purchased':
                    $orderBy = 'p.purchase_date ASC, p.common_name ASC';
                    break;
                case 'scientific_name':
                    // Put rows with empty/NULL scientific_name last, then sort by scientific_name, then by common_name
                    $orderBy = "(CASE WHEN p.scientific_name IS NULL OR p.scientific_name = '' THEN 1 ELSE 0 END), p.scientific_name ASC, p.common_name ASC";
                    break;
                default:
                    $orderBy = 'p.common_name ASC';
            }

            // Select plant info and the first image file_path (if any) for each plant, ordered according to $orderBy
            $sql = "SELECT p.plant_id, p.common_name, (
                SELECT i.file_path FROM images i WHERE i.plant_id = p.plant_id LIMIT 1
            ) AS file_path
            FROM plants p
            WHERE p.user_id = ?
            ORDER BY " . $orderBy;

            $stmt = $db->prepare($sql);
            $stmt->execute([$userId]);
            $plants = $stmt->fetchAll();
        }

        return $this->html(compact('plants'));
    }

    /**
     * Zobrazí a spracuje formulár na pridanie rastliny
     */
    public function addPlant(Request $request): Response
    {
        $errors = [];
        $success = false;
        if ($request->isPost()) {
            $data = $request->post();
            $common_name = trim($data['common_name'] ?? '');
            $scientific_name = trim($data['scientific_name'] ?? '');
            $location = trim($data['location'] ?? '');
            $purchase_date = trim($data['purchase_date'] ?? '');
            $notes = trim($data['notes'] ?? '');
            if ($common_name === '') {
                $errors[] = 'Common name is required.';
            }
            if ($purchase_date === '') {
                $errors[] = 'Purchase date is required.';
            }
            if (!$errors) {
                try {
                    $user_id = $this->user->getId();
                    $db = Connection::getInstance();

                    // Ensure plant_uid column exists (MySQL 8+ supports IF NOT EXISTS)
                    try {
                        $db->prepare('ALTER TABLE plants ADD COLUMN IF NOT EXISTS plant_uid VARCHAR(100) UNIQUE')->execute();
                    } catch (\Exception $e) {
                        // If the server doesn't support IF NOT EXISTS, attempt a safe add ignoring errors
                        try {
                            $db->prepare('ALTER TABLE plants ADD COLUMN plant_uid VARCHAR(100) UNIQUE')->execute();
                        } catch (\Exception $ex) {
                            // ignore - column might already exist or DB doesn't allow altering; continue
                        }
                    }

                    // Generate unique plant uid: timestamp + user id + short uniq
                    $timestamp = (new \DateTime())->format('YmdHis');
                    $uniq = substr(uniqid(), -6);
                    $plant_uid = $timestamp . '_' . $user_id . '_' . $uniq;

                    $stmt = $db->prepare('INSERT INTO plants (plant_uid, user_id, common_name, scientific_name, location, purchase_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$plant_uid, $user_id, $common_name, $scientific_name, $location, $purchase_date, $notes]);

                    $success = true;

                    // Redirect back to admin index so the new plant is immediately visible
                    return $this->redirect($this->url('admin.index'));
                } catch (\Exception $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }
        return $this->html(['errors' => $errors, 'success' => $success], 'add_plant');
    }

    /**
     * Deletes a plant and its related data.
     *
     * This action deletes a plant specified by plant_id. It verifies that the plant belongs to the
     * currently logged-in user, deletes related images (both database records and physical files),
     * reminders, and care actions, and then deletes the plant itself. All deletions are performed
     * within a database transaction. After successful deletion, it redirects to the admin index.
     *
     * @param Request $request The request object containing the plant_id to delete.
     * @return Response A redirect response to the admin index.
     */
    public function deletePlant(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->redirect($this->url('admin.index'));
        }

        $plantId = (int)($request->value('plant_id') ?? 0);
        if ($plantId <= 0) {
            return $this->redirect($this->url('admin.index'));
        }

        $db = Connection::getInstance();
        try {
            // Verify ownership
            $stmt = $db->prepare('SELECT user_id FROM plants WHERE plant_id = ?');
            $stmt->execute([$plantId]);
            $row = $stmt->fetch();
            if (!$row) {
                return $this->redirect($this->url('admin.index'));
            }
            $ownerId = (int)$row['user_id'];
            if ($ownerId !== $this->user->getId()) {
                // Not the owner
                return $this->redirect($this->url('admin.index'));
            }

            // Begin transaction
            $db->beginTransaction();

            // Find image file paths to unlink
            $stmt = $db->prepare('SELECT file_path FROM images WHERE plant_id = ?');
            $stmt->execute([$plantId]);
            $images = $stmt->fetchAll();

            // Delete image records
            $stmt = $db->prepare('DELETE FROM images WHERE plant_id = ?');
            $stmt->execute([$plantId]);

            // Delete reminders related to this plant
            $stmt = $db->prepare('DELETE FROM reminders WHERE plant_id = ?');
            $stmt->execute([$plantId]);

            // Delete care_actions related to this plant
            $stmt = $db->prepare('DELETE FROM care_actions WHERE plant_id = ?');
            $stmt->execute([$plantId]);

            // Delete the plant
            $stmt = $db->prepare('DELETE FROM plants WHERE plant_id = ?');
            $stmt->execute([$plantId]);

            $db->commit();

            // Unlink files after commit (so DB changes are atomic even if unlink fails)
            $projectRoot = dirname(__DIR__, 2); // project root
            foreach ($images as $img) {
                $file = trim((string)($img['file_path'] ?? ''));
                if ($file === '') continue;
                $filePath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
                try {
                    if (is_file($filePath)) {
                        @unlink($filePath);
                    }
                } catch (\Exception $e) {
                    // ignore unlink errors
                }
            }

            return $this->redirect($this->url('admin.index'));
        } catch (\Exception $e) {
            try { $db->rollBack(); } catch (\Exception $_) {}
            // you might want to log the error; for now redirect back
            return $this->redirect($this->url('admin.index'));
        }
    }

    /**
     * Edit a plant (show form on GET, process on POST).
     */
    public function editPlant(Request $request): Response
    {
        $errors = [];
        $success = false;
        $db = Connection::getInstance();

        // plant id can be in GET (id) or POST
        $plantId = (int)($request->value('id') ?? $request->value('plant_id') ?? 0);
        if ($plantId <= 0) {
            return $this->redirect($this->url('admin.index'));
        }

        // Verify ownership and fetch current plant
        $stmt = $db->prepare('SELECT * FROM plants WHERE plant_id = ?');
        $stmt->execute([$plantId]);
        $plant = $stmt->fetch();
        if (!$plant) {
            return $this->redirect($this->url('admin.index'));
        }
        if ((int)$plant['user_id'] !== $this->user->getId()) {
            return $this->redirect($this->url('admin.index'));
        }

        if ($request->isPost()) {
            $data = $request->post();
            $common_name = trim($data['common_name'] ?? '');
            $scientific_name = trim($data['scientific_name'] ?? '');
            $location = trim($data['location'] ?? '');
            $purchase_date = trim($data['purchase_date'] ?? '');
            $notes = trim($data['notes'] ?? '');

            if ($common_name === '') {
                $errors[] = 'Common name is required.';
            }
            if ($purchase_date === '') {
                $errors[] = 'Purchase date is required.';
            }

            if (empty($errors)) {
                try {
                    $stmt = $db->prepare('UPDATE plants SET common_name = ?, scientific_name = ?, location = ?, purchase_date = ?, notes = ? WHERE plant_id = ? AND user_id = ?');
                    $stmt->execute([$common_name, $scientific_name, $location, $purchase_date, $notes, $plantId, $this->user->getId()]);
                    $success = true;
                    return $this->redirect($this->url('admin.index'));
                } catch (\Exception $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }

        return $this->html(['errors' => $errors, 'success' => $success, 'plant' => $plant], 'edit_plant');
    }
}
