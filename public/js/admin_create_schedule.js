(function(){
    'use strict';

    function run() {
        var root = document.getElementById('admin_create_schedule');
        if (!root) return;

        var minTomorrow = root.dataset.minTomorrow || '';
        var minToday = root.dataset.minToday || '';

        var btnSchedule = document.getElementById('btn_schedule');
        var btnPlan = document.getElementById('btn_plan');
        var btnEditList = document.getElementById('btn_edit_list');
        var btnDeleteList = document.getElementById('btn_delete_list');
        var formSchedule = document.getElementById('form_schedule');
        var formPlan = document.getElementById('form_plan');
        var titleOption = document.getElementById('title_option');
        var customWrapper = document.getElementById('custom_title_wrapper');
        var editListRoot = document.getElementById('reminder_edit_list');
        var deleteListRoot = document.getElementById('reminder_delete_list');
        var editItems = document.getElementById('edit_items');
        var deleteItems = document.getElementById('delete_items');
        var editEmpty = document.getElementById('edit_empty');
        var deleteEmpty = document.getElementById('delete_empty');
        var tc; // single declaration for title_custom element reference

        function showSchedule(){
            if (formSchedule) formSchedule.style.display = '';
            if (formPlan) formPlan.style.display = 'none';
            if (editListRoot) editListRoot.style.display = 'none';
            if (deleteListRoot) deleteListRoot.style.display = 'none';
            if (btnSchedule) btnSchedule.disabled = true;
            if (btnPlan) btnPlan.disabled = false;
        }
        function showPlan(){
            if (formSchedule) formSchedule.style.display = 'none';
            if (formPlan) formPlan.style.display = '';
            if (editListRoot) editListRoot.style.display = 'none';
            if (deleteListRoot) deleteListRoot.style.display = 'none';
            if (btnSchedule) btnSchedule.disabled = false;
            if (btnPlan) btnPlan.disabled = true;
        }

        function showEditList(){
            if (formSchedule) formSchedule.style.display = 'none';
            if (formPlan) formPlan.style.display = 'none';
            if (editListRoot) editListRoot.style.display = '';
            if (deleteListRoot) deleteListRoot.style.display = 'none';
            if (btnSchedule) btnSchedule.disabled = false;
            if (btnPlan) btnPlan.disabled = false;
            fetchRemindersAndRender('edit');
        }

        function showDeleteList(){
            if (formSchedule) formSchedule.style.display = 'none';
            if (formPlan) formPlan.style.display = 'none';
            if (editListRoot) editListRoot.style.display = 'none';
            if (deleteListRoot) deleteListRoot.style.display = '';
            if (btnSchedule) btnSchedule.disabled = false;
            if (btnPlan) btnPlan.disabled = false;
            fetchRemindersAndRender('delete');
        }

        function fetchRemindersAndRender(mode){
            if (editItems) editItems.innerHTML = '';
            if (deleteItems) deleteItems.innerHTML = '';
            if (editEmpty) editEmpty.style.display = 'none';
            if (deleteEmpty) deleteEmpty.style.display = 'none';

            // Use controller endpoint to list reminders (returns JSON)
            var url = window.location.pathname + '?c=admin&a=listReminders';
            fetch(url, {credentials: 'same-origin'})
                .then(function(resp){ return resp.json(); })
                .then(function(data){
                    if (!Array.isArray(data) || data.length === 0) {
                        if (editEmpty) editEmpty.style.display = mode === 'edit' ? '' : 'none';
                        if (deleteEmpty) deleteEmpty.style.display = mode === 'delete' ? '' : 'none';
                        return;
                    }

                    data.forEach(function(r){
                        var common = r.plant_name ? (r.plant_name + ' — ') : '';
                        var when = r.remind_date || '';

                        // Row wrapper
                        var row = document.createElement('div');
                        row.className = 'reminder-item';

                        // Left content (flexible)
                        var left = document.createElement('div');
                        left.className = 'reminder-left';
                        left.innerText = common + when + ' — ' + (r.title || '');

                        // Right content (actions)
                        var right = document.createElement('div');
                        right.className = 'reminder-right';

                        if (mode === 'edit'){
                            var a = document.createElement('a');
                            a.className = 'reminder-action';
                            a.href = window.location.pathname + '?c=admin&a=editReminder&id=' + encodeURIComponent(r.reminder_id);
                            a.setAttribute('aria-label', 'Edit reminder');
                            a.innerText = '✎';
                            right.appendChild(a);
                            if (editItems) editItems.appendChild(row);
                        } else {
                            var del = document.createElement('button');
                            del.className = 'reminder-action';
                            del.type = 'button';
                            del.setAttribute('aria-label', 'Delete reminder');
                            del.innerText = '🗑';
                            del.addEventListener('click', function(){
                                if (!confirm('Delete this schedule/plan?')) return;
                                var fd = new FormData(); fd.append('reminder_id', r.reminder_id);
                                fetch(window.location.pathname + '?c=admin&a=deleteReminder', {method: 'POST', body: fd, credentials: 'same-origin'})
                                    .then(function(res){ return res.json(); })
                                    .then(function(resp){
                                        if (resp && resp.success) {
                                            // remove element from UI
                                            if (row && row.parentNode) row.parentNode.removeChild(row);
                                        } else {
                                            alert('Unable to delete.');
                                        }
                                    }).catch(function(){ alert('Network error'); });
                            });
                            right.appendChild(del);
                            if (deleteItems) deleteItems.appendChild(row);
                        }

                        row.appendChild(left);
                        row.appendChild(right);
                    });
                }).catch(function(){
                    // ignore - silent fail
                });
        }

        if (btnSchedule) btnSchedule.addEventListener('click', showSchedule);
        if (btnPlan) btnPlan.addEventListener('click', showPlan);
        if (btnEditList) btnEditList.addEventListener('click', showEditList);
        if (btnDeleteList) btnDeleteList.addEventListener('click', showDeleteList);

        if (titleOption) titleOption.addEventListener('change', function(){
            if (this.value === 'custom') {
                if (customWrapper) customWrapper.style.display = '';
                tc = document.getElementById('title_custom');
                if (tc) tc.setAttribute('required','required');
            } else {
                if (customWrapper) customWrapper.style.display = 'none';
                tc = document.getElementById('title_custom');
                if (tc) tc.removeAttribute('required');
            }
        });

        // Set initial state: Schedule selected
        showSchedule();

        // Ensure min dates are set (for browsers that don't honor PHP min attr)
        var firstDate = document.getElementById('first_date');
        if (firstDate && minTomorrow) firstDate.setAttribute('min', minTomorrow);
        var planDate = document.getElementById('plan_date');
        if (planDate && minToday) planDate.setAttribute('min', minToday);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
