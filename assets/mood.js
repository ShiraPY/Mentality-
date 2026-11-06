// assets/mood.js - Mood page: the Edit pop-up for a past entry.
document.addEventListener('DOMContentLoaded', function () {
    var dlg = document.getElementById('mood-edit-modal');
    if (!dlg) { return; }

    function fill(m) {
        document.getElementById('mood-edit-date').value  = m.date;
        document.getElementById('mood-edit-label').textContent = m.label || m.date;
        document.getElementById('mood-edit-note').value  = m.note || '';
        Array.prototype.forEach.call(dlg.querySelectorAll('input[name=mood]'), function (r) { r.checked = (r.value === m.mood); });
    }

    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('.btn-edit');
        if (!btn) { return; }
        var box = document.getElementById('mood-edit-errors');
        if (box) { box.innerHTML = ''; }
        fill(JSON.parse(btn.dataset.mood));
        dlg.showModal();
    });

    if (dlg.dataset.reopen) {
        try { fill(JSON.parse(dlg.dataset.reopen)); dlg.showModal(); } catch (e) { /* ignore */ }
    }
});
