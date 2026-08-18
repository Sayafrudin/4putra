/**
 * toast.js — Shared toast notification system (Optimized)
 * Taruh di: public/js/toast.js
 */
(function () {
    'use strict';

    var container = null;

    var ICONS = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px] fill-green-700 shrink-0" viewBox="0 0 330 330"><path d="M165 0C74.019 0 0 74.019 0 165s74.019 165 165 165 165-74.019 165-165S255.981 0 165 0m0 300c-74.44 0-135-60.561-135-135S90.56 30 165 30s135 60.561 135 135-60.561 135-135 135"/><path d="m226.872 106.664-84.854 84.853-38.89-38.891c-5.857-5.857-15.355-5.858-21.213-.001-5.858 5.858-5.858 15.355 0 21.213l49.496 49.498a15 15 0 0 0 10.606 4.394h.001c3.978 0 7.793-1.581 10.606-4.393l95.461-95.459c5.858-5.858 5.858-15.355 0-21.213s-15.355-5.859-21.213-.001"/></svg>',
        warning: '<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px] fill-yellow-700 shrink-0" viewBox="0 0 486.463 486.463"><path d="M243.225 333.382c-13.6 0-25 11.4-25 25s11.4 25 25 25c13.1 0 25-11.4 24.4-24.4.6-14.3-10.7-25.6-24.4-25.6"/><path d="M474.625 421.982c15.7-27.1 15.8-59.4.2-86.4l-156.6-271.2c-15.5-27.3-43.5-43.5-74.9-43.5s-59.4 16.3-74.9 43.4l-156.8 271.5c-15.6 27.3-15.5 59.8.3 86.9 15.6 26.8 43.5 42.9 74.7 42.9h312.8c31.3 0 59.4-16.3 75.2-43.6m-34-19.6c-8.7 15-24.1 23.9-41.3 23.9h-312.8c-17 0-32.3-8.7-40.8-23.4-8.6-14.9-8.7-32.7-.1-47.7l156.8-271.4c8.5-14.9 23.7-23.7 40.9-23.7 17.1 0 32.4 8.9 40.9 23.8l156.7 271.4c8.4 14.6 8.3 32.2-.3 47.1"/><path d="M237.025 157.882c-11.9 3.4-19.3 14.2-19.3 27.3.6 7.9 1.1 15.9 1.7 23.8 1.7 30.1 3.4 59.6 5.1 89.7.6 10.2 8.5 17.6 18.7 17.6s18.2-7.9 18.7-18.2c0-6.2 0-11.9.6-18.2 1.1-19.3 2.3-38.6 3.4-57.9.6-12.5 1.7-25 2.3-37.5 0-4.5-.6-8.5-2.3-12.5-5.1-11.2-17-16.9-28.9-14.1"/></svg>',
        error: '<svg xmlns="http://www.w3.org/2000/svg" class="size-[18px] fill-red-700 shrink-0" viewBox="0 0 512 512"><path d="M256 0C114.508 0 0 114.497 0 256c0 141.493 114.497 256 256 256 141.492 0 256-114.497 256-256C512 114.507 397.503 0 256 0m0 472c-119.384 0-216-96.607-216-216 0-119.385 96.607-216 216-216 119.384 0 216 96.607 216 216 0 119.385-96.607 216-216 216"/><path d="M343.586 315.302 284.284 256l59.302-59.302c7.81-7.81 7.811-20.473.001-28.284-7.812-7.811-20.475-7.81-28.284 0L256 227.716l-59.303-59.302c-7.809-7.811-20.474-7.811-28.284 0s-7.81 20.474.001 28.284L227.716 256l-59.302 59.302c-7.811 7.811-7.812 20.474-.001 28.284 7.813 7.812 20.476 7.809 28.284 0L256 284.284l59.303 59.302c7.808 7.81 20.473 7.811 28.284 0s7.81-20.474-.001-28.284"/></svg>',
    };

    var COLORS = {
        success: 'bg-green-50 dark:bg-green-900/50 border-green-200 dark:border-green-800 border-l-green-700',
        warning: 'bg-yellow-50 dark:bg-yellow-900/50 border-yellow-200 dark:border-yellow-800 border-l-yellow-700',
        error: 'bg-red-50 dark:bg-red-900/50 border-red-200 dark:border-red-800 border-l-red-700',
    };

    function getContainer() {
        if (container && document.contains(container)) return container;
        container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed top-4 right-4 z-[9999] flex flex-col gap-3 pointer-events-none';
            document.body.appendChild(container);
        }
        return container;
    }

    function showToast(type, title, message, duration) {
        duration = duration === undefined ? 3500 : duration;
        try {
            var wrap = getContainer();
            var el = document.createElement('div');
            el.className = (COLORS[type] || COLORS.success) +
                ' p-4 rounded-md border border-l-4 min-w-xs max-w-sm relative shadow-lg pointer-events-auto ' +
                'transform translate-x-full opacity-0 transition-all duration-300 ease-out';

            var icon = ICONS[type] || ICONS.success;
            el.innerHTML =
                '<div class="flex items-start gap-2.5">' + icon +
                '<div class="flex-1 min-w-0"><p class="text-sm text-slate-900 dark:text-slate-100 font-medium leading-tight">' + title + '</p>' +
                '<p class="text-xs mt-1 text-slate-600 dark:text-slate-300">' + message + '</p></div>' +
                '<button type="button" class="ml-auto opacity-70 hover:opacity-100 shrink-0" aria-label="Dismiss">' +
                '<svg class="size-2.5 fill-slate-500 dark:fill-slate-400" viewBox="0 0 329.269 329"><path d="M194.8 164.77 323.013 36.555c8.343-8.34 8.343-21.825 0-30.164-8.34-8.34-21.825-8.34-30.164 0L164.633 134.605 36.422 6.391c-8.344-8.34-21.824-8.34-30.164 0-8.344 8.34-8.344 21.824 0 30.164l128.21 128.215L6.259 292.984c-8.344 8.34-8.344 21.825 0 30.164a21.27 21.27 0 0 0 15.082 6.25c5.46 0 10.922-2.09 15.082-6.25l128.21-128.214 128.216 128.214a21.27 21.27 0 0 0 15.082 6.25c5.46 0 10.922-2.09 15.082-6.25 8.343-8.34 8.343-21.824 0-30.164z"/></svg>' +
                '</button></div>';
            wrap.appendChild(el);

            // Trigger animasi masuk
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    el.classList.remove('translate-x-full', 'opacity-0');
                    el.classList.add('translate-x-0', 'opacity-100');
                });
            });

            // Event listener untuk close button
            el.querySelector('button').addEventListener('click', function () { removeToast(el); });

            // Auto remove
            if (duration > 0) setTimeout(function () { removeToast(el); }, duration);
        } catch (err) {
            console.error('showToast error:', err);
        }
    }

    function removeToast(el) {
        if (el._closing) return;
        el._closing = true;

        el.classList.remove('translate-x-0', 'opacity-100');
        el.classList.add('translate-x-full', 'opacity-0');

        setTimeout(function () { el.remove(); }, 300);
    }

    // Expose globally
    window.showToast = showToast;
})();