<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use App\Models\Plant;
use App\Models\Image;
use App\Models\Reminder;
use App\Models\UserRepository;
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

            // Determine sorting from request (whitelisted). Default is name_asc
            $sort = (string)($request->value('sort') ?? 'name_asc');

            // Use model to fetch plants
            $plants = Plant::getForUser($userId, $sort);
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

                    // Ensure plant_uid column exists (attempt, ignore failures)
                    $db = Connection::getInstance();
                    try {
                        $db->prepare('ALTER TABLE plants ADD COLUMN IF NOT EXISTS plant_uid VARCHAR(100) UNIQUE')->execute();
                    } catch (\Exception $e) {
                        try {
                            $db->prepare('ALTER TABLE plants ADD COLUMN plant_uid VARCHAR(100) UNIQUE')->execute();
                        } catch (\Exception $ex) {
                            // ignore - column might already exist or DB doesn't allow altering; continue
                        }
                    }

                    // Use Plant model to create the plant (it will handle image storage)
                    $uploaded = $request->file('image');
                    $plantId = Plant::create($user_id, [
                        'common_name' => $common_name,
                        'scientific_name' => $scientific_name,
                        'location' => $location,
                        'purchase_date' => $purchase_date,
                        'notes' => $notes
                    ], $uploaded);

                    $success = true;

                    // Redirect back to admin index so the new plant is immediately visible
                    return $this->redirect($this->url('admin.index'));
                } catch (\Exception $e) {
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

        try {
            $images = Plant::delete($plantId, $this->user->getId());

            // Unlink files after commit
            if (!empty($images)) {
                Image::unlinkFiles($images);
            }

            return $this->redirect($this->url('admin.index'));
        } catch (\Exception $e) {
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

        // plant id can be in GET (id) or POST
        $plantId = (int)($request->value('id') ?? $request->value('plant_id') ?? 0);
        if ($plantId <= 0) {
            return $this->redirect($this->url('admin.index'));
        }

        // Verify ownership and fetch current plant
        $plant = Plant::getById($plantId);
        if (!$plant) {
            return $this->redirect($this->url('admin.index'));
        }
        if ((int)$plant['user_id'] !== $this->user->getId()) {
            return $this->redirect($this->url('admin.index'));
        }

        // Fetch first image for this plant (if any) to show in edit form
        $images = Image::getImagesByPlant($plantId);
        $imagePath = $images[0]['file_path'] ?? null;

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
                    $uploaded = $request->file('image');

                    $oldImages = Plant::update($plantId, $this->user->getId(), [
                        'common_name' => $common_name,
                        'scientific_name' => $scientific_name,
                        'location' => $location,
                        'purchase_date' => $purchase_date,
                        'notes' => $notes
                    ], $uploaded);

                    // Unlink old image files now that DB transaction committed
                    if (!empty($oldImages)) {
                        Image::unlinkFiles($oldImages);
                    }

                    $success = true;
                    return $this->redirect($this->url('admin.index'));
                } catch (\Exception $e) {
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
        $userId = $this->user->getId();

        // Fetch user's plants so the UI can offer a selection
        $plants = Plant::getForUser($userId);

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
                    Reminder::insert([
                        'user_id' => $userId,
                        'plant_id' => $plantId,
                        'remind_date' => $firstDate,
                        'frequency_days' => $frequency,
                        'title' => $title,
                        'notes' => $notes,
                        'active' => 1
                    ]);
                    return $this->redirect($this->url('admin.index'));
                }
            } else {
                // Plan (single event).
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
                    Reminder::insert([
                        'user_id' => $userId,
                        'plant_id' => $plantId,
                        'remind_date' => $planDate,
                        'frequency_days' => -1,
                        'title' => $title,
                        'notes' => $notes,
                        'active' => 1
                    ]);
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
            $occurrences = Reminder::occurrencesForMonth($this->user->getId(), $year, $month);
            return $this->json($occurrences);
        } catch (\Exception $e) {
            return $this->json([]);
        }
    }

    /**
     * Return a JSON list of reminders (raw rows) for the current user.
     */
    public function listReminders(Request $request): Response
    {
        if (!$this->user->isLoggedIn()) {
            return $this->json([]);
        }
        $rows = Reminder::listForUser($this->user->getId());
        return $this->json($rows);
    }

    /**
     * Edit a single reminder (GET shows form, POST updates).
     */
    public function editReminder(Request $request): Response
    {
        $reminderId = (int)($request->value('id') ?? $request->value('reminder_id') ?? 0);
        if ($reminderId <= 0) {
            return $this->redirect($this->url('admin.createSchedule'));
        }

        $rem = Reminder::getById($reminderId);
        if (!$rem) {
            return $this->redirect($this->url('admin.createSchedule'));
        }
        if ((int)$rem['user_id'] !== $this->user->getId()) {
            return $this->redirect($this->url('admin.createSchedule'));
        }

        $plants = Plant::getForUser($this->user->getId());

        $errors = [];
        if ($request->isPost()) {
            $data = $request->post();
            $isPlan = (isset($data['type']) && $data['type'] === 'plan') || (isset($data['frequency_days']) && (int)$data['frequency_days'] === -1);

            $remind_date = trim((string)($data['remind_date'] ?? ''));
            $d = \DateTime::createFromFormat('Y-m-d', $remind_date);
            $isValidDate = $d && $d->format('Y-m-d') === $remind_date;
            $today = new \DateTime('today');
            if (!$isValidDate) { $errors[] = 'Date is not a valid date (YYYY-MM-DD).'; }
            else if ($isPlan) {
                if ($d < $today) { $errors[] = 'Plan date cannot be in the past.'; }
            } else {
                if ($d <= $today) { $errors[] = 'First date must be in the future.'; }
            }

            $plantId = (int)($data['plant_id'] ?? 0);
            if ($plantId > 0) {
                $found = false;
                foreach ($plants as $p) { if ((int)$p['plant_id'] === $plantId) { $found = true; break; } }
                if (!$found) { $errors[] = 'Selected plant not found.'; }
            } else {
                if (!empty($plants)) {
                    $plantId = (int)$plants[0]['plant_id'];
                } else {
                    $errors[] = 'No plant selected. Please add a plant first.';
                }
            }

            $title = trim((string)($data['title'] ?? ''));
            if ($title === '') { $errors[] = 'Title is required.'; }
            elseif (mb_strlen($title) > 50) { $errors[] = 'Title must be at most 50 characters.'; }

            $notes = trim((string)($data['notes'] ?? ''));

            $frequencyDays = -1;
            if (!$isPlan) {
                $frequencyDays = (int)($data['frequency_days'] ?? 0);
                if ($frequencyDays <= 0) { $errors[] = 'Frequency must be a positive number of days.'; }
            }

            if (empty($errors)) {
                Reminder::update($reminderId, $this->user->getId(), [
                    'plant_id' => $plantId,
                    'remind_date' => $remind_date,
                    'frequency_days' => $frequencyDays,
                    'title' => $title,
                    'notes' => $notes
                ]);
                return $this->redirect($this->url('admin.createSchedule'));
            }

            // repopulate $rem for form
            $rem['plant_id'] = $plantId;
            $rem['remind_date'] = $remind_date;
            $rem['frequency_days'] = $frequencyDays;
            $rem['title'] = $title;
            $rem['notes'] = $notes;
        }

        return $this->html(['reminder' => $rem, 'plants' => $plants, 'errors' => $errors], 'edit_reminder');
    }

    /**
     * Delete a reminder via POST; returns JSON success status.
     */
    public function deleteReminder(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->json(['success' => false]);
        }
        $reminderId = (int)($request->value('reminder_id') ?? 0);
        if ($reminderId <= 0) {
            return $this->json(['success' => false]);
        }

        try {
            $rem = Reminder::getById($reminderId);
            if (!$rem) return $this->json(['success' => false]);
            if ((int)$rem['user_id'] !== $this->user->getId()) return $this->json(['success' => false]);

            Reminder::delete($reminderId);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['success' => false]);
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
        $users = UserRepository::listAllWithCounts();
        return $this->html(compact('users'));
    }
}
