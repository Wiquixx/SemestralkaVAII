<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser $user */
/** @var array $plants */
/** @var array $reminder */
/** @var array $errors */
?>

<div class="container" style="max-width:760px;margin-top:12px;">
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <h2 style="margin:0;">Edit Schedule / Plan</h2>
        <a href="<?= htmlspecialchars($link->url('admin.createSchedule'), ENT_QUOTES) ?>" class="btn btn-secondary btn-sm">&larr; Back to menu</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div style="color:#a00;border:1px solid #f2c2c2;background:#fff7f7;padding:10px;margin:12px 0;border-radius:6px;text-align:left;">
            <strong>There were some problems with your submission:</strong>
            <ul style="margin:8px 0;padding-left:20px;">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php $isPlan = ((int)($reminder['frequency_days'] ?? -1) === -1); ?>

    <form method="post" action="<?= htmlspecialchars($link->url('admin.editReminder'), ENT_QUOTES) ?>">
        <input type="hidden" name="reminder_id" value="<?= (int)($reminder['reminder_id'] ?? 0) ?>">

        <div style="margin-top:10px;display:flex;gap:8px;align-items:center;">
            <label id="edit_type_label" for="edit_type"><strong>Type</strong></label>
            <select id="edit_type" name="type" class="form-select" aria-labelledby="edit_type_label" style="max-width:220px;">
                <option value="schedule" <?= $isPlan ? '' : 'selected' ?>>Schedule (recurring)</option>
                <option value="plan" <?= $isPlan ? 'selected' : '' ?>>Plan (one-off)</option>
            </select>
        </div>

        <div style="margin-top:10px;">
            <label for="edit_plant_id"><strong>Plant</strong></label><br>
            <select name="plant_id" id="edit_plant_id" class="form-select" required>
                <?php if (!empty($plants)) foreach ($plants as $p): ?>
                    <option value="<?= (int)$p['plant_id'] ?>" <?= ((int)($reminder['plant_id'] ?? 0) === (int)$p['plant_id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['common_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="freq_block" style="margin-top:10px;<?= $isPlan ? 'display:none;' : '' ?>">
            <label for="edit_frequency"><strong>Frequency (days)</strong></label>
            <input type="number" name="frequency_days" id="edit_frequency" min="1" class="form-control" value="<?= $isPlan ? '' : (int)($reminder['frequency_days'] ?? '') ?>">
        </div>

        <div style="margin-top:10px;">
            <label for="edit_date"><strong>Date</strong></label>
            <input type="date" name="remind_date" id="edit_date" class="form-control" value="<?= htmlspecialchars($reminder['remind_date'] ?? '') ?>" required>
        </div>

        <div style="margin-top:10px;">
            <label for="edit_title"><strong>Title</strong></label>
            <input type="text" name="title" id="edit_title" maxlength="50" class="form-control" value="<?= htmlspecialchars($reminder['title'] ?? '') ?>" required>
        </div>

        <div style="margin-top:10px;">
            <label for="edit_notes"><strong>Notes</strong></label>
            <textarea name="notes" id="edit_notes" class="form-control" rows="4" maxlength="1000"><?= htmlspecialchars($reminder['notes'] ?? '') ?></textarea>
        </div>

        <div style="margin-top:14px;text-align:center;">
            <div class="d-grid gap-2" style="margin-top:0;">
                <button type="submit" class="btn btn-success" style="background:#21b573;border:none;">Save Changes</button>
                <a href="<?= htmlspecialchars($link->url('admin.createSchedule'), ENT_QUOTES) ?>" class="btn btn-danger" style="background:#dc3545;border:none;color:#fff;">Cancel</a>
            </div>
        </div>
    </form>

    <script src="<?= htmlspecialchars($link->asset('js/admin_edit_reminder.js'), ENT_QUOTES) ?>"></script>

</div>
