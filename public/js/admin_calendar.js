//Vytvorené s pomocou Github Copilot
(function () {
    // admin_calendar.js
    // Renders a simple month calendar in the existing modal, enforces ±6 months from today.

    const container = document.getElementById('admin_index');
    const overlay = document.getElementById('calendar_overlay');
    const modal = document.getElementById('calendar_modal');
    const openBtn = document.getElementById('view_calendar_btn');
    const closeBtn = document.getElementById('calendar_close_btn');
    const prevBtn = document.getElementById('calendar_prev');
    const nextBtn = document.getElementById('calendar_next');
    const titleEl = document.getElementById('calendar_title');
    const root = document.getElementById('calendar_root');

    if (!overlay || !modal || !openBtn || !closeBtn || !prevBtn || !nextBtn || !titleEl || !root || !container) {
        // Required elements not present; nothing to do.
        return;
    }

    const loggedIn = container.getAttribute('data-logged-in') === '1';

    // Dates
    const today = new Date();
    let monthsFromToday = 0; // 0 = current month, negative = past, positive = future
    const LIMIT = 6; // months limit both directions

    // Utilities
    function getDisplayedDate() {
        return new Date(today.getFullYear(), today.getMonth() + monthsFromToday, 1);
    }

    function monthName(monthIndex) {
        return [
            'January','February','March','April','May','June','July','August','September','October','November','December'
        ][monthIndex];
    }

    function render() {
        const d = getDisplayedDate();
        const year = d.getFullYear();
        const month = d.getMonth();

        // Title
        titleEl.textContent = `${monthName(month)} ${year}`;

        // Prev/Next disabled state
        prevBtn.disabled = monthsFromToday <= -LIMIT;
        nextBtn.disabled = monthsFromToday >= LIMIT;
        prevBtn.style.opacity = prevBtn.disabled ? '0.4' : '1';
        nextBtn.style.opacity = nextBtn.disabled ? '0.4' : '1';

        // Build calendar grid (Sunday - Saturday)
        const firstDay = new Date(year, month, 1).getDay(); // 0 (Sun) - 6 (Sat)
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Clear root
        root.innerHTML = '';

        const table = document.createElement('table');
        table.setAttribute('role','grid');
        table.style.width = '100%';
        table.style.borderCollapse = 'collapse';
        table.style.textAlign = 'center';
        // Use fixed layout so cell widths/heights stay constant across months
        table.style.tableLayout = 'fixed';
        table.style.height = '100%';

        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        const weekdayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        weekdayNames.forEach(w => {
            const th = document.createElement('th');
            th.textContent = w;
            th.style.padding = '6px 4px';
            th.style.fontSize = '13px';
            th.style.color = '#444';
            // fixed width per column to avoid reflow
            th.style.width = (100 / 7) + '%';
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);

        const tbody = document.createElement('tbody');

        // Ensure consistent row height by calculating cell height
        const cellHeight = '44px';

        let row = document.createElement('tr');
        // Fill empty cells before first day
        for (let i = 0; i < firstDay; i++) {
            const td = document.createElement('td');
            td.style.padding = '8px 4px';
            td.style.height = cellHeight;
            td.style.verticalAlign = 'middle';
            row.appendChild(td);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const weekday = (firstDay + day - 1) % 7;
            const td = document.createElement('td');
            td.textContent = String(day);
            td.style.padding = '8px 4px';
            td.style.cursor = 'default';
            td.style.height = cellHeight;
            td.style.verticalAlign = 'middle';
            td.style.overflow = 'hidden';

            // mark cell with data attributes so we can find it later
            td.dataset.day = String(day);
            // store full date in YYYY-MM-DD for convenience
            const mm = String(month + 1).padStart(2, '0');
            td.dataset.date = `${year}-${mm}-${String(day).padStart(2,'0')}`;

            // highlight today
            if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
                td.style.background = '#e6f7ff';
                td.style.borderRadius = '4px';
                td.style.fontWeight = '700';
            }

            row.appendChild(td);

            if (weekday === 6 && day !== daysInMonth) {
                tbody.appendChild(row);
                row = document.createElement('tr');
            }
        }

        // Fill trailing empty cells
        const lastRowCells = row.children.length;
        if (lastRowCells > 0) {
            for (let i = lastRowCells; i < 7; i++) {
                const td = document.createElement('td');
                td.style.padding = '8px 4px';
                td.style.height = cellHeight;
                td.style.verticalAlign = 'middle';
                row.appendChild(td);
            }
            tbody.appendChild(row);
        }

        table.appendChild(tbody);

        // Styling wrapper
        const wrap = document.createElement('div');
        wrap.style.padding = '6px';
        wrap.style.height = '100%';
        wrap.appendChild(table);

        root.appendChild(wrap);

        // After table is rendered, fetch reminders for this month (if logged in)
        if (loggedIn) {
            fetchRemindersForMonth(year, month + 1).then(map => {
                // map: dateStr -> [reminders]
                if (!map) return;
                Object.keys(map).forEach(dateStr => {
                    // find cell with matching data-date
                    const cell = root.querySelector(`td[data-date="${dateStr}"]`);
                    if (!cell) return;

                    // create container for small indicators if not present
                    let indicatorWrap = cell.querySelector('.rem-indicators');
                    if (!indicatorWrap) {
                        indicatorWrap = document.createElement('div');
                        indicatorWrap.className = 'rem-indicators';
                        indicatorWrap.style.position = 'absolute';
                        indicatorWrap.style.right = '6px';
                        indicatorWrap.style.top = '6px';
                        indicatorWrap.style.display = 'flex';
                        indicatorWrap.style.gap = '4px';
                        // make cell position relative so absolute works
                        cell.style.position = 'relative';
                        cell.appendChild(indicatorWrap);
                    }

                    // Render a single indicator per date: green for one reminder, orange for multiple reminders
                    const reminders = map[dateStr];
                    if (reminders && reminders.length > 0) {
                        const bubble = document.createElement('span');
                        bubble.className = 'rem-bubble';
                        // title shows either the single reminder title or a count for multiple
                        bubble.title = reminders.length === 1 ? (reminders[0].title || '') : (reminders.length + ' reminders');
                        bubble.style.width = '10px';
                        bubble.style.height = '10px';
                        bubble.style.borderRadius = '50%';
                        bubble.style.display = 'inline-block';
                        bubble.style.boxShadow = '0 0 0 2px rgba(0,0,0,0.04)';
                        bubble.dataset.date = dateStr;

                        if (reminders.length === 1) {
                            // single reminder - green
                            bubble.style.background = '#28a745';
                            bubble.style.boxShadow = '0 0 0 2px rgba(40,167,69,0.12)';
                        } else {
                            // multiple reminders - orange circle
                            bubble.style.background = '#ff8c00'; // orange
                            bubble.style.boxShadow = '0 0 0 2px rgba(255,140,0,0.12)';
                        }

                        // show tooltip on hover — use the cell as the anchor so hovering anywhere on the date shows it
                        bubble.addEventListener('mouseenter', function () {
                            showReminderTooltip(cell, reminders);
                        });

                        indicatorWrap.appendChild(bubble);
                    }

                    // also allow hovering the entire cell to show details (use closure to reference cell)
                    cell.addEventListener('mouseenter', function () {
                        showReminderTooltip(cell, map[dateStr]);
                    });
                    cell.addEventListener('mouseleave', function () {
                        hideReminderTooltip();
                    });
                });
            }).catch(() => {
                // ignore fetch errors silently
            });
        }
    }

    // Fetch reminders JSON for a specific year+month (month: 1-12). Returns a map of dateStr->array
    function fetchRemindersForMonth(year, month) {
        return new Promise(function (resolve, reject) {
            try {
                const url = window.location.pathname + '?c=admin&a=reminders&year=' + encodeURIComponent(year) + '&month=' + encodeURIComponent(month);
                fetch(url, { credentials: 'same-origin' }).then(function (resp) {
                    if (!resp.ok) { resolve(null); return; }
                    return resp.json();
                }).then(function (data) {
                    if (!data || !Array.isArray(data)) { resolve(null); return; }
                    const map = {};
                    data.forEach(item => {
                        const dateStr = item.date; // expected YYYY-MM-DD
                        if (!dateStr) return;
                        if (!map[dateStr]) map[dateStr] = [];
                        map[dateStr].push(item);
                    });
                    resolve(map);
                }).catch(reject);
            } catch (ex) {
                reject(ex);
            }
        });
    }

    // Tooltip management (keep tooltip visible while hovering cell or the tooltip itself)
    let _remTooltip = null;
    let _remHideTimeout = null;

    function _removeTooltipImmediate() {
        if (_remHideTimeout) {
            clearTimeout(_remHideTimeout);
            _remHideTimeout = null;
        }
        if (_remTooltip) {
            try { document.body.removeChild(_remTooltip); } catch (e) {}
            _remTooltip = null;
        }
    }

    function showReminderTooltip(targetEl, reminders) {
        // remove any pending hide and any existing tooltip immediately
        _removeTooltipImmediate();
        if (!reminders || reminders.length === 0) return;
        const rect = targetEl.getBoundingClientRect();
        const tip = document.createElement('div');
        tip.className = 'rem-tooltip';
        tip.style.position = 'absolute';
        tip.style.zIndex = 9999;
        tip.style.minWidth = '180px';
        tip.style.maxWidth = '320px';
        tip.style.background = '#fff';
        tip.style.border = '1px solid #ddd';
        tip.style.boxShadow = '0 4px 12px rgba(0,0,0,0.08)';
        tip.style.padding = '8px';
        tip.style.fontSize = '13px';
        tip.style.color = '#222';
        tip.style.borderRadius = '6px';

        // Build content
        const list = document.createElement('div');
        reminders.forEach(r => {
            const item = document.createElement('div');
            item.style.marginBottom = '6px';

            const title = document.createElement('div');
            title.textContent = r.title || '(no title)';
            title.style.fontWeight = '700';
            title.style.marginBottom = '2px';
            item.appendChild(title);

            const meta = document.createElement('div');
            meta.textContent = r.plant_name ? r.plant_name : '';
            meta.style.fontStyle = 'italic';
            meta.style.fontSize = '12px';
            meta.style.color = '#444';
            item.appendChild(meta);

            if (r.notes) {
                const notes = document.createElement('div');
                notes.textContent = r.notes;
                notes.style.fontSize = '12px';
                notes.style.color = '#333';
                notes.style.marginTop = '4px';
                item.appendChild(notes);
            }

            list.appendChild(item);
        });

        tip.appendChild(list);

        // Keep tooltip open while hovering it
        tip.addEventListener('mouseenter', function () {
            if (_remHideTimeout) { clearTimeout(_remHideTimeout); _remHideTimeout = null; }
        });
        tip.addEventListener('mouseleave', function () {
            // schedule hide when pointer leaves the tooltip
            hideReminderTooltip();
        });

        document.body.appendChild(tip);
        _remTooltip = tip;

        // Position tooltip: try above target, otherwise below
        const scrollY = window.scrollY || window.pageYOffset;
        let left = rect.left + (rect.width / 2) - (tip.offsetWidth / 2);
        left = Math.max(8, Math.min(left, window.innerWidth - tip.offsetWidth - 8));

        let top = rect.top + scrollY - tip.offsetHeight - 8;
        if (top < scrollY + 8) {
            top = rect.top + scrollY + rect.height + 8;
        }

        tip.style.left = left + 'px';
        tip.style.top = top + 'px';
    }

    function hideReminderTooltip() {
        // schedule a short delay so moving between cell and tooltip doesn't immediately hide it
        if (_remHideTimeout) { clearTimeout(_remHideTimeout); }
        _remHideTimeout = setTimeout(function () {
            _removeTooltipImmediate();
        }, 120);
    }

    function openModal() {
        monthsFromToday = 0;
        render();
        overlay.style.display = 'flex';
        // focus close button for accessibility
        closeBtn.focus();
    }

    function closeModal() {
        overlay.style.display = 'none';
    }

    // Events
    openBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (!loggedIn) {
            // Not logged in - give gentle feedback
            // If there's a login link on the page we could redirect, but keep it simple
            alert('Please log in to view the calendar.');
            return;
        }
        openModal();
    });

    closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        closeModal();
    });

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });

    prevBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (monthsFromToday > -LIMIT) {
            monthsFromToday--;
            render();
        }
    });

    nextBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (monthsFromToday < LIMIT) {
            monthsFromToday++;
            render();
        }
    });

    // Keyboard: Esc to close
    document.addEventListener('keydown', function (e) {
        if (overlay.style.display !== 'none' && e.key === 'Escape') {
            closeModal();
        }
    });

    // Expose for debugging
    window._adminCalendar = {
        open: openModal,
        close: closeModal,
        render: render
    };

})();
