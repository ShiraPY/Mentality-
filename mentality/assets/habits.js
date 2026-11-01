// assets/habits.js - My Habits page: one pop-up used for both "Add habit" and "Edit habit".
document.addEventListener('DOMContentLoaded', function () {
    var dlg = document.getElementById('habit-modal');
    if (!dlg) { return; }

    function fill(h) {
        var editing = !!h.id;
        document.getElementById('habit-modal-title').textContent = editing ? 'Edit Habit' : 'Add a New Habit';
        document.getElementById('habit-action').value   = editing ? 'update' : 'add';
        document.getElementById('habit-id').value       = editing ? h.id : '0';
        document.getElementById('habit-name').value     = h.name || '';
        document.getElementById('habit-category').value = String(h.category || 0);
        document.getElementById('habit-details').value  = h.details || '';
        document.getElementById('habit-submit').textContent = editing ? 'Save Changes' : 'Add Habit';
    }
    function clearErrors() { var box = document.getElementById('habit-errors'); if (box) { box.innerHTML = ''; } }

    document.addEventListener('click', function (ev) {
        if (ev.target.closest('[data-habit-new]')) {
            clearErrors(); fill({}); dlg.showModal(); return;
        }
        var edit = ev.target.closest('.btn-edit');
        if (edit) {
            clearErrors(); fill(JSON.parse(edit.dataset.habit)); dlg.showModal();
        }
    });

    // The server found a problem: show the pop-up again with what the user typed
    if (dlg.dataset.reopen) {
        try { fill(JSON.parse(dlg.dataset.reopen)); dlg.showModal(); } catch (e) { /* ignore */ }
    }
});
