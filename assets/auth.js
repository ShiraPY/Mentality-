// assets/auth.js - login / register / reset pages: show or hide the password.
document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.toggle-pw');
    if (!btn) { return; }
    var input = btn.parentElement.querySelector('input');
    var show = (input.type === 'password');
    input.type = show ? 'text' : 'password';
    btn.classList.toggle('on', show);
    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});

// ---------- Login page: a new quote every few seconds (fades out and in) ----------
(function () {
    var el = document.getElementById('login-quote');
    if (!el) { return; }
    var quotes;
    try { quotes = JSON.parse(el.dataset.quotes); } catch (e) { return; }
    if (!quotes || quotes.length < 2) { return; }
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }

    function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
    function html(q) {
        var w = q.split(' ');
        if (w.length < 4) { return esc(q); }
        return esc(w.slice(0, -2).join(' ')) + ' <span class="accent">' + esc(w.slice(-2).join(' ')) + '</span>';
    }
    var current = el.textContent.trim().replace(/\s+/g, ' ');

    setInterval(function () {
        var q;
        do { q = quotes[Math.floor(Math.random() * quotes.length)]; } while (q === current);
        current = q;
        el.classList.add('fade');
        setTimeout(function () { el.innerHTML = html(q); el.classList.remove('fade'); }, 450);
    }, 9000);
})();

// ---------- Login page: live clock and Caps Lock warning ----------
(function () {
    var clock = document.getElementById('lg-clock');
    if (clock) {
        var tick = function () {
            var n = new Date();
            clock.textContent = n.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', timeZone: 'Asia/Manila' }) +
                ' \u00B7 ' + n.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', timeZone: 'Asia/Manila' });
        };
        tick();
        setInterval(tick, 15000);
    }
    var note = document.getElementById('lg-caps'), pw = document.getElementById('password');
    if (note && pw) {
        var check = function (ev) { note.hidden = !(ev.getModifierState && ev.getModifierState('CapsLock')); };
        pw.addEventListener('keydown', check);
        pw.addEventListener('keyup', check);
        pw.addEventListener('blur', function () { note.hidden = true; });
    }
})();
