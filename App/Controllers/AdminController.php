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
        // Only logged in users can access admin actions generally
        if (!$this->user->isLoggedIn()) {
            return false;
        }

        // Restrict the users listing to admin users (status === 1)
        if ($action === 'users') {
            try {
                return $this->user->getStatus() === 1;
            } catch (\Throwable $_) {
                // In case identity doesn't expose status for some reason, deny access by default
                return false;
            }
        }

        return true;
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

        // Read and clear flash message (if any) so it can be displayed once in the view
        $flash = $this->app->getSession()->get('flash_message');
        if ($flash !== null) {
            $this->app->getSession()->remove('flash_message');
        }

        return $this->html(compact('plants', 'flash'));
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
     * currently logged-in user, deletes related images (both database records and physical files)
     * and reminders, and then deletes the plant itself. All deletions are performed
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

    /**
     * Show the Create Schedule page (placeholder) and handle form submissions for schedules and plans.
     *
     * Supports two modes: 'schedule' (recurring reminders) and 'plan' (single-event reminders).
     */
    public function createSchedule(Request $request): Response
    {
        $db = Connection::getInstance();
        $userId = $this->user->getId();

        // Fetch user's plants so the UI can offer a selection
        $stmt = $db->prepare('SELECT plant_id, common_name FROM plants WHERE user_id = ? ORDER BY common_name ASC');
        $stmt->execute([$userId]);
        $plants = $stmt->fetchAll();

        $errors = [];

        if ($request->isPost()) {
            $data = $request->post();
            $type = isset($data['type']) ? $data['type'] : 'schedule'; // 'schedule' or 'plan'

            if ($type === 'schedule') {
                // Validate plant selection
                $plantId = (int)($data['plant_id'] ?? 0);
                if ($plantId <= 0) {
                    $errors[] = 'Please select a plant.';
                } else {
                    $found = false;
                    foreach ($plants as $p) {
                        if ((int)$p['plant_id'] === $plantId) { $found = true; break; }
                    }
                    if (!$found) { $errors[] = 'Selected plant not found.'; }
                }

                // Frequency (days)
                $frequency = (int)($data['frequency'] ?? 0);
                if ($frequency <= 0) { $errors[] = 'Frequency must be a positive number of days.'; }

                // First date: must be valid and in the future
                $firstDate = trim((string)($data['first_date'] ?? ''));
                $d = \DateTime::createFromFormat('Y-m-d', $firstDate);
                $isValidDate = $d && $d->format('Y-m-d') === $firstDate;
                $today = new \DateTime('today');
                if (!$isValidDate) { $errors[] = 'First date is not a valid date (YYYY-MM-DD).'; }
                else if ($d <= $today) { $errors[] = 'First date must be in the future.'; }

                // Title handling
                $titleOption = trim((string)($data['title_option'] ?? ''));
                $allowed = ['watering', 'change_of_place', 'fertilize', 'soil_change', 'custom'];
                if (!in_array($titleOption, $allowed, true)) { $errors[] = 'Please select a valid title option.'; }

                if ($titleOption === 'custom') {
                    $title = trim((string)($data['title_custom'] ?? ''));
                    if ($title === '') { $errors[] = 'Custom title is required when "Custom" is selected.'; }
                    elseif (mb_strlen($title) > 50) { $errors[] = 'Custom title must be at most 50 characters.'; }
                } else {
                    $map = [
                        'watering' => 'watering',
                        'change_of_place' => 'change of place',
                        'fertilize' => 'fertilize',
                        'soil_change' => 'soil change'
                    ];
                    $title = $map[$titleOption] ?? $titleOption;
                }

                $notes = trim((string)($data['notes'] ?? ''));

                if (empty($errors)) {
                    $stmt = $db->prepare('INSERT INTO reminders (user_id, plant_id, remind_date, frequency_days, title, notes, active) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$userId, $plantId, $firstDate, $frequency, $title, $notes, 1]);
                    return $this->redirect($this->url('admin.index'));
                }
            } else {
                // Plan (single event). Date required, plant optional per UI; but DB requires plant_id (not null/fk)
                $planDate = trim((string)($data['plan_date'] ?? ''));
                $d = \DateTime::createFromFormat('Y-m-d', $planDate);
                $isValidDate = $d && $d->format('Y-m-d') === $planDate;
                $today = new \DateTime('today');
                if (!$isValidDate) { $errors[] = 'Plan date is not a valid date (YYYY-MM-DD).'; }
                else if ($d < $today) { $errors[] = 'Plan date cannot be in the past.'; }

                $plantId = (int)($data['plan_plant_id'] ?? 0);
                if ($plantId > 0) {
                    $found = false;
                    foreach ($plants as $p) { if ((int)$p['plant_id'] === $plantId) { $found = true; break; } }
                    if (!$found) { $errors[] = 'Selected plant not found.'; }
                } else {
                    // The database requires plant_id not null; if user didn't select a plant, try to use the first plant
                    if (!empty($plants)) {
                        $plantId = (int)$plants[0]['plant_id'];
                    } else {
                        $errors[] = 'No plant selected. Please add a plant first.';
                    }
                }

                $title = trim((string)($data['plan_title'] ?? ''));
                if ($title === '') { $errors[] = 'Title is required for a plan.'; }
                elseif (mb_strlen($title) > 50) { $errors[] = 'Title must be at most 50 characters.'; }

                $notes = trim((string)($data['plan_notes'] ?? ''));

                if (empty($errors)) {
                    // frequency_days = -1 for plans to indicate one-off
                    $stmt = $db->prepare('INSERT INTO reminders (user_id, plant_id, remind_date, frequency_days, title, notes, active) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$userId, $plantId, $planDate, -1, $title, $notes, 1]);
                    return $this->redirect($this->url('admin.index'));
                }
            }
        }

        return $this->html(['plants' => $plants, 'errors' => $errors], 'create_schedule');
    }

    // New JSON endpoint: returns reminder occurrences for a given month for the current user.
    public function reminders(Request $request): Response
    {
        // If user not logged in, return empty list
        if (!$this->user->isLoggedIn()) {
            return $this->json([]);
        }

        $year = (int)($request->value('year') ?? date('Y'));
        $month = (int)($request->value('month') ?? date('n'));
        if ($month < 1) $month = 1;
        if ($month > 12) $month = 12;

        try {
            $start = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
            $end = clone $start;
            $end->modify('last day of this month');

            $db = Connection::getInstance();
            // Fetch one-off reminders within the month, and recurring reminders that started on or before month end
            $sql = "SELECT r.reminder_id, r.user_id, r.plant_id, r.remind_date, r.frequency_days, r.title, r.notes, p.common_name AS plant_name
                    FROM reminders r
                    JOIN plants p ON p.plant_id = r.plant_id
                    WHERE r.user_id = ?
                      AND r.active = 1
                      AND (
                        (r.frequency_days = -1 AND r.remind_date BETWEEN ? AND ?)
                        OR (r.frequency_days > 0 AND r.remind_date <= ?)
                      )";

            $stmt = $db->prepare($sql);
            $stmt->execute([$this->user->getId(), $start->format('Y-m-d'), $end->format('Y-m-d'), $end->format('Y-m-d')]);
            $rows = $stmt->fetchAll();

            $occurrences = [];
            foreach ($rows as $r) {
                $freq = (int)($r['frequency_days'] ?? -1);
                $first = new \DateTime($r['remind_date']);
                if ($freq === -1) {
                    // one-off
                    $dStr = $first->format('Y-m-d');
                    $occurrences[] = [
                        'date' => $dStr,
                        'reminder_id' => (int)$r['reminder_id'],
                        'title' => $r['title'],
                        'notes' => $r['notes'],
                        'plant_id' => (int)$r['plant_id'],
                        'plant_name' => $r['plant_name'] ?? null,
                    ];
                } elseif ($freq > 0) {
                    // recurring: find occurrences within [start, end]
                    if ($first > $end) {
                        continue;
                    }

                    // advance to first occurrence >= start
                    $d = clone $first;
                    if ($d < $start) {
                        $diff = (int)(($start->getTimestamp() - $d->getTimestamp()) / 86400);
                        $steps = (int)floor($diff / $freq);
                        if ($steps > 0) {
                            $d->modify('+' . ($steps * $freq) . ' days');
                        }
                        while ($d < $start) {
                            $d->modify('+' . $freq . ' days');
                        }
                    }

                    while ($d <= $end) {
                        $occurrences[] = [
                            'date' => $d->format('Y-m-d'),
                            'reminder_id' => (int)$r['reminder_id'],
                            'title' => $r['title'],
                            'notes' => $r['notes'],
                            'plant_id' => (int)$r['plant_id'],
                            'plant_name' => $r['plant_name'] ?? null,
                        ];
                        $d->modify('+' . $freq . ' days');
                    }
                }
            }

            return $this->json(array_values($occurrences));
        } catch (\Exception $e) {
            // On error, return empty list (do not expose internals)
            return $this->json([]);
        }
    }

    /**
     * Displays the users page in the admin panel.
     *
     * This action requires authorization. It fetches all users along with counts of their plants and reminders,
     * and passes this data to the view for rendering.
     *
     * @return Response Returns a response object containing the rendered HTML.
     */
    public function users(Request $request): Response
    {
        $db = Connection::getInstance();
        $sql = "SELECT u.user_id, u.display_name, u.email, u.created_at,
            COALESCE(p.plant_count, 0) AS plant_count,
            COALESCE(r.reminder_count, 0) AS reminder_count
            FROM users u
            LEFT JOIN (
                SELECT user_id, COUNT(*) AS plant_count FROM plants GROUP BY user_id
            ) p ON p.user_id = u.user_id
            LEFT JOIN (
                SELECT user_id, COUNT(*) AS reminder_count FROM reminders GROUP BY user_id
            ) r ON r.user_id = u.user_id
            ORDER BY u.user_id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([]);
        $users = $stmt->fetchAll();

        return $this->html(compact('users'));
    }
}
