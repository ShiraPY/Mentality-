// assets/goals.js - Goals page: Edit pop-up + re-opening a pop-up when the server found a problem.
document.addEventListener('DOMContentLoaded', function () {
    var editDlg = document.getElementById('goal-edit-modal');
    var addDlg  = document.getElementById('goal-add-modal');

    function fillEdit(g) {
        document.getElementById('edit-goal-id').value     = g.id;
        var delId = document.getElementById('delete-goal-id');
        if (delId) { delId.value = g.id; }
        document.getElementById('edit-title').value       = g.title || '';
        document.getElementById('edit-description').value = g.description || '';
        document.getElementById('edit-category').value    = String(g.category || 0);

        // A completed goal can only change its text; date and habits are locked.
        var extra = document.getElementById('edit-extra');
        extra.hidden = !!g.completed;
        Array.prototype.forEach.call(extra.querySelectorAll('input'), function (i) { i.disabled = !!g.completed; });

        document.getElementById('edit-date').value = g.date || '';
        var habits = (g.habits || []).map(String);
        Array.prototype.forEach.call(extra.querySelectorAll('input.edit-habit'), function (cb) {
            cb.checked = habits.indexOf(cb.value) !== -1;
        });
    }

    if (editDlg) {
        // Click on a goal's Edit button
        document.addEventListener('click', function (ev) {
            var btn = ev.target.closest('.btn-edit');
            if (!btn) { return; }
            var box = document.getElementById('edit-errors');
            if (box) { box.innerHTML = ''; }
            fillEdit(JSON.parse(btn.dataset.goal));
            editDlg.showModal();
        });

        // Server found a problem with an edit: show the pop-up again with what the user typed
        if (editDlg.dataset.reopen) {
            try { fillEdit(JSON.parse(editDlg.dataset.reopen)); editDlg.showModal(); } catch (e) { /* ignore */ }
        }
    }

    if (addDlg && addDlg.dataset.reopen === '1') { addDlg.showModal(); }
});
