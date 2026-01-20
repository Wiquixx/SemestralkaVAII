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
    function cloneDate(d) { return new Date(d.getTime()); }

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
