// assets/settings.js - Appearance settings: the page changes instantly while you choose. Nothing is saved until you click Save.
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('appearance-form');
    if (!form) { return; }

    var root     = document.documentElement;
    var fonts    = JSON.parse(form.dataset.fonts);
    var defaults = JSON.parse(form.dataset.defaults);
    var custom   = form.elements.custom_on;
    var colorInputs = [form.elements.bg_color, form.elements.card_color, form.elements.text_color];

    function set(name, value) { root.style.setProperty(name, value); }

    function apply() {
        var themeInput = form.querySelector('input[name=appearance]:checked');
        var theme = themeInput ? themeInput.value : 'dark';
        root.setAttribute('data-theme', theme);

        colorInputs.forEach(function (i) { i.disabled = !custom.checked; });

        var base = defaults[theme];
        set('--bg',   custom.checked ? form.elements.bg_color.value   : base.bg);
        set('--card', custom.checked ? form.elements.card_color.value : base.card);
        set('--text', custom.checked ? form.elements.text_color.value : base.text);
        set('--input-bg', 'color-mix(in srgb, var(--bg) 72%, var(--card))');
        set('--hover',    'color-mix(in srgb, var(--text) 8%, var(--card))');
        set('--border',   'color-mix(in srgb, var(--text) 16%, var(--card))');
        set('--muted',    'color-mix(in srgb, var(--text) 62%, var(--card))');

        var acc = form.querySelector('input[name=accent_color]:checked');
        var accent = (acc && acc.value) ? acc.value : '#3b82f6';
        set('--primary', accent);
        set('--primary-hover', 'color-mix(in srgb, ' + accent + ' 82%, #000)');
        set('--link', 'color-mix(in srgb, ' + accent + ' 70%, var(--text))');

        set('--font', fonts[form.elements.font_choice.value] || fonts.modern);
    }

    form.addEventListener('input', apply);
    form.addEventListener('change', apply);
    apply();
});
