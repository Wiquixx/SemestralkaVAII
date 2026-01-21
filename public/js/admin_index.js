//Vytvorené s pomocou Github Copilot
(function () {
    // JS for Admin index page - moved from inline PHP view to keep MVC separation.
    document.addEventListener('DOMContentLoaded', function () {
        const root = document.getElementById('admin_index');
        const base = root && root.dataset && root.dataset.adminIndex ? root.dataset.adminIndex : null;
        const baseEdit = root && root.dataset && root.dataset.adminEdit ? root.dataset.adminEdit : null;

        // Auto-dismiss flash alert after 3 seconds (moved from view inline script)
        (function(){
            var flash = document.getElementById('flash_alert');
            if (flash) {
                setTimeout(function(){
                    flash.style.transition = 'opacity 0.5s ease';
                    flash.style.opacity = '0';
                    setTimeout(function(){ if (flash && flash.parentNode) flash.parentNode.removeChild(flash); }, 500);
                }, 3000);
            }
        })();

        const select = document.getElementById('sort_by');
        if (select && base) {
            select.addEventListener('change', function () {
                const val = this.value || 'name_asc';
                const sep = base.indexOf('?') !== -1 ? '&' : '?';
                window.location = base + sep + 'sort=' + encodeURIComponent(val);
            });
        }

        // Toggle remove/edit modes
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

        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (removeMode) exitRemoveMode(); else enterRemoveMode();
        });

        toggleEditBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (editMode) exitEditMode(); else enterEditMode();
        });

        // When in remove/edit mode, clicking a plant triggers action
        const deleteForm = document.getElementById('delete_form');
        const deleteInput = document.getElementById('delete_plant_id');

        document.addEventListener('click', function (e) {
            const card = e.target.closest && e.target.closest('.plant-card');
            if (!card) return;

            if (removeMode) {
                e.preventDefault();
                const plantId = card.dataset.plantId;
                const plantName = card.dataset.plantName || 'this plant';
                const ok = window.confirm('Do you really want to remove plant ' + plantName + '?');
                if (!ok) {
                    exitRemoveMode();
                } else {
                    if (deleteInput && deleteForm) {
                        deleteInput.value = plantId;
                        deleteForm.submit();
                    }
                }
            } else if (editMode) {
                e.preventDefault();
                const plantId = card.dataset.plantId;
                if (!baseEdit) return;
                const sep = baseEdit.indexOf('?') !== -1 ? '&' : '?';
                window.location = baseEdit + sep + 'id=' + encodeURIComponent(plantId);
            }
        });
    });
})();
