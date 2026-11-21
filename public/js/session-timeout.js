/**
 * session-timeout.js — Sesi ala MyBCA: tanpa tombol Perpanjang.
 * Kembali ke tab admin setelah idle melewati batas → modal "sesi berakhir"
 * + auto-logout server-side → tombol menuju halaman login.
 *
 * Lifetime dari meta[name="session-lifetime-minutes"] (layout admin);
 * batas idle = lifetime − 5 menit (min 1 menit agar env test tetap workable).
 * Ping /admin/ping hanya saat wake dengan idle masih di bawah batas —
 * memvalidasi sesi (401 = kicked/expired) dan memperbarui token CSRF.
 */
(function () {
    "use strict";

    var lifetimeMeta = document.querySelector(
        'meta[name="session-lifetime-minutes"]',
    );
    var TIMEOUT_MINUTES = Math.max(
        1,
        parseInt(
            lifetimeMeta ? lifetimeMeta.getAttribute("content") : "30",
            10,
        ) || 30,
    );
    var IDLE_LIMIT_MINUTES = Math.max(1, TIMEOUT_MINUTES - 5);
    var IDLE_LIMIT_MS = IDLE_LIMIT_MINUTES * 60 * 1000;

    var lastActivity = Date.now();
    var modalEl = null;
    var selesai = false;
    var validasiBerjalan = false;

    function getCsrfToken() {
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        return csrfMeta ? csrfMeta.getAttribute("content") : "";
    }

    function applyCsrf(data) {
        if (!data || !data.csrf) return;
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute("content", data.csrf);
        }
        document
            .querySelectorAll('input[name="_token"]')
            .forEach(function (input) {
                input.value = data.csrf;
            });
    }

    /**
     * Ping endpoint validasi sesi.
     * Resolve {ok,csrf} saat sesi hidup, {expired:true} saat 401/419
     * (bedakan kicked = sesi digantikan login di perangkat lain),
     * {failed:true} untuk respons lain (jaringan/redirect/500).
     */
    function requestPing() {
        return fetch("/admin/ping", {
            method: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
                "X-CSRF-TOKEN": getCsrfToken(),
            },
            credentials: "same-origin",
        })
            .then(function (res) {
                if (res.status === 401 || res.status === 419) {
                    return res
                        .json()
                        .catch(function () {
                            return null;
                        })
                        .then(function (body) {
                            if (body && body.sesi_digantikan) {
                                return { expired: true, kicked: true };
                            }
                            return { expired: true };
                        });
                }
                var ct = res.headers.get("content-type") || "";
                if (res.ok && ct.indexOf("application/json") !== -1) {
                    return res.json().then(function (data) {
                        return data && data.ok ? data : { failed: true };
                    });
                }
                return { failed: true };
            })
            .catch(function () {
                return { failed: true };
            });
    }

    // ===== Interceptor fetch: AJAX admin yang kena 401/419 = sesi mati =====
    // Tanpa popup Perpanjang, tab yang TIDAK PERNAH tersembunyi tak pernah
    // kena validasi wake — satu-satunya tanda sesi mati baginya adalah request
    // gagal. Alih-alih toast "Gagal", lempar langsung ke alur sesiBerakhir
    // (modal + auto-logout), persis perilaku myBCA.
    var fetchAsli = window.fetch;
    window.fetch = function (input) {
        var url =
            typeof input === "string"
                ? input
                : input && input.url
                  ? String(input.url)
                  : "";
        var lewati =
            selesai ||
            url.indexOf("/admin/ping") !== -1 || // ping menangani dirinya sendiri
            url.indexOf("/logout") !== -1; // hindari loop auto-logout
        var respons = fetchAsli.apply(this, arguments);
        if (lewati) return respons;
        return respons.then(function (res) {
            if (res.status !== 401 && res.status !== 419) return res;
            // clone(): pemanggil asli tetap bisa membaca body responsnya
            res.clone()
                .json()
                .catch(function () {
                    return null;
                })
                .then(function (body) {
                    sesiBerakhir(!!(body && body.sesi_digantikan));
                });
            return res;
        });
    };

    function buatModal(kicked) {
        // Turbo menukar body saat navigasi: modal menempel di body lama bisa
        // lepas dari DOM — bangun ulang, jangan pakai referensi basi.
        if (modalEl && modalEl.isConnected) return modalEl;
        modalEl = null;

        modalEl = document.createElement("div");
        modalEl.id = "session-timeout-modal";
        modalEl.className =
            "fixed inset-0 z-[10001] hidden items-center justify-center bg-black/60 opacity-0 transition-opacity duration-300";
        modalEl.innerHTML =
            '<div class="bg-[#1e2530] rounded-xl p-6 max-w-sm w-full mx-4 shadow-2xl border border-gray-700 transform scale-95 opacity-0 transition-all duration-300 ease-out">' +
            '<div class="flex items-center gap-3 mb-4">' +
            '<div class="w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center shrink-0">' +
            '<svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">' +
            '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>' +
            "</svg>" +
            "</div>" +
            '<h3 class="text-lg font-bold text-white">' +
            (kicked
                ? "Sesi Anda Telah Digantikan"
                : "Sesi Anda Telah Berakhir") +
            "</h3>" +
            "</div>" +
            '<p class="text-sm text-gray-400 mb-5">' +
            (kicked
                ? "Akun Anda telah login di perangkat lain. Sesi ini diakhiri otomatis."
                : "Sesi Anda telah berakhir karena tidak ada aktivitas lebih dari " +
                  IDLE_LIMIT_MINUTES +
                  " menit. Silakan login kembali untuk melanjutkan.") +
            "</p>" +
            '<div class="flex justify-end">' +
            '<button id="session-login-btn" class="px-4 py-2.5 text-sm font-semibold text-white bg-[#E62C37] hover:bg-[#c5242d] rounded-lg transition-colors">Login Kembali</button>' +
            "</div>" +
            "</div>";

        document.body.appendChild(modalEl);
        document
            .getElementById("session-login-btn")
            .addEventListener("click", function () {
                window.location.href = "/login";
            });

        return modalEl;
    }

    function tampilkanModal(modal) {
        var dialog = modal.querySelector("div");
        modal.classList.remove("hidden");
        modal.classList.add("flex");

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                modal.classList.remove("opacity-0");
                modal.classList.add("opacity-100");
                dialog.classList.remove("scale-95", "opacity-0");
                dialog.classList.add("scale-100", "opacity-100");
            });
        });
    }

    function sesiBerakhir(kicked) {
        if (selesai) return;
        selesai = true;

        tampilkanModal(buatModal(kicked));

        // Auto-logout server-side: sesi (dan remember bila ada) ikut diakhiri
        // walau user menutup tab tanpa klik tombol. Gagal jaringan = biarkan;
        // sesi mati sendiri di server setelah lifetime.
        fetch("/logout", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": getCsrfToken(),
                Accept: "application/json",
            },
            credentials: "same-origin",
        }).catch(function () {});
    }

    function validateOnWake() {
        if (selesai || validasiBerjalan) return;
        var idleMs = Date.now() - lastActivity;

        // Idle melewati batas → jangan ping (ping memperbarui sesi diam-diam
        // = perilaku Perpanjang yang dihapus). Akhiri sesi langsung.
        if (idleMs > IDLE_LIMIT_MS) {
            sesiBerakhir(false);
            return;
        }

        validasiBerjalan = true;
        requestPing()
            .then(function (data) {
                if (data && data.expired) {
                    sesiBerakhir(data.kicked);
                    return;
                }
                if (data && data.ok) {
                    applyCsrf(data);
                    lastActivity = Date.now(); // kembali ke tab = aktivitas
                }
                // failed (jaringan putus) → abaikan, cek lagi di wake berikutnya
            })
            .finally(function () {
                validasiBerjalan = false;
            });
    }

    // Aktivitas user menunda batas idle (throttle 1 detik).
    var throttleTimer = null;
    [
        "mousedown",
        "mousemove",
        "keypress",
        "scroll",
        "touchstart",
        "click",
    ].forEach(function (event) {
        document.addEventListener(
            event,
            function () {
                if (throttleTimer) return;
                throttleTimer = setTimeout(function () {
                    throttleTimer = null;
                }, 1000);
                lastActivity = Date.now();
            },
            true,
        );
    });

    // Wake = kembali ke tab admin (pindah tab / alt-tab / kembali dari minimize).
    document.addEventListener("visibilitychange", function () {
        if (document.visibilityState === "visible") validateOnWake();
    });
    window.addEventListener("focus", validateOnWake);

    // Expose untuk E2E/debug: pemicu wake deterministik (headless tak punya tab).
    window.sessionTimeout = { wake: validateOnWake };
})();
