<#
.SYNOPSIS
    Starts the MariaDB test container, runs the full test suite, then stops the container.

.USAGE
    # From the project root:
    .\scripts\run-tests.ps1

    # Run only a specific test file:
    .\scripts\run-tests.ps1 -Filter BookingFlowTest

    # Keep the container running after tests (useful for debugging):
    .\scripts\run-tests.ps1 -KeepAlive
#>

param(
    [string] $Filter    = '',
    [switch] $KeepAlive = $false
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

Set-Location $root

# ── 1. Start the test container ───────────────────────────────────────────────
Write-Host "`n[1/4] Starting MariaDB test container..." -ForegroundColor Cyan
docker compose -f docker-compose.test.yml up -d

# ── 2. Wait for MariaDB to be healthy ────────────────────────────────────────
Write-Host "[2/4] Waiting for MariaDB to be ready..." -ForegroundColor Cyan
$maxWait  = 60   # seconds
$interval = 3
$elapsed  = 0

while ($elapsed -lt $maxWait) {
    $status = docker inspect --format='{{.State.Health.Status}}' zahira-test-mariadb 2>$null
    if ($status -eq 'healthy') { break }
    Write-Host "  Still waiting... ($elapsed s)" -ForegroundColor DarkGray
    Start-Sleep -Seconds $interval
    $elapsed += $interval
}

if ($elapsed -ge $maxWait) {
    Write-Host "ERROR: MariaDB did not become healthy within $maxWait seconds." -ForegroundColor Red
    docker compose -f docker-compose.test.yml logs
    docker compose -f docker-compose.test.yml down
    exit 1
}

Write-Host "  MariaDB is healthy." -ForegroundColor Green

# ── 3. Prepare the test database ─────────────────────────────────────────────
Write-Host "[3/4] Running migrations on test database..." -ForegroundColor Cyan
php artisan migrate --force --env=testing
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Migrations failed." -ForegroundColor Red
    if (-not $KeepAlive) { docker compose -f docker-compose.test.yml down }
    exit 1
}

# ── 4. Run the test suite ─────────────────────────────────────────────────────
Write-Host "[4/4] Running tests..." -ForegroundColor Cyan

$phpunitArgs = @('vendor/bin/phpunit', '--colors=always', '--testdox')

if ($Filter) {
    $phpunitArgs += "--filter=$Filter"
}

php @phpunitArgs
$exitCode = $LASTEXITCODE

# ── Summary ───────────────────────────────────────────────────────────────────
if ($exitCode -eq 0) {
    Write-Host "`nAll tests passed." -ForegroundColor Green
} else {
    Write-Host "`nSome tests failed (exit code $exitCode)." -ForegroundColor Red
}

# ── Cleanup ───────────────────────────────────────────────────────────────────
if (-not $KeepAlive) {
    Write-Host "`nStopping test container..." -ForegroundColor Cyan
    docker compose -f docker-compose.test.yml down
} else {
    Write-Host "`nContainer left running (--KeepAlive). Stop it with:" -ForegroundColor Yellow
    Write-Host "  docker compose -f docker-compose.test.yml down" -ForegroundColor Yellow
}

exit $exitCode
