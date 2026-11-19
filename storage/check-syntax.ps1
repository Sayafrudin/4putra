Set-Location F:\Github\4putra
$files = @(
    'tests/e2e/admin-spa-flow.mjs',
    'tests/e2e/admin-table-wheel.mjs',
    'tests/e2e/delete-flow.mjs',
    'tests/e2e/kandang-flow.mjs',
    'tests/e2e/keuangan-tab-flow.mjs',
    'tests/e2e/smoke-farm-pages.mjs',
    'tests/e2e/variant-flow.mjs',
    'public/js/session-timeout.js',
    'resources/js/dashboard-charts.js'
)
$gagal = 0
foreach ($f in $files) {
    node --check $f
    if ($LASTEXITCODE -ne 0) {
        Write-Output ("SALAH: " + $f)
        $gagal++
    }
}
if ($gagal -eq 0) { Write-Output "semua sintaks OK" } else { Write-Output ("gagal: " + $gagal) }
