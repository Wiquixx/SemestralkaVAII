<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser $user */
/** @var array $plants */
?>

<div class="container-fluid">
    <div class="row">
        <div class="col text-center">
            <div>
                <!-- Welcome message removed as requested -->
            </div>
            <div class="admin-actions-grid mt-5">
                <a href="#" class="admin-action-btn">View Calendar</a>
                <a href="#" class="admin-action-btn">Create Schedule</a>
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

<!-- Hidden form for deleting a plant -->
<form id="delete_form" method="post" action="<?= $link->url('admin.deletePlant') ?>" style="display:none;">
    <input type="hidden" name="plant_id" id="delete_plant_id" value="">
</form>

<script>
(function() {
    const select = document.getElementById('sort_by');
    if (select) {
        // base URL for admin index
        const base = <?= json_encode($link->url('admin.index')) ?>;
        select.addEventListener('change', function() {
            const val = this.value || 'name_asc';
            const sep = base.indexOf('?') !== -1 ? '&' : '?';
            window.location = base + sep + 'sort=' + encodeURIComponent(val);
        });
    }

    // Toggle remove mode: highlight borders and swap button text
    const toggleBtn = document.getElementById('toggle_remove_btn');
    const toggleEditBtn = document.getElementById('toggle_edit_btn');
    if (!toggleBtn || !toggleEditBtn) return;
    let removeMode = false;
    let editMode = false;
    const plantCards = () => Array.from(document.querySelectorAll('.plant-card'));

    const enterRemoveMode = () => {
        removeMode = true;
        toggleBtn.textContent = 'Cancel';
        toggleEditBtn.classList.add('disabled');
        plantCards().forEach(c => c.classList.add('remove-active'));
    };

    const exitRemoveMode = () => {
        removeMode = false;
        toggleBtn.textContent = 'Remove Plant';
        toggleEditBtn.classList.remove('disabled');
        plantCards().forEach(c => c.classList.remove('remove-active'));
    };

    const enterEditMode = () => {
        editMode = true;
        toggleEditBtn.textContent = 'Cancel';
        toggleBtn.classList.add('disabled');
        plantCards().forEach(c => c.classList.add('edit-active'));
    };

    const exitEditMode = () => {
        editMode = false;
        toggleEditBtn.textContent = 'Edit Plant';
        toggleBtn.classList.remove('disabled');
        plantCards().forEach(c => c.classList.remove('edit-active'));
    };

    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (removeMode) exitRemoveMode(); else enterRemoveMode();
    });

    toggleEditBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (editMode) exitEditMode(); else enterEditMode();
    });

    // When in remove mode, clicking a plant asks for confirmation and deletes on Yes
    const deleteForm = document.getElementById('delete_form');
    const deleteInput = document.getElementById('delete_plant_id');
    document.addEventListener('click', function(e) {
        const card = e.target.closest('.plant-card');
        if (!card) return;
        if (removeMode) {
            e.preventDefault();
            const plantId = card.dataset.plantId;
            const plantName = card.dataset.plantName || 'this plant';
            const ok = confirm(`Do you really want to remove plant ${plantName}?`);
            if (!ok) {
                // behave like Cancel
                exitRemoveMode();
                return;
            }
            // submit the hidden form
            if (deleteInput && deleteForm) {
                deleteInput.value = plantId;
                deleteForm.submit();
            }
            return;
        }
        if (editMode) {
            e.preventDefault();
            const plantId = card.dataset.plantId;
            const baseEdit = <?= json_encode($link->url('admin.editPlant')) ?>;
            const sep = baseEdit.indexOf('?') !== -1 ? '&' : '?';
            // navigate to edit page with plant id
            window.location = baseEdit + sep + 'id=' + encodeURIComponent(plantId);
            return;
        }
    });
})();
</script>
