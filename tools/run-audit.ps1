<#
.SYNOPSIS
    Evidence-gathering audit of the SARCNA 2027 site, for Windows.

.DESCRIPTION
    A generic audit script — one that runs npm ci, pip install and docker build
    — produces nothing useful here, because this project has none of those
    things. It is plain PHP 8.1+ and MySQL with no build step, no Composer and
    no npm. Such a script reports "no package.json found" and stops, which
    says nothing about whether the site works.

    This one audits what is actually here. It writes everything to a
    timestamped log beside the repository and never guesses: each section
    either produces evidence or records, in the log, exactly why it could not.

.PARAMETER RepoPath
    The repository. Defaults to the folder this script lives in, i.e. running
    it from tools\ inside a clone just works.

.PARAMETER BaseUrl
    Where to reach the site under test. Default http://127.0.0.1:8000

.PARAMETER AdminPassword
    Administrator password, for the audit's admin and write checks. Without it
    the audit still runs but reports about 19 failures for the sections it
    could not perform — it refuses to skip silently. Supply it for a real
    verdict.

.PARAMETER SkipInstall
    Do not attempt an install. Use when pointing at a site that already has a
    database, so the audit runs against it as-is.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\tools\run-audit.ps1

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\tools\run-audit.ps1 -AdminPassword 'YourAdminPassword'
#>

param(
    [string]$RepoPath      = (Split-Path -Parent $PSScriptRoot),
    [string]$BaseUrl       = 'http://127.0.0.1:8000',
    [string]$AdminPassword = '',
    [switch]$SkipInstall
)

$ErrorActionPreference = 'Continue'

$Stamp = (Get-Date).ToUniversalTime().ToString('yyyyMMddTHHmmssZ')
$Log   = Join-Path (Split-Path -Parent $RepoPath) "sarcna-audit-$Stamp.log"

function Section {
    param([string]$Title)
    Write-Host ''
    Write-Host "========== $Title =========="
}

function Have {
    param([string]$Name)
    return [bool](Get-Command $Name -ErrorAction SilentlyContinue)
}

function Run {
    param([string]$Label, [scriptblock]$Command)

    Section "RUN: $Label"
    $global:LASTEXITCODE = 0
    $sw = [Diagnostics.Stopwatch]::StartNew()

    try {
        & $Command
        $code = $global:LASTEXITCODE
        if ($null -eq $code) { $code = 0 }
        Write-Host "EXIT_CODE=$code"
    } catch {
        Write-Host "EXCEPTION=$($_.Exception.Message)"
        Write-Host 'EXIT_CODE=1'
    } finally {
        $sw.Stop()
        Write-Host "ELAPSED_SECONDS=$([math]::Round($sw.Elapsed.TotalSeconds, 2))"
    }
}

Start-Transcript -Path $Log -Force

