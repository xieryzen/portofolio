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
        const setOpen = (open) => {
            mobileNav.classList.toggle('is-open', open);
            navToggle.setAttribute('aria-expanded', String(open));
            navToggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
            navToggle.innerHTML = open
                ? '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>'
                : '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>';
        };

        navToggle.addEventListener('click', () => setOpen(!mobileNav.classList.contains('is-open')));

        // Tutup setelah memilih tautan, saat menekan Escape, atau klik di luar panel.
        mobileNav.addEventListener('click', (event) => {
            if (event.target instanceof Element && event.target.closest('a')) setOpen(false);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && mobileNav.classList.contains('is-open')) {
                setOpen(false);
                navToggle.focus();
            }
        });

        document.addEventListener('pointerdown', (event) => {
            if (!mobileNav.classList.contains('is-open')) return;
            const target = event.target;
            if (!(target instanceof Node)) return;
            if (mobileNav.contains(target) || navToggle.contains(target)) return;
            setOpen(false);
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
    /* ---------- Efek klik: ripple, spark, dan getar ---------- */
    const reduceMotion = typeof window.matchMedia === 'function'
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : { matches: false };
    const CLICKABLE = 'a[href], button, [role="button"], .btn, .chip, .doc-item, .project-card, summary, .nav-link, .social-link, .filter-btn';
    const BLOCKED = '[aria-disabled="true"], .is-disabled, [data-blocked], button[disabled], input[disabled], select[disabled], textarea[disabled]';

    const shake = (el) => {
        if (!el || !(el instanceof Element)) return;
        el.classList.remove('shake');
        void el.offsetWidth; // paksa reflow agar animasi bisa diputar ulang
        el.classList.add('shake');
        el.addEventListener('animationend', () => el.classList.remove('shake'), { once: true });
    };

    const rippleAt = (el, x, y) => {
        if (reduceMotion.matches || el.dataset.noRipple !== undefined) return;

        const rect = el.getBoundingClientRect();
        if (!rect.width || !rect.height) return;

        const size = Math.max(rect.width, rect.height) * 2.2;
        const restorePosition = el.style.position;
        const restoreOverflow = el.style.overflow;

        if (getComputedStyle(el).position === 'static') el.style.position = 'relative';
        el.style.overflow = 'hidden';

        const ink = document.createElement('span');
        ink.className = 'ripple-ink';
        ink.setAttribute('aria-hidden', 'true');
        ink.style.width = size + 'px';
        ink.style.height = size + 'px';
        ink.style.left = (x - rect.left) + 'px';
        ink.style.top = (y - rect.top) + 'px';
        el.appendChild(ink);

        const cleanup = () => {
            if (!ink.isConnected) return;
            ink.remove();
            el.style.position = restorePosition;
            el.style.overflow = restoreOverflow;
        };

        ink.addEventListener('animationend', cleanup, { once: true });
        setTimeout(cleanup, 900);
    };

    const sparkAt = (x, y) => {
        if (reduceMotion.matches) return;

        const spark = document.createElement('span');
        spark.className = 'click-spark';
        spark.setAttribute('aria-hidden', 'true');
        spark.style.left = x + 'px';
        spark.style.top = y + 'px';
        document.body.appendChild(spark);
        spark.addEventListener('animationend', () => spark.remove(), { once: true });
        setTimeout(() => spark.remove(), 900);
    };

    document.addEventListener('pointerdown', (event) => {
        if (event.button > 0) return;

        const target = event.target instanceof Element ? event.target : null;
        if (!target) return;

        const blocked = target.closest(BLOCKED);
        if (blocked) {
            shake(blocked);
            return;
        }

        const clickable = target.closest(CLICKABLE);
        if (clickable && !clickable.matches('input, select, textarea, option, label')) {
            rippleAt(clickable, event.clientX, event.clientY);
        }

        // Kolom isian tidak perlu cincin klik — terlalu ramai saat mengetik.
        const isField = target.closest('input, textarea, select, label, .field');
        if (!clickable && !isField) sparkAt(event.clientX, event.clientY);
    }, { capture: true, passive: true });

    /* Tombol native `disabled` tidak menerima event, jadi dengarkan dari induknya. */
    const bindBlockedButtons = () => {
        $$('button[disabled], input[type="submit"][disabled], a.is-disabled, .is-unclickable').forEach((el) => {
            if (el.dataset.shakeReady === '1') return;
            el.dataset.shakeReady = '1';

            const host = el.parentElement;
            if (!host) return;

            host.addEventListener('pointerdown', (event) => {
                const rect = el.getBoundingClientRect();
                const inside = event.clientX >= rect.left && event.clientX <= rect.right
                    && event.clientY >= rect.top && event.clientY <= rect.bottom;

                if (inside) shake(el);
            }, { passive: true });
        });
    };

    bindBlockedButtons();

    if ('MutationObserver' in window) {
        let queued = false;
        const observer = new MutationObserver(() => {
            if (queued) return;
            queued = true;
            requestAnimationFrame(() => {
                queued = false;
                bindBlockedButtons();
            });
        });
        observer.observe(document.body, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ['disabled', 'class'],
        });
    }

    /* Validasi form bawaan browser: kolom yang tidak valid ikut bergetar. */
    document.addEventListener('invalid', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        const field = target.closest('.field, .form-row') || target;
        shake(field);
    }, true);
})();
