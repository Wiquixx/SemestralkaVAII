<?php

//Vytvorené s pomocou Github Copilot

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser $user */
/** @var array $plants */
/** @var array $errors */
?>

<?php $today = (new \DateTime('today'))->format('Y-m-d');
      $tomorrow = (new \DateTime('tomorrow'))->format('Y-m-d'); ?>

<div class="container" id="admin_create_schedule" data-logged-in="<?= $user->isLoggedIn() ? '1' : '0' ?>" data-min-today="<?= $today ?>" data-min-tomorrow="<?= $tomorrow ?>">
    <div class="row">
        <div class="col text-center">
            <div style="display:flex;justify-content:space-between;align-items:center;margin:12px 0;">
                <h2 style="margin:0;">Manage Schedules</h2>
                <!-- Back to menu button: match users view style -->
                <a href="<?= htmlspecialchars($link->url('admin.index'), ENT_QUOTES) ?>" class="btn btn-secondary btn-sm">&larr; Back to menu</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div style="color:#a00;border:1px solid #f2c2c2;background:#fff7f7;padding:10px;margin:12px 0;border-radius:6px;text-align:left;max-width:720px;margin-left:auto;margin-right:auto;">
                    <strong>There were some problems with your submission:</strong>
                    <ul style="margin:8px 0;padding-left:20px;">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="admin-action-row" style="margin-top:10px;">
                <button id="btn_schedule" class="admin-action-btn" type="button">Schedule</button>
                <button id="btn_plan" class="admin-action-btn" type="button">Plan</button>
                <button id="btn_edit_list" class="admin-action-btn" type="button">Edit</button>
                <button id="btn_delete_list" class="admin-action-btn" type="button">Delete</button>
            </div>

            <div style="margin-top:18px;max-width:720px;margin-left:auto;margin-right:auto;text-align:left;">
                <!-- Schedule form -->
                <form id="form_schedule" method="post" action="<?= htmlspecialchars($link->url('admin.createSchedule'), ENT_QUOTES) ?>">
                    <input type="hidden" name="type" value="schedule">

                    <div style="margin-bottom:10px;">
                        <label for="plant_id"><strong>Plant</strong></label><br>
                        <select name="plant_id" id="plant_id" class="form-select" required>
                            <option value="">-- select plant --</option>
                            <?php if (!empty($plants)) foreach ($plants as $p): ?>
                                <option value="<?= (int)$p['plant_id'] ?>"><?= htmlspecialchars($p['common_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
                        <div style="flex:1;min-width:180px;">
                            <label for="frequency"><strong>Frequency (days)</strong></label>
                            <input type="number" name="frequency" id="frequency" min="1" class="form-control" required>
                        </div>

                        <div style="flex:1;min-width:180px;">
                            <label for="first_date"><strong>First date</strong></label>
                            <input type="date" name="first_date" id="first_date" min="<?= $tomorrow ?>" class="form-control" required>
                        </div>
                    </div>

                    <div style="margin-bottom:10px;">
                        <label for="title_option"><strong>Title</strong></label>
                        <select name="title_option" id="title_option" class="form-select" required>
                            <option value="watering">watering</option>
                            <option value="change_of_place">change of place</option>
                            <option value="fertilize">fertilize</option>
                            <option value="soil_change">soil change</option>
                            <option value="custom">custom</option>
                        </select>
                    </div>

                    <div id="custom_title_wrapper" style="display:none;margin-bottom:10px;">
                        <label for="title_custom"><strong>Custom title (max 50 chars)</strong></label>
                        <input type="text" name="title_custom" id="title_custom" maxlength="50" class="form-control">
                    </div>

                    <div style="margin-bottom:14px;">
                        <label for="notes"><strong>Notes</strong></label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" maxlength="1000"></textarea>
                    </div>

                    <!-- Replace single create button with the same layout used in add/edit plant views -->
                    <div class="d-grid gap-2" style="margin-bottom:16px;">
                        <button type="submit" class="btn btn-success" style="background:#21b573;border:none;">Create Schedule</button>
                        <a href="<?= htmlspecialchars($link->url('admin.index'), ENT_QUOTES) ?>" class="btn btn-danger" style="background:#dc3545;border:none;color:#fff;">Cancel</a>
                    </div>
                </form>

                <!-- Plan form -->
                <form id="form_plan" method="post" action="<?= htmlspecialchars($link->url('admin.createSchedule'), ENT_QUOTES) ?>" style="display:none;">
                    <input type="hidden" name="type" value="plan">

                    <div style="margin-bottom:10px;">
                        <label for="plan_date"><strong>Date</strong></label>
                        <input type="date" name="plan_date" id="plan_date" min="<?= $today ?>" class="form-control" required>
                    </div>

                    <div style="margin-bottom:10px;">
                        <label for="plan_plant_id"><strong>Plant (optional)</strong></label><br>
                        <select name="plan_plant_id" id="plan_plant_id" class="form-select">
                            <option value="">-- no plant --</option>
                            <?php if (!empty($plants)) foreach ($plants as $p): ?>
                                <option value="<?= (int)$p['plant_id'] ?>"><?= htmlspecialchars($p['common_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom:10px;">
                        <label for="plan_title"><strong>Title (custom)</strong></label>
                        <input type="text" name="plan_title" id="plan_title" maxlength="50" class="form-control" required>
                    </div>

                    <div style="margin-bottom:14px;">
                        <label for="plan_notes"><strong>Notes</strong></label>
                        <textarea name="plan_notes" id="plan_notes" class="form-control" rows="3" maxlength="1000"></textarea>
                    </div>

                    <!-- Match add/edit plant button layout for plan form too -->
                    <div class="d-grid gap-2" style="margin-bottom:16px;">
                        <button type="submit" class="btn btn-success" style="background:#21b573;border:none;">Create Plan</button>
                        <a href="<?= htmlspecialchars($link->url('admin.index'), ENT_QUOTES) ?>" class="btn btn-danger" style="background:#dc3545;border:none;color:#fff;">Cancel</a>
                    </div>
                </form>

                <!-- Edit list (shows reminders with edit links) -->
                <div id="reminder_edit_list" style="display:none;margin-top:14px;">
                    <h3 style="margin-top:0;margin-bottom:8px;font-size:18px;">Edit schedules / plans</h3>
                    <div id="edit_items" style="display:flex;flex-direction:column;gap:8px;"></div>
                    <div id="edit_empty" style="color:#666;display:none;">No schedules or plans found.</div>
                </div>

                <!-- Delete list (shows reminders with delete buttons) -->
                <div id="reminder_delete_list" style="display:none;margin-top:14px;">
                    <h3 style="margin-top:0;margin-bottom:8px;font-size:18px;">Delete schedules / plans</h3>
                    <div id="delete_items" style="display:flex;flex-direction:column;gap:8px;"></div>
                    <div id="delete_empty" style="color:#666;display:none;">No schedules or plans found.</div>
                </div>

            </div>

        </div>
    </div>
</div>

<script src="<?= htmlspecialchars($link->asset('js/admin_create_schedule.js'), ENT_QUOTES) ?>"></script>
