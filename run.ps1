param(
    [string]$Command = "dev"
)

$ErrorActionPreference = "Stop"

Write-Host "`n  ╔═══════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "  ║     🍞 Erles Bakery ERP Launcher      ║" -ForegroundColor Cyan
Write-Host "  ╚═══════════════════════════════════════╝`n" -ForegroundColor Cyan

function Setup-EnvFiles {
    Write-Host "Checking .env files..." -ForegroundColor Yellow
    $dirs = @(".", "backend", "admin", "public")
    foreach ($dir in $dirs) {
        $example = Join-Path $dir ".env.example"
        $target = Join-Path $dir ".env"
        if ((Test-Path $example) -and (-not (Test-Path $target))) {
            Copy-Item $example $target
            Write-Host "✓ Created $target from $example" -ForegroundColor Green
        }
    }
}

switch ($Command.ToLower()) {
    "dev" {
        Setup-EnvFiles

        Write-Host "[1/5] Checking Docker daemon..." -ForegroundColor Yellow
        try {
            $null = docker info 2>&1
            Write-Host "✓ Docker daemon is running" -ForegroundColor Green
        } catch {
            Write-Host "✗ Docker daemon belum berjalan. Silakan buka aplikasi Docker Desktop di Windows terlebih dahulu." -ForegroundColor Red
            exit 1
        }

        Write-Host "[2/5] Updating git submodules..." -ForegroundColor Yellow
        git submodule update --init --recursive
        Write-Host "✓ Submodules updated" -ForegroundColor Green

        Write-Host "[3/5] Building and starting containers..." -ForegroundColor Yellow
        docker compose up --build -d
        Write-Host "✓ Containers started" -ForegroundColor Green

        Write-Host "[4/5] Waiting for database to be ready..." -ForegroundColor Yellow
        $retries = 30
        $ready = $false
        while ($retries -gt 0 -and -not $ready) {
            $check = docker compose exec -T db pg_isready -U postgres -d erles_bakery 2>&1
            if ($LASTEXITCODE -eq 0) {
                $ready = $true
            } else {
                Write-Host "  Waiting for PostgreSQL... ($retries retries left)"
                $retries--
                Start-Sleep -Seconds 2
            }
        }

        if (-not $ready) {
            Write-Host "✗ Database did not become ready in time." -ForegroundColor Red
            exit 1
        }
        Write-Host "✓ Database is ready" -ForegroundColor Green

        Write-Host "[5/5] Running Laravel setup, migrations & seeders..." -ForegroundColor Yellow
        docker compose exec -T backend php artisan migrate --force
        docker compose exec -T backend php artisan db:seed --force
        Write-Host "✓ Migrations and seeders completed" -ForegroundColor Green

        Write-Host "`n═══════════════════════════════════════════" -ForegroundColor Cyan
        Write-Host "  ✓ Erles Bakery ERP is up and running!" -ForegroundColor Green
        Write-Host "═══════════════════════════════════════════`n" -ForegroundColor Cyan
        Write-Host "  Backend API:   http://localhost:8000/api" -ForegroundColor White
        Write-Host "  Health Check:  http://localhost:8000/api/health" -ForegroundColor White
        Write-Host "  Admin Panel:   http://localhost:5174" -ForegroundColor White
        Write-Host "  Public Site:   http://localhost:5173" -ForegroundColor White
        Write-Host "  PostgreSQL:    localhost:5432 (internal)`n" -ForegroundColor White
    }
    "stop" {
        Write-Host "Stopping all services..." -ForegroundColor Yellow
        docker compose down
        Write-Host "✓ Services stopped" -ForegroundColor Green
    }
    "restart" {
        docker compose down
        & $PSCommandPath dev
    }
    "logs" {
        docker compose logs -f
    }
    "fresh" {
        Write-Host "Refreshing database..." -ForegroundColor Yellow
        docker compose exec -T backend php artisan migrate:fresh --seed --force
        Write-Host "✓ Database refreshed with seeds" -ForegroundColor Green
    }
    default {
        Write-Host "Usage: .\run.ps1 [dev|stop|restart|logs|fresh]" -ForegroundColor Yellow
    }
}

