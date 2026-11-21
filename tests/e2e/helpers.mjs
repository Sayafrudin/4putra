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
