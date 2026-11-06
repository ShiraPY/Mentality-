// assets/app.js - browser-side behaviour shared by every page.

// ---------- Pop-ups (<dialog> elements) ----------
//   <button data-open-modal="my-dialog-id">  opens it
//   <button data-close-modal>                closes the pop-up it sits in
//   <dialog data-static>                     is NOT closed by clicking the dimmed background (forms)
document.addEventListener('click', function (ev) {
    var opener = ev.target.closest('[data-open-modal]');
    if (opener) {
        var d = document.getElementById(opener.dataset.openModal);
        if (d && d.showModal) { d.showModal(); }
        return;
    }
    var closer = ev.target.closest('[data-close-modal]');
    if (closer) {
        var dlg = closer.closest('dialog');
        if (dlg) { dlg.close(); }
        return;
    }
    if (ev.target.tagName === 'DIALOG' && ev.target.open && !ev.target.hasAttribute('data-static')) {
        ev.target.close();                       // click on the dimmed background
    }
});

// ---------- Dropdown menus (profile, notifications) ----------
document.addEventListener('click', function (ev) {
    var toggle  = ev.target.closest('[data-dropdown-toggle]');
    var inMenu  = ev.target.closest('.dropdown-menu');
    var opensModal = ev.target.closest('[data-open-modal]');
    var current = toggle ? toggle.closest('[data-dropdown]') : null;

    if (inMenu && !opensModal) { return; }                    // clicking inside an open menu keeps it open
    Array.prototype.forEach.call(document.querySelectorAll('[data-dropdown].open'), function (dd) {
        if (dd !== current) { dd.classList.remove('open'); }
    });
    if (current) { current.classList.toggle('open'); }
});
document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') {
        Array.prototype.forEach.call(document.querySelectorAll('[data-dropdown].open'), function (dd) { dd.classList.remove('open'); });
    }
});

document.addEventListener('DOMContentLoaded', function () {

    // ---------- Live date and time (left column) ----------
    var clockDate = document.getElementById('live-date');
    var clockTime = document.getElementById('live-time');
    if (clockDate && clockTime) {
        var tick = function () {
            var now = new Date();
            clockDate.textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Asia/Manila' });
            clockTime.textContent = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit', timeZone: 'Asia/Manila' });
        };
        tick();
        setInterval(tick, 1000);
    }

    // ---------- Habit ticks on the Dashboard ----------
    var csrf = document.querySelector('meta[name="csrf"]');
    csrf = csrf ? csrf.content : '';

    var grid = document.getElementById('habit-grid');
    if (!grid) { return; }

    grid.addEventListener('change', function (ev) {
        var box = ev.target;
        if (!box.classList.contains('habit-check')) { return; }

        var body = new FormData();
        body.append('csrf', csrf);
        body.append('habit_id', box.dataset.habit);
        body.append('date', box.dataset.date);
        body.append('completed', box.checked ? '1' : '0');

        box.disabled = true;
        fetch(grid.dataset.url, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (res) {
                return res.text().then(function (text) {
                    var data = null;
                    try { data = JSON.parse(text); } catch (e) { /* not JSON */ }
                    return { status: res.status, ok: res.ok, data: data, text: text };
                });
            })
            .then(function (r) {
                if (!r.data) {
                    var preview = r.text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 200);
                    throw new Error('The server did not send valid data (HTTP ' + r.status + ').\n' +
                                    'Check that toggle_habit.php is inside your mentality folder.\n\n' + preview);
                }
                if (!r.ok || !r.data.ok) { throw new Error(r.data.message || 'Could not save.'); }
                updateSummary();
            })
            .catch(function (err) {
                box.checked = !box.checked;           // undo the tick if saving failed
                alert(err.message);
            })
            .then(function () { box.disabled = false; });
    });

    function updateSummary() {
        var boxes = Array.prototype.slice.call(grid.querySelectorAll('.habit-check'));
        var total = parseInt(grid.dataset.total, 10);
        var daysElapsed = parseInt(grid.dataset.days, 10);
        var today = grid.dataset.today;

        var doneToday = boxes.filter(function (b) { return b.checked && b.dataset.date === today; }).length;
        var doneWeek  = boxes.filter(function (b) { return b.checked; }).length;
        var pct = (total > 0 && daysElapsed > 0) ? Math.round(doneWeek / (total * daysElapsed) * 100) : 0;

        document.getElementById('stat-today').textContent = doneToday + ' / ' + total;
        document.getElementById('stat-week').textContent  = pct + '%';
    }
});
