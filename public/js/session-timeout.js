/**
 * session-timeout.js — Auto-logout setelah inaktivitas (seperti MyBCA)
 * Taruh di: public/js/session-timeout.js
 * Menggunakan CSS transition untuk animasi yang ringan
 *
 * Lifetime diambil dari meta[name="session-lifetime-minutes"] yang disuntikkan
 * layout admin dari config('session.lifetime') agar tidak drift dengan server.
 */
(function () {
    'use strict';

    var lifetimeMeta = document.querySelector('meta[name="session-lifetime-minutes"]');
    var TIMEOUT_MINUTES = Math.max(1, parseInt(lifetimeMeta ? lifetimeMeta.getAttribute('content') : '30', 10) || 30);
    // Popup peringatan muncul sedikitnya 5 menit sebelum sesi server kedaluwarsa
    var WARNING_MINUTES = 5;
    var TIMEOUT_MS = TIMEOUT_MINUTES * 60 * 1000;
    var WARNING_MS = WARNING_MINUTES * 60 * 1000;
    var PING_INTERVAL_MS = 10 * 60 * 1000; // Ping setiap 10 menit

    var warningTimer = null;
    var logoutTimer = null;
    var pingTimer = null;
    var warningShown = false;
    var modalEl = null;

    function getCsrfToken() {
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        return csrfMeta ? csrfMeta.getAttribute('content') : '';
    }

    function applyCsrf(data) {
        if (!data || !data.csrf) return;
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('content', data.csrf);
        }
        document.querySelectorAll('input[name="_token"]').forEach(function (input) {
            input.value = data.csrf;
        });
    }

    /**
     * Ping endpoint keep-alive.
     * Resolve {csrf} saat sukses (sesi diperbarui server),
     * resolve {expired:true} saat 401/419 (sesi telah mati).
     */
    function requestPing() {
        return fetch('/admin/ping', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
        }).then(function (res) {
            if (res.ok && (res.headers.get('content-type') || '').indexOf('application/json') !== -1) {
                return res.json();
            }
            return { expired: res.status === 401 || res.status === 419 };
        });
    }

    function pingSession() {
        requestPing().then(function (data) {
            if (data && !data.expired) {
                applyCsrf(data);
            }
        }).catch(function () {});
    }

    function createModal() {
        if (modalEl) return modalEl;

        modalEl = document.createElement('div');
        modalEl.id = 'session-timeout-modal';
        modalEl.className = 'fixed inset-0 z-[10001] hidden items-center justify-center bg-black/60 opacity-0 transition-opacity duration-300';
        modalEl.innerHTML =
            '<div class="bg-[#1e2530] rounded-xl p-6 max-w-sm w-full mx-4 shadow-2xl border border-gray-700 transform scale-95 opacity-0 transition-all duration-300 ease-out">' +
                '<div class="flex items-center gap-3 mb-4">' +
                    '<div class="w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center shrink-0">' +
                        '<svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>' +
                        '</svg>' +
                    '</div>' +
                    '<h3 class="text-lg font-bold text-white">Sesi Akan Berakhir</h3>' +
                '</div>' +
                '<p class="text-sm text-gray-400 mb-2">Anda telah tidak aktif selama ' + (TIMEOUT_MINUTES - WARNING_MINUTES) + ' menit.</p>' +
                '<p class="text-sm text-gray-400 mb-4">Sesi akan berakhir dalam <span id="session-countdown" class="text-yellow-400 font-bold">' + WARNING_MINUTES + ':00</span></p>' +
                '<div class="flex justify-end gap-3">' +
                    '<button id="session-logout-btn" class="px-4 py-2 text-sm text-gray-300 hover:text-white border border-gray-600 rounded-lg hover:border-gray-500 transition-colors">' +
                        'Logout' +
                    '</button>' +
                    '<button id="session-extend-btn" class="px-4 py-2 text-sm text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-lg transition-colors">' +
                        'Perpanjang Sesi' +
                    '</button>' +
                '</div>' +
            '</div>';

        document.body.appendChild(modalEl);

        document.getElementById('session-extend-btn').addEventListener('click', extendSession);
        document.getElementById('session-logout-btn').addEventListener('click', logoutNow);

        return modalEl;
    }

    function showModalAnimation(modal) {
        var dialog = modal.querySelector('div');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                modal.classList.remove('opacity-0');
                modal.classList.add('opacity-100');
                dialog.classList.remove('scale-95', 'opacity-0');
                dialog.classList.add('scale-100', 'opacity-100');
            });
        });
    }

    function showWarning() {
        if (warningShown) return;
        warningShown = true;

        var modal = createModal();
        showModalAnimation(modal);

        // Countdown timer menuju auto-logout
        var remaining = WARNING_MS;
        var countdownEl = document.getElementById('session-countdown');

        logoutTimer = setInterval(function () {
            remaining -= 1000;
            if (remaining <= 0) {
                logoutNow();
                return;
            }
            var mins = Math.floor(remaining / 60000);
            var secs = Math.floor((remaining % 60000) / 1000);
            if (countdownEl) {
                countdownEl.textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;
            }
        }, 1000);
    }

    /**
     * Edge case: sesi sudah mati di server (401/419) saat tombol perpanjang diklik.
     * Jangan redirect paksa & jangan auto-submit logout — biarkan user menyalin
     * data form panjang terlebih dahulu, lalu arahkan manual ke halaman login.
     */
    function showExpiredState() {
        if (logoutTimer) {
            clearInterval(logoutTimer); // hentikan auto-logout paksa
            logoutTimer = null;
        }
        warningShown = true;

        var modal = createModal();
        if (!modal.classList.contains('flex')) {
            showModalAnimation(modal);
        }

        var dialog = modal.querySelector('div');
        var titleEl = dialog.querySelector('h3');
        var paragraphs = dialog.querySelectorAll('p');
        var extendBtn = document.getElementById('session-extend-btn');
        var logoutBtn = document.getElementById('session-logout-btn');

        if (titleEl) {
            titleEl.textContent = 'Sesi Anda Telah Berakhir';
        }
        if (paragraphs.length > 0) {
            paragraphs[0].textContent = 'Silakan login kembali untuk melanjutkan.';
        }
        if (paragraphs.length > 1) {
            paragraphs[1].textContent = 'Data pada form tidak terkirim — salin teks penting terlebih dahulu bila diperlukan.';
        }

        if (extendBtn) {
            var loginBtn = extendBtn.cloneNode(true); // clone tanpa listener lama
            loginBtn.textContent = 'Ke Halaman Login';
            extendBtn.parentNode.replaceChild(loginBtn, extendBtn);
            loginBtn.addEventListener('click', function () {
                window.location.href = '/login';
            });
        }

        if (logoutBtn) {
            logoutBtn.style.display = 'none';
        }
    }

    function hideWarning() {
        warningShown = false;

        if (logoutTimer) {
            clearInterval(logoutTimer);
            logoutTimer = null;
        }

        if (modalEl) {
            var dialog = modalEl.querySelector('div');

            // Animasi keluar
            modalEl.classList.remove('opacity-100');
            modalEl.classList.add('opacity-0');
            dialog.classList.remove('scale-100', 'opacity-100');
            dialog.classList.add('scale-95', 'opacity-0');

            setTimeout(function () {
                modalEl.classList.add('hidden');
                modalEl.classList.remove('flex');
            }, 300);
        }
    }

    function extendSession(e) {
        if (e && typeof e.preventDefault === 'function') {
            e.preventDefault();
        }
        if (e && typeof e.stopPropagation === 'function') {
            e.stopPropagation();
        }

        // Tunggu keputusan server sebelum menutup modal atau mereset timer,
        // agar tidak ada reset palsu saat sesi ternyata sudah kedaluwarsa.
        requestPing().then(function (data) {
            if (data && data.expired) {
                showExpiredState();
                return;
            }
            applyCsrf(data);
            // Sukses: tutup popup + reset timer lokal ke 0, tanpa reload halaman.
            hideWarning();
            resetTimer();
        }).catch(function () {});
    }

    function logoutNow() {
        hideWarning();

        // Buat form logout dan submit
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '/logout';

        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_token';
            input.value = csrfToken.getAttribute('content');
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    }

    function resetTimer() {
        if (warningTimer) clearTimeout(warningTimer);

        warningTimer = setTimeout(function () {
            showWarning();
        }, TIMEOUT_MS - WARNING_MS);
    }

    // Event listeners untuk deteksi aktivitas
    var activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];

    function handleActivity() {
        if (!warningShown) {
            resetTimer();
        }
    }

    // Throttle event handler
    var throttleTimer = null;
    function throttledHandleActivity() {
        if (throttleTimer) return;
        throttleTimer = setTimeout(function () {
            throttleTimer = null;
            handleActivity();
        }, 1000);
    }

    // Pasang event listeners
    activityEvents.forEach(function (event) {
        document.addEventListener(event, throttledHandleActivity, true);
    });

    // Mulai timer pertama kali
    resetTimer();

    // Mulai periodic ping untuk keep-alive session (setiap 10 menit)
    pingTimer = setInterval(pingSession, PING_INTERVAL_MS);

    // Expose untuk debugging
    window.sessionTimeout = {
        reset: resetTimer,
        showWarning: showWarning,
        hideWarning: hideWarning,
        showExpiredState: showExpiredState,
        ping: pingSession
    };
})();
