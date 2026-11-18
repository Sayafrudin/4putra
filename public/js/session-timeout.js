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
    // Popup peringatan muncul 5 menit sebelum sesi server kedaluwarsa
    // (dalam, minimal 1 menit, agar lifetime pendek di env test tetap workable)
    var WARNING_MINUTES = Math.max(1, Math.min(5, TIMEOUT_MINUTES - 1));
    var TIMEOUT_MS = TIMEOUT_MINUTES * 60 * 1000;
    var WARNING_MS = WARNING_MINUTES * 60 * 1000;
    var PING_INTERVAL_MS = 10 * 60 * 1000; // Ping setiap 10 menit

    var warningTimer = null;
    var logoutTimer = null;
    var pingTimer = null;
    var warningShown = false;
    var modalEl = null;
    var lastActivity = Date.now();

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
     * Resolve {ok,csrf} saat sukses (sesi diperbarui server),
     * resolve {expired:true} saat 401/419 (sesi telah mati),
     * resolve {failed:true} untuk respons lain (redirect 308, 500, HTML, jaringan).
     */
    function requestPing() {
        return fetch('/admin/ping', {
            method: 'GET', // GET = bebas 419 CSRF palsu; endpoint juga menerima POST
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
        }).then(function (res) {
            if (res.status === 401 || res.status === 419) {
                return { expired: true };
            }
            var ct = res.headers.get('content-type') || '';
            if (res.ok && ct.indexOf('application/json') !== -1) {
                return res.json().then(function (data) {
                    return data && data.ok ? data : { failed: true };
                });
            }
            // 308 redirect (AdminDomain), 500, HTML, dll = BUKAN sukses
            return { failed: true };
        }).catch(function () {
            return { failed: true }; // jaringan putus — tidak lagi ditelan diam-diam
        });
    }

    function pingSession() {
        // Ping = memperbarui sesi server. Tab tak terlihat (browser mem-bekukan
        // timer & user tidak melihat popup) atau pengguna sudah lama tidak
        // beraktivitas TIDAK boleh merahasiakan sesi tetap hidup.
        if (document.visibilityState === 'hidden') return;
        if (Date.now() - lastActivity > TIMEOUT_MS - WARNING_MS) return;
        requestPing().then(function (data) {
            if (data && !data.expired) {
                applyCsrf(data);
            }
        }).catch(function () {});
    }

    function createModal() {
        // Turbo menukar body saat navigasi sidebar: modal yang menempel di body lama
        // lepas dari DOM — bangun ulang, jangan pakai referensi basi (popup tak terlihat)
        if (modalEl && modalEl.isConnected) return modalEl;
        modalEl = null;

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
                '<p id="session-modal-status" class="hidden text-xs text-red-400 mb-2"></p>' +
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
            titleEl.textContent = 'Sesi Anda Telah Habis';
        }
        if (paragraphs.length > 0) {
            paragraphs[0].textContent = 'Anda telah ter-logout karena sesi berakhir. Silakan login kembali untuk melanjutkan.';
        }
        if (paragraphs.length > 1) {
            paragraphs[1].textContent = 'Data pada form tidak terkirim — salin teks penting terlebih dahulu bila diperlukan. Setelah login ulang, Anda dikembalikan otomatis ke halaman ini.';
        }

        if (extendBtn) {
            var reloadBtn = extendBtn.cloneNode(true); // clone tanpa listener lama
            reloadBtn.textContent = 'Muat Ulang Halaman';
            extendBtn.parentNode.replaceChild(reloadBtn, extendBtn);
            reloadBtn.addEventListener('click', function () {
                // Reload URL sekarang: dengan remember → kembali langsung tanpa
                // login; tanpa remember → login singkat lalu balik ke halaman ini
                // (URL intended dipertahankan server).
                window.location.reload();
            });
        }

        if (logoutBtn) {
            logoutBtn.style.display = 'none';
        }
    }

    function hideWarning() {
        warningShown = false;
        setStatus('');

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

    function setStatus(msg) {
        var el = document.getElementById('session-modal-status');
        if (el) {
            el.textContent = msg || '';
            el.classList.toggle('hidden', !msg);
        }
    }

    function setExtending(busy) {
        var btn = document.getElementById('session-extend-btn');
        if (!btn) return;
        btn.disabled = busy;
        if (busy) {
            btn.dataset.label = btn.textContent;
            btn.textContent = 'Memperbarui...';
        } else if (btn.dataset.label) {
            btn.textContent = btn.dataset.label;
        }
    }

    function extendSession(e) {
        if (e && typeof e.preventDefault === 'function') {
            e.preventDefault();
        }
        if (e && typeof e.stopPropagation === 'function') {
            e.stopPropagation();
        }

        setExtending(true);
        setStatus('');
        requestPing().then(function (data) {
            setExtending(false);
            if (data && data.expired) {
                // Sesi benar-benar habis di server → arahkan ke login
                showExpiredState();
                return;
            }
            if (data && data.failed) {
                // Jangan tutup modal pada kegagalan — beri kesempatan retry
                setStatus('Gagal memperbarui sesi. Periksa koneksi lalu klik Perpanjang Sesi lagi.');
                return;
            }
            applyCsrf(data);
            // Sukses: tutup popup + reset timer lokal ke 0, tanpa reload halaman.
            hideWarning();
            resetTimer();
            // Konfirmasi eksplisit bahwa sesi benar-benar diperpanjang di server.
            if (window.showToast) {
                window.showToast('success', 'Sesi Diperpanjang', 'Sesi diperpanjang \u00b1' + TIMEOUT_MINUTES + ' menit.');
            }
        });
    }

    function logoutNow() {
        hideWarning();

        // Sesi bisa saja SUDAH mati di server (idle melewati lifetime) — POST
        // form biasa akan 419 dan user mendarat di halaman error. Logout via
        // fetch lalu arahkan ke /login apa pun hasilnya.
        fetch('/logout', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        }).catch(function () {}).finally(function () {
            window.location.href = '/login';
        });
    }

    function resetTimer() {
        if (warningTimer) clearTimeout(warningTimer);

        // Clamp minimal 1 menit: jika lifetime < window warning, popup jangan
        // muncul instan begitu halaman dibuka.
        var warningDelay = Math.max(60 * 1000, TIMEOUT_MS - WARNING_MS);

        warningTimer = setTimeout(function () {
            showWarning();
        }, warningDelay);
    }

    // Event listeners untuk deteksi aktivitas
    var activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];

    function handleActivity() {
        lastActivity = Date.now();
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

    // Validasi ulang saat tab kembali aktif (bangun dari sleep / pindah tab).
    // PENTING: kalau pengguna sudah lama tidak beraktivitas (melewati batas
    // popup), JANGAN ping — ping akan memperbarui sesi diam-diam tanpa izin
    // (perilaku lama yang membuat sesi tak pernah mati). Tunjukkan popup;
    // sesi benar-benar mati baru terasa saat tombol Perpanjang diklik.
    function validateOnWake() {
        var idleMs = Date.now() - lastActivity;
        if (idleMs >= TIMEOUT_MS - WARNING_MS) {
            showWarning(); // popup jujur, tanpa refresh di belakang
            return;
        }
        requestPing().then(function (data) {
            if (data && data.expired) {
                showExpiredState(); // jujur sejak awal, sebelum user klik apa pun
                return;
            }
            if (data && data.ok) {
                applyCsrf(data);
                if (warningShown) {
                    hideWarning(); // sesi ternyata masih hidup → tutup warning basi
                }
                resetTimer();
            }
            // failed (jaringan/redirect) → abaikan, timer lokal tetap berjalan
        });
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            validateOnWake();
        }
    });

    // Popup bisa tertelan body-swap Turbo (navigasi sidebar) — tutup rapi + reset
    // timer idle: navigasi = tindakan nyata user; popup akan muncul lagi bila idle lagi.
    document.addEventListener('turbo:before-render', function () {
        if (!warningShown) return;
        hideWarning(); // hentikan countdown auto-logout tersembunyi + tutup popup (walau node lepas)
        resetTimer();
    });

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
