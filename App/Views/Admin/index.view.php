<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser $user */
/** @var array $plants */
?>

<div class="container-fluid" id="admin_index" data-admin-index="<?= htmlspecialchars($link->url('admin.index'), ENT_QUOTES) ?>" data-admin-edit="<?= htmlspecialchars($link->url('admin.editPlant'), ENT_QUOTES) ?>" data-logged-in="<?= $user->isLoggedIn() ? '1' : '0' ?>">
    <div class="row">
        <div class="col text-center">
            <div>
                <!-- Welcome message removed as requested -->
            </div>
            <div class="admin-actions-grid mt-5">
                <?php if ($user->isLoggedIn()): ?>
                    <a href="#" id="view_calendar_btn" class="admin-action-btn">View Calendar</a>
                <?php else: ?>
                    <a href="#" id="view_calendar_btn" class="admin-action-btn" aria-disabled="true" tabindex="-1" style="opacity:0.6;pointer-events:none;">View Calendar</a>
                <?php endif; ?>
                <a href="<?= $link->url('admin.createSchedule') ?>" class="admin-action-btn">Create Schedule</a>
                <a href="<?= $link->url('admin.addPlant') ?>" class="admin-action-btn">Add Plant</a>
                <a href="#" id="toggle_edit_btn" class="admin-action-btn">Edit Plant</a>
                <a href="#" id="toggle_remove_btn" class="admin-action-btn">Remove Plant</a>
            </div>

            <!-- Sort row: dropdown without blank option; default selection is Name A-Z -->
            <div class="sort-row mt-3">
                <div class="admin-action-btn sort-single">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:8px;">
                        <span style="font-weight:700;">Sort by</span>
                        <?php $currentSort = $_GET['sort'] ?? 'name_asc'; ?>
                        <select id="sort_by" class="form-select" aria-label="Sort plants">
                            <option value="name_asc" <?= $currentSort === 'name_asc' ? 'selected' : '' ?>>Name A - Z
                            </option>
                            <option value="name_desc" <?= $currentSort === 'name_desc' ? 'selected' : '' ?>>Name Z - A
                            </option>
                            <option value="date_purchased" <?= $currentSort === 'date_purchased' ? 'selected' : '' ?>>
                                Date Purchased
                            </option>
                            <option value="scientific_name" <?= $currentSort === 'scientific_name' ? 'selected' : '' ?>>
                                Scientific Name
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Plants grid (left-to-right) -->
            <div class="plants-section mt-4">
                <?php if (empty($plants)): ?>
                    <div class="text-muted">No plants yet.</div>
                <?php else: ?>
                    <div class="plants-grid">
                        <?php foreach ($plants as $p): ?>
                            <div class="plant-card" data-plant-id="<?= (int)$p['plant_id'] ?>" data-plant-name="<?= htmlspecialchars($p['common_name'] ?? '') ?>">
                                <div class="plant-name"><?= htmlspecialchars($p['common_name'] ?? '') ?></div>
                                <?php $file = trim((string)($p['file_path'] ?? '')); ?>
                                <?php if ($file): ?>
                                    <?php $url = '/' . ltrim($file, '/'); ?>
                                    <div class="plant-media">
                                        <img src="<?= htmlspecialchars($url) ?>" alt="<?= htmlspecialchars($p['common_name'] ?? 'plant') ?>">
                                    </div>
                                <?php else: ?>
                                    <div class="plant-media plant-media--empty"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Calendar modal (hidden by default) -->
<div id="calendar_overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;">
    <div id="calendar_modal" role="dialog" aria-modal="true" style="background:#fff;width:520px;max-width:520px;min-width:320px;height:420px;box-sizing:border-box;border-radius:8px;padding:12px;box-shadow:0 10px 30px rgba(0,0,0,0.3);position:relative;overflow:hidden;">
        <!-- Close button kept at top-right with high z-index and reserved space in header -->
        <button id="calendar_close_btn" aria-label="Close calendar" style="position:absolute;right:8px;top:8px;border:0;background:transparent;font-size:20px;cursor:pointer;z-index:60;padding:4px 8px;">&times;</button>

        <!-- Header: relative container so prev/next can be absolutely positioned and won't overlap close -->
        <div style="position:relative;display:block;height:48px;margin-bottom:8px;">
            <button id="calendar_prev" aria-label="Previous month" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);background:#eee;border:0;padding:6px 8px;border-radius:4px;cursor:pointer;z-index:20;">&lt;</button>
            <div id="calendar_title" style="font-weight:700;text-align:center;line-height:48px;">&nbsp;</div>
            <button id="calendar_next" aria-label="Next month" style="position:absolute;right:48px;top:50%;transform:translateY(-50%);background:#eee;border:0;padding:6px 8px;border-radius:4px;cursor:pointer;z-index:20;">&gt;</button>
        </div>

        <!-- Calendar root: fixed area within modal; scrollable if content exceeds space -->
        <div id="calendar_root" style="height:calc(100% - 110px);padding:6px 8px;overflow:auto;box-sizing:border-box;">
            <!-- Calendar grid will be rendered here by JS -->
        </div>
        <div style="height:42px;line-height:42px;margin-top:8px;text-align:center;color:#666;font-size:12px;border-top:1px solid #f0f0f0;">You can navigate up to 6 months into the past and 6 months into the future.</div>
    </div>
</div>

<!-- Hidden form for deleting a plant -->
<form id="delete_form" method="post" action="<?= $link->url('admin.deletePlant') ?>" style="display:none;">
    <input type="hidden" name="plant_id" id="delete_plant_id" value="">
</form>

<script src="<?= htmlspecialchars($link->asset('js/admin_index.js'), ENT_QUOTES) ?>"></script>
<script src="<?= htmlspecialchars($link->asset('js/admin_calendar.js'), ENT_QUOTES) ?>"></script>