try {
    Section 'AUDIT START'
    Write-Host "UTC=$((Get-Date).ToUniversalTime().ToString('o'))"
    Write-Host "RepoPath=$RepoPath"
    Write-Host "BaseUrl=$BaseUrl"
    Write-Host "Log=$Log"
    Write-Host "AdminPasswordSupplied=$([bool]$AdminPassword)"

    if (-not (Test-Path (Join-Path $RepoPath 'app\bootstrap.php'))) {
        Write-Host "FATAL: $RepoPath does not look like the SARCNA repository (no app\bootstrap.php)."
        exit 1
    }

    Set-Location $RepoPath

    # ---------------------------------------------------------------- system

    Section 'SYSTEM'
    Write-Host "PWD=$(Get-Location)"
    Write-Host "OS=$([System.Environment]::OSVersion.VersionString)"
    Write-Host "PowerShell=$($PSVersionTable.PSVersion)"

    foreach ($cmd in @('php', 'mysql', 'git')) {
        if (Have $cmd) { Run "$cmd --version" { & $cmd --version } }
        else           { Write-Host "MISSING_COMMAND=$cmd" }
    }

    if (-not (Have 'php')) {
        Write-Host ''
        Write-Host 'FATAL: PHP is not on PATH. Everything below needs it.'
        Write-Host 'Install PHP 8.1 or newer (https://windows.php.net/download) and re-run.'
        exit 1
    }

    Run 'php -m (loaded extensions)' { php -m }

    Section 'REQUIRED PHP EXTENSIONS'
    foreach ($ext in @('pdo_mysql', 'gd', 'mbstring', 'curl', 'json')) {
        $present = (php -r "echo extension_loaded('$ext') ? 'yes' : 'no';")
        Write-Host ("EXTENSION {0,-10} {1}" -f $ext, $present)
    }

    # -------------------------------------------- what this project is NOT

    # A generic auditor looks for these and, finding none, concludes nothing.
    # Recording their absence explicitly turns that silence into evidence.
    Section 'BUILD TOOLING PRESENT? (expected: none)'
    foreach ($f in @(
        'package.json', 'package-lock.json', 'pnpm-lock.yaml', 'yarn.lock',
        'Dockerfile', 'docker-compose.yml', 'compose.yaml',
        'requirements.txt', 'pyproject.toml', 'setup.py',
        'composer.json', 'tsconfig.json',
        'vercel.json', 'netlify.toml', 'render.yaml', 'railway.json'
    )) {
        Write-Host ("{0,-24} {1}" -f $f, $(if (Test-Path $f) { 'PRESENT' } else { 'absent' }))
    }
    Write-Host ''
    Write-Host 'All absent is the CORRECT result. No build step, no dependency'
    Write-Host 'manager, no container. Upload the files and open /install.'

    # ------------------------------------------------------------- git state

    if (Have 'git') {
        Section 'GIT STATE'
        Run 'git remote -v'                  { git remote -v }
        Run 'git status --porcelain=v1 -b'   { git status --porcelain=v1 -b }
        Run 'git rev-parse HEAD'             { git rev-parse HEAD }
        Run 'git branch -vv'                 { git branch -vv }
        Run 'git log -20 --oneline --decorate' { git log -20 --oneline --decorate }

        Section 'TRACKED FILE MANIFEST'
        $manifest = Join-Path (Split-Path -Parent $RepoPath) 'repo-tracked-manifest.csv'
        $listing  = Join-Path (Split-Path -Parent $RepoPath) 'repo-tracked-files.txt'

        $files = git ls-files
        $files | Sort-Object | Set-Content $listing -Encoding UTF8

        $rows = foreach ($f in $files) {
            if (Test-Path -LiteralPath $f -PathType Leaf) {
                [PSCustomObject]@{
                    Path   = $f
                    Size   = (Get-Item -LiteralPath $f).Length
                    SHA256 = (Get-FileHash -LiteralPath $f -Algorithm SHA256).Hash
                }
            }
        }

        $rows | Sort-Object Path | Export-Csv $manifest -NoTypeInformation -Encoding UTF8
        Write-Host "TRACKED_FILE_COUNT=$($rows.Count)"
        Write-Host "WROTE=$manifest"
        Write-Host "WROTE=$listing"
    }

    # ----------------------------------------------------------------- lint

    Section 'SYNTAX CHECK — every PHP file'
    $bad = 0
    $checked = 0

    Get-ChildItem -Path 'app', 'public_html', 'tools' -Recurse -Filter '*.php' -File |
        ForEach-Object {
            $checked++
            $out = php -l $_.FullName 2>&1
            if ($LASTEXITCODE -ne 0) {
                $bad++
                Write-Host "SYNTAX_ERROR $($_.FullName)"
                Write-Host $out
            }
        }

    Write-Host "FILES_CHECKED=$checked"
    Write-Host "SYNTAX_ERRORS=$bad"

    # ------------------------------------------------------------------ env

    Section 'CONFIGURATION'
    Write-Host ".env present:         $(Test-Path '.env')"
    Write-Host ".env.example present: $(Test-Path '.env.example')"
    Write-Host "installed.lock:       $(Test-Path 'app\Config\installed.lock')"

    if (Test-Path '.env.example') {
        Write-Host ''
        Write-Host 'Environment variable NAMES required (values never printed):'
        Get-Content '.env.example' |
            Where-Object { $_ -match '^[A-Z_]+=' } |
            ForEach-Object { ($_ -split '=')[0] } |
            Sort-Object -Unique |
            ForEach-Object { Write-Host "  $_" }
    }

    if (-not (Test-Path '.env')) {
        Write-Host ''
        Write-Host 'NOTE: no .env, so nothing below can reach a database.'
        Write-Host 'Copy .env.example to .env and set APP_URL and the six DB_* keys.'
    }

    # ------------------------------------------------------------- database

    Section 'DATABASE CONNECTION'
    $dbOk = $false

    if (Test-Path '.env') {
        # A dedicated script, not inline PHP: quoting PHP inside a shell string
        # differs between platforms and fails silently when it goes wrong.
        php tools\db-check.php
        $dbOk = ($LASTEXITCODE -eq 0)
        Write-Host "DB_REACHABLE=$dbOk"
    } else {
        Write-Host 'SKIPPED: no .env'
    }

    # ----------------------------------------------------------- the suite

    if (-not $dbOk) {
        Section 'TEST SUITE'
        Write-Host 'SKIPPED: no database connection. This is a configuration'
        Write-Host 'result, not a code result — it says nothing about whether'
        Write-Host 'the site works. Fix .env and re-run to get a real verdict.'
    } else {
        Section 'SERVER'
        $server = Start-Process -FilePath 'php' `
            -ArgumentList @('-S', '127.0.0.1:8000', '-t', 'public_html', 'tools\dev-router.php') `
            -PassThru -WindowStyle Hidden

        Write-Host "STARTED_PID=$($server.Id)"

        $up = $false
        foreach ($i in 1..30) {
            Start-Sleep -Seconds 1
            try {
                $code = (Invoke-WebRequest -Uri "$BaseUrl/install" -UseBasicParsing -TimeoutSec 5).StatusCode
                if ($code -eq 200) { $up = $true; break }
            } catch {
                if ($_.Exception.Response.StatusCode.value__ -in @(200, 410)) { $up = $true; break }
            }
        }

        Write-Host "SERVER_UP=$up"

        try {
            if ($up -and -not $SkipInstall -and -not (Test-Path 'app\Config\installed.lock')) {
                Run 'install through the real /install form' {
                    php tools\ci-install.php $BaseUrl
                }
            }

            if ($up) {
                Run 'smoke test'  { php tools\smoke-test.php }
                Run 'race test'   { php tools\race-test.php $BaseUrl }

                if ($AdminPassword) {
                    Run 'full audit, first run'  { php tools\audit.php $BaseUrl "--password=$AdminPassword" }
                    Run 'full audit, second run' { php tools\audit.php $BaseUrl "--password=$AdminPassword" }
                    Write-Host ''
                    Write-Host 'The two totals above MUST match. A total that climbs'
                    Write-Host 'means the audit left records behind — which once put'
                    Write-Host 'fictional revenue in the finance screens.'
                } else {
                    Run 'full audit (no admin password)' { php tools\audit.php $BaseUrl }
                    Write-Host ''
                    Write-Host 'NOTE: without -AdminPassword the audit reports roughly 19'
                    Write-Host 'FAILURES for the admin and write sections. That is the audit'
                    Write-Host 'refusing to skip checks it could not perform, NOT a defect in'
                    Write-Host 'the site. Re-run with -AdminPassword for a real verdict.'
                }
            }
        } finally {
            if ($server -and -not $server.HasExited) {
                Stop-Process -Id $server.Id -Force -ErrorAction SilentlyContinue
                Write-Host "STOPPED_PID=$($server.Id)"
            }
        }
    }

    # ---------------------------------------------------------- deployment

    Section 'DEPLOYMENT FILES'
    foreach ($f in @(
        'public_html\index.php',
        'public_html\.htaccess',
        'public_html\preflight.php',
        '.htaccess',
        'app\.htaccess',
        'tools\.htaccess',
        'database\schema.sql',
        'database\seed.sql',
        'database\demo-data.sql',
        'docs\DEPLOYMENT-HANDOFF.md',
        'docs\cpanel-deployment-guide.md',
        'HANDOVER.md',
        '.github\workflows\tests.yml'
    )) {
        Write-Host ("{0,-38} {1}" -f $f, $(if (Test-Path $f) { 'present' } else { 'MISSING' }))
    }

    Section 'FOLDER LAYOUT'
    Write-Host 'The application root must be the PARENT of the web root.'
    Write-Host 'Flattening it returns HTTP 500 with nothing in any log.'
    Write-Host ''
    Write-Host "app\ beside index.php (WRONG if true): $(Test-Path 'public_html\app\bootstrap.php')"
    Write-Host "app\ above public_html (correct):      $(Test-Path 'app\bootstrap.php')"
}
finally {
    Section 'AUDIT END'
    Write-Host "Audit log written to: $Log"
    Stop-Transcript
}
