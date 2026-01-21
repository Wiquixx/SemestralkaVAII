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
        <a href="<?= htmlspecialchars($link->url('admin.createSchedule'), ENT_QUOTES) ?>" title="Back">&times;</a>
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
            <label style="font-weight:600;">Type</label>
            <select id="edit_type" name="type" class="form-select" style="max-width:220px;">
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
            <button type="submit" class="admin-action-btn">Save</button>
            <a href="<?= htmlspecialchars($link->url('admin.createSchedule'), ENT_QUOTES) ?>" class="admin-action-btn" style="margin-left:8px;">Cancel</a>
        </div>
    </form>

    <script>
    (function(){
        var sel = document.getElementById('edit_type');
        var freq = document.getElementById('freq_block');
        var freqInput = document.getElementById('edit_frequency');
        sel.addEventListener('change', function(){
            if (this.value === 'plan'){
                freq.style.display = 'none';
                // ensure frequency_days will be submitted as -1 by adding a hidden input
                if (!document.getElementById('hidden_freq_flag')){
                    var h = document.createElement('input');
                    h.type = 'hidden'; h.name = 'frequency_days'; h.id = 'hidden_freq_flag'; h.value = '-1';
                    freq.parentNode.insertBefore(h, freq.nextSibling);
                } else {
                    document.getElementById('hidden_freq_flag').value = '-1';
                }
                if (freqInput) freqInput.removeAttribute('required');
            } else {
                freq.style.display = '';
                var h = document.getElementById('hidden_freq_flag'); if (h) h.parentNode.removeChild(h);
                if (freqInput) freqInput.setAttribute('required','required');
            }
        });
    })();
    </script>

</div>

