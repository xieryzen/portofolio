/* Portfolio CMS — tema terang/gelap tanpa kedip (dijalankan sebelum <body>) */
(function () {
    'use strict';

    const KEY = 'portfolio-theme';
    const root = document.documentElement;
    const media = typeof window.matchMedia === 'function'
        ? window.matchMedia('(prefers-color-scheme: light)')
        : null;

    const storedMode = () => {
        try {
            const value = localStorage.getItem(KEY);
            return value === 'light' || value === 'dark' ? value : 'auto';
        } catch (error) {
            return 'auto';
        }
    };

    const resolve = (mode) => {
        if (mode === 'light' || mode === 'dark') return mode;
        return media && media.matches ? 'light' : 'dark';
    };

    const apply = (mode) => {
        const theme = resolve(mode);

        root.setAttribute('data-theme', theme);
        root.setAttribute('data-theme-mode', mode);

        const colorScheme = document.querySelector('meta[name="color-scheme"]');
        if (colorScheme) colorScheme.setAttribute('content', theme === 'light' ? 'light' : 'dark');

        const themeColor = document.querySelector('meta[name="theme-color"]');
        if (themeColor) themeColor.setAttribute('content', theme === 'light' ? '#f5f7fd' : '#060a14');

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            const target = theme === 'light' ? 'gelap' : 'terang';
            button.setAttribute('aria-label', `Ganti ke tema ${target}`);
            button.setAttribute('title', `Tema ${theme === 'light' ? 'terang' : 'gelap'} — klik untuk ${target}`);
            button.setAttribute('aria-pressed', String(theme === 'light'));
        });
    };

    apply(storedMode());

    if (media) {
        const onChange = () => {
            if (storedMode() === 'auto') apply('auto');
        };

        if (typeof media.addEventListener === 'function') {
            media.addEventListener('change', onChange);
        } else if (typeof media.addListener === 'function') {
            media.addListener(onChange);
        }
    }

    document.addEventListener('click', (event) => {
        const button = event.target instanceof Element ? event.target.closest('[data-theme-toggle]') : null;
        if (!button) return;

        const next = resolve(storedMode()) === 'light' ? 'dark' : 'light';

        try {
            localStorage.setItem(KEY, next);
        } catch (error) {
            /* mode privat: tema tetap berubah untuk sesi ini */
        }

        apply(next);
    });

    window.PortfolioTheme = {
        apply,
        current: () => resolve(storedMode()),
        reset: () => {
            try {
                localStorage.removeItem(KEY);
            } catch (error) {
                /* abaikan */
            }
            apply('auto');
        },
    };
})();
