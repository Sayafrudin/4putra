// Helper E2E bersama — file test baru WAJIB import dari sini, jangan salin-tempel.
// Server dev + TiDB remote kadang menggantung request; goto GET idempoten → aman di-retry.
// Kembalikan respons page.goto agar pemanggil bisa cek status (smoke test).
export async function gotoRetry(page, url, opts = {}) {
    try {
        return await page.goto(url, {
            waitUntil: "load",
            timeout: 30000,
            ...opts,
        });
    } catch {
        return await page.goto(url, {
            waitUntil: "load",
            timeout: 30000,
            ...opts,
        });
    }
}

// Akun admin tunggal untuk SEMUA test E2E (perintah bos). Bisa dioverride lewat env.
export const ADMIN = {
    user: process.env.E2E_ADMIN_USER || "admin",
    pass: process.env.E2E_ADMIN_PASS || "password1225",
};

// Login browser via form asli (password readonly-until-focus → isi via evaluate).
// Regex, bukan glob: intended URL bisa /admin/collections dsb. setelah redirect.
// POST /login sesekali gugup saat run beruntun (TiDB dingin) — coba ulang sekali.
export async function loginAdmin(page, BASE) {
    await gotoRetry(page, `${BASE}/login`);
    for (let coba = 1; ; coba++) {
        await page.fill("#username", ADMIN.user);
        await page.evaluate((pass) => {
            const el = document.getElementById("password");
            el.removeAttribute("readonly");
            el.value = pass;
            el.dispatchEvent(new Event("input", { bubbles: true }));
        }, ADMIN.pass);
        await page.click("button[type=submit]");
        try {
            await page.waitForURL(/\/admin(\/|$)/, { timeout: 20000 });
            return;
        } catch (e) {
            if (coba >= 2) throw e;
            // Jeda juga menabuh throttle login (5 percobaan/menit per IP).
            await new Promise((r) => setTimeout(r, 15000));
            await gotoRetry(page, `${BASE}/login`);
        }
    }
}
