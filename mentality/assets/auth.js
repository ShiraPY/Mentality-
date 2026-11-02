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
