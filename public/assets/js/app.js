/* Portfolio CMS — interaksi UI tanpa dependensi */
(function () {
    'use strict';

    const $ = (selector, scope = document) => scope.querySelector(selector);
    const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));

    /* ---------- Header scroll ---------- */
    const header = $('.site-header');
    if (header) {
        const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ---------- Mobile nav ---------- */
    const navToggle = $('[data-nav-toggle]');
    const mobileNav = $('[data-mobile-nav]');
    if (navToggle && mobileNav) {
        navToggle.addEventListener('click', () => {
            const open = mobileNav.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', String(open));
            navToggle.innerHTML = open
                ? '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>'
                : '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>';
        });
    }

    /* ---------- Toast ---------- */
    $$('.toast').forEach((toast) => {
        const close = $('.toast-close', toast);
        const dismiss = () => {
            toast.classList.add('is-hiding');
            setTimeout(() => toast.remove(), 260);
        };
        if (close) close.addEventListener('click', dismiss);
        setTimeout(dismiss, 6200);
    });

    /* ---------- Reveal on scroll ---------- */
    const revealItems = $$('[data-reveal]');
    if (revealItems.length > 0) {
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

            revealItems.forEach((item, index) => {
                item.style.transitionDelay = Math.min(index % 6, 5) * 55 + 'ms';
                observer.observe(item);
            });
        } else {
            revealItems.forEach((item) => item.classList.add('is-visible'));
        }
    }

    /* ---------- Lightbox ---------- */
    const lightbox = $('[data-lightbox-modal]');
    if (lightbox) {
        const image = $('img', lightbox);
        const caption = $('[data-lightbox-caption]', lightbox);
        const closeBtn = $('[data-lightbox-close]', lightbox);

        const open = (src, text) => {
            image.src = src;
            image.alt = text || 'Dokumentasi project';
            if (caption) caption.textContent = text || '';
            lightbox.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        };

        const close = () => {
            lightbox.classList.remove('is-open');
            document.body.style.overflow = '';
            image.removeAttribute('src');
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-lightbox]');
            if (trigger) {
                event.preventDefault();
                open(trigger.getAttribute('data-lightbox'), trigger.getAttribute('data-caption') || '');
            }
        });

        if (closeBtn) closeBtn.addEventListener('click', close);
        lightbox.addEventListener('click', (event) => {
            if (event.target === lightbox) close();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && lightbox.classList.contains('is-open')) close();
        });
    }

    /* ---------- Toggle password ---------- */
    $$('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.getAttribute('data-toggle-password'));
            if (!target) return;
            const show = target.type === 'password';
            target.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            button.innerHTML = show
                ? '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>'
                : '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M3 3l18 18M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6M9.9 5.2A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-2.2 3.1M6.3 6.3A17 17 0 0 0 2 12s3.6 7 10 7a10.6 10.6 0 0 0 4.2-.9"/></svg>';
        });
    });

    /* ---------- Admin sidebar ---------- */
    const sidebar = $('[data-sidebar]');
    const sidebarToggle = $('[data-sidebar-toggle]');
    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
        });
        document.addEventListener('click', (event) => {
            if (window.innerWidth > 900) return;
            if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('is-open');
            }
        });
    }

    /* ---------- Konfirmasi aksi berbahaya ---------- */
    const modal = $('[data-confirm-modal]');
    if (modal) {
        const titleEl = $('[data-confirm-title]', modal);
        const textEl = $('[data-confirm-text]', modal);
        const okBtn = $('[data-confirm-ok]', modal);
        const cancelBtn = $('[data-confirm-cancel]', modal);
        let pendingForm = null;

        const close = () => {
            modal.classList.remove('is-open');
            pendingForm = null;
        };

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
            if (form.dataset.confirmed === '1') return;

            event.preventDefault();
            pendingForm = form;
            if (titleEl) titleEl.textContent = form.getAttribute('data-confirm-title') || 'Konfirmasi';
            if (textEl) textEl.textContent = form.getAttribute('data-confirm') || 'Apakah Anda yakin?';
            modal.classList.add('is-open');
            if (okBtn) okBtn.focus();
        });

        if (okBtn) {
            okBtn.addEventListener('click', () => {
                if (pendingForm) {
                    pendingForm.dataset.confirmed = '1';
                    pendingForm.submit();
                }
                close();
            });
        }

        if (cancelBtn) cancelBtn.addEventListener('click', close);
        modal.addEventListener('click', (event) => {
            if (event.target === modal) close();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close();
        });
    }

    /* ---------- Preview gambar sebelum upload ---------- */
    $$('[data-preview-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const targetSelector = input.getAttribute('data-preview-input');
            const target = document.querySelector(targetSelector);
            if (!target) return;
            const file = input.files && input.files[0];
            if (!file || !file.type.startsWith('image/')) return;
            const url = URL.createObjectURL(file);
            target.innerHTML = '<img alt="Pratinjau gambar">';
            $('img', target).src = url;
        });
    });

    /* ---------- Pesan masuk: buka/tutup ---------- */
    $$('[data-msg-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const card = button.closest('.msg-card');
            if (card) card.classList.toggle('is-open');
        });
    });

    /* ---------- Kategori keahlian custom ---------- */
    const categorySelect = $('[data-skill-category]');
    const categoryInput = $('[data-skill-category-custom]');
    if (categorySelect && categoryInput) {
        const sync = () => {
            categoryInput.classList.toggle('hidden', categorySelect.value !== '__custom');
        };
        categorySelect.addEventListener('change', sync);
        sync();
    }

    /* ---------- Counter animasi ringan ---------- */
    const counters = $$('[data-count-to]');
    if (counters.length && 'IntersectionObserver' in window) {
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                const target = parseInt(el.getAttribute('data-count-to'), 10) || 0;
                const duration = 900;
                const start = performance.now();

                const tick = (now) => {
                    const progress = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = String(Math.round(target * eased));
                    if (progress < 1) requestAnimationFrame(tick);
                };
                requestAnimationFrame(tick);
                counterObserver.unobserve(el);
            });
        }, { threshold: 0.4 });

        counters.forEach((el) => counterObserver.observe(el));
    }
})();
