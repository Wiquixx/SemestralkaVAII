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
            } else {
                // validate date format (Y-m-d) and ensure it's not in the future
                $d = \DateTime::createFromFormat('Y-m-d', $purchase_date);
                $isValidDate = $d && $d->format('Y-m-d') === $purchase_date;
                if (!$isValidDate) {
                    $errors[] = 'Purchase date is not a valid date.';
                } else {
                    $today = new \DateTime('today');
                    if ($d > $today) {
                        $errors[] = 'Purchase date cannot be in the future.';
                    }
                }
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

                    // Begin transaction to ensure both plant and image are saved together
                    $db->beginTransaction();

                    $stmt = $db->prepare('INSERT INTO plants (plant_uid, user_id, common_name, scientific_name, location, purchase_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$plant_uid, $user_id, $common_name, $scientific_name, $location, $purchase_date, $notes]);

                    // get newly inserted plant id
                    $plantId = (int)$db->lastInsertId();

                    // Handle uploaded image if present
                    $uploaded = $request->file('image');
                    $movedFilePath = null;
                    if ($uploaded && $uploaded->isOk()) {
                        // validate size (<=5MB) and mime type
                        $maxBytes = 5 * 1024 * 1024;
                        if ($uploaded->getSize() > $maxBytes) {
                            throw new \Exception('Uploaded image exceeds 5MB size limit.');
                        }
                        // determine mime type from tmp file
                        $tmpPath = $uploaded->getFileTempPath();
                        $finfoType = @mime_content_type($tmpPath);
                        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
                        if (!in_array($finfoType, $allowed, true)) {
                            throw new \Exception('Uploaded file is not a permitted image type (JPEG, PNG, GIF).');
                        }

                        // ensure upload dir exists
                        $projectRoot = dirname(__DIR__, 2);
                        $uploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
                        if (!is_dir($uploadDir)) {
                            @mkdir($uploadDir, 0755, true);
                        }

                        $origName = $uploaded->getName();
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        if ($ext === '') {
                            // try to map mime type to ext
                            $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
                            $ext = $map[$finfoType] ?? 'bin';
                        }

                        $newFileName = 'plant_' . $plantId . '_' . substr(uniqid(), -8) . '.' . $ext;
                        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

                        if (!$uploaded->store($targetPath)) {
                            throw new \Exception('Failed to move uploaded file.');
                        }

                        // store web-accessible path (use forward slashes)
                        $webPath = '/uploads/' . $newFileName;

                        // insert image record
                        $stmtImg = $db->prepare('INSERT INTO images (plant_id, file_path) VALUES (?, ?)');
                        $stmtImg->execute([$plantId, $webPath]);

                        // remember moved path for possible cleanup on failure
                        $movedFilePath = $targetPath;
                    }

                    $db->commit();

                    $success = true;

                    // Redirect back to admin index so the new plant is immediately visible
                    return $this->redirect($this->url('admin.index'));
                } catch (\Exception $e) {
                    // cleanup: if file moved, try to remove it
                    try { $db->rollBack(); } catch (\Exception $_) {}
                    if (!empty($movedFilePath) && is_file($movedFilePath)) {
                        @unlink($movedFilePath);
                    }
                    $errors[] = 'Error: ' . $e->getMessage();
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

        // Fetch first image for this plant (if any) to show in edit form
        $stmtImg = $db->prepare('SELECT file_path FROM images WHERE plant_id = ? LIMIT 1');
        $stmtImg->execute([$plantId]);
        $imgRow = $stmtImg->fetch();
        $imagePath = $imgRow['file_path'] ?? null;

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
            } else {
                // validate date format and ensure not in the future
                $d = \DateTime::createFromFormat('Y-m-d', $purchase_date);
                $isValidDate = $d && $d->format('Y-m-d') === $purchase_date;
                if (!$isValidDate) {
                    $errors[] = 'Purchase date is not a valid date.';
                } else {
                    $today = new \DateTime('today');
                    if ($d > $today) {
                        $errors[] = 'Purchase date cannot be in the future.';
                    }
                }
            }

            if (empty($errors)) {
                try {
                    // Begin transaction to update plant and optionally store image
                    $db->beginTransaction();

                    $stmt = $db->prepare('UPDATE plants SET common_name = ?, scientific_name = ?, location = ?, purchase_date = ?, notes = ? WHERE plant_id = ? AND user_id = ?');
                    $stmt->execute([$common_name, $scientific_name, $location, $purchase_date, $notes, $plantId, $this->user->getId()]);

                    // Handle uploaded image if present
                    $uploaded = $request->file('image');
                    $movedFilePath = null;
                    $oldImages = [];
                    if ($uploaded && $uploaded->isOk()) {
                        $maxBytes = 5 * 1024 * 1024;
                        if ($uploaded->getSize() > $maxBytes) {
                            throw new \Exception('Uploaded image exceeds 5MB size limit.');
                        }
                        $tmpPath = $uploaded->getFileTempPath();
                        $finfoType = @mime_content_type($tmpPath);
                        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
                        if (!in_array($finfoType, $allowed, true)) {
                            throw new \Exception('Uploaded file is not a permitted image type (JPEG, PNG, GIF).');
                        }

                        $projectRoot = dirname(__DIR__, 2);
                        $uploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
                        if (!is_dir($uploadDir)) {
                            @mkdir($uploadDir, 0755, true);
                        }

                        $origName = $uploaded->getName();
                        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                        if ($ext === '') {
                            $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
                            $ext = $map[$finfoType] ?? 'bin';
                        }

                        $newFileName = 'plant_' . $plantId . '_' . substr(uniqid(), -8) . '.' . $ext;
                        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

                        if (!$uploaded->store($targetPath)) {
                            throw new \Exception('Failed to move uploaded file.');
                        }

                        $webPath = '/uploads/' . $newFileName;
                        // Remove existing image records for this plant so we keep only the new one
                        $stmtOld = $db->prepare('SELECT file_path FROM images WHERE plant_id = ?');
                        $stmtOld->execute([$plantId]);
                        $oldImages = $stmtOld->fetchAll();
                        $stmtDel = $db->prepare('DELETE FROM images WHERE plant_id = ?');
                        $stmtDel->execute([$plantId]);

                        $stmtImg = $db->prepare('INSERT INTO images (plant_id, file_path) VALUES (?, ?)');
                        $stmtImg->execute([$plantId, $webPath]);

                        $movedFilePath = $targetPath;
                    }

                    $db->commit();

                    // Unlink old image files now that DB transaction committed
                    if (!empty($oldImages)) {
                        $projectRoot = dirname(__DIR__, 2);
                        foreach ($oldImages as $img) {
                            $file = trim((string)($img['file_path'] ?? ''));
                            if ($file === '') continue;
                            $filePath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
                            try { if (is_file($filePath)) { @unlink($filePath); } } catch (\Exception $_) {}
                        }
                    }

                    $success = true;
                    return $this->redirect($this->url('admin.index'));
                } catch (\Exception $e) {
                    try { $db->rollBack(); } catch (\Exception $_) {}
                    if (!empty($movedFilePath) && is_file($movedFilePath)) {
                        @unlink($movedFilePath);
                    }
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }

        return $this->html(['errors' => $errors, 'success' => $success, 'plant' => $plant, 'imagePath' => $imagePath], 'edit_plant');
    }
}
