# Build WordPress Plugin Script
# Auto-detects plugin information from WordPress plugin headers
# Creates Linux-compatible ZIP packages using bsdtar
# Version: 2.0.0

$ErrorActionPreference = "Stop"

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "WordPress Plugin Build" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Configuration
$DistDir = "dist-package"

# Step 0: Auto-detect plugin information
Write-Host "[0/6] Detecting plugin information..." -ForegroundColor Yellow

# Find the main plugin file (contains Plugin Name header)
$PluginFiles = Get-ChildItem -Path . -Filter "*.php" -File | Where-Object {
    $content = Get-Content $_.FullName -Raw
    $content -match "\*\s*Plugin Name:"
}

if ($PluginFiles.Count -eq 0) {
    Write-Host "  [FAIL] No WordPress plugin file found" -ForegroundColor Red
    Write-Host "  Expected a PHP file with 'Plugin Name:' header" -ForegroundColor Yellow
    exit 1
}

# Use the first match (or the one without 'uninstall' in name)
$MainPluginFile = $PluginFiles | Where-Object { $_.Name -notmatch "uninstall" } | Select-Object -First 1
if (-not $MainPluginFile) {
    $MainPluginFile = $PluginFiles | Select-Object -First 1
}

Write-Host "  [OK] Found plugin file: $($MainPluginFile.Name)" -ForegroundColor Green

# Parse plugin headers
$PluginContent = Get-Content $MainPluginFile.FullName -Raw

# Extract Plugin Name
if ($PluginContent -match "\*\s*Plugin Name:\s*(.+)") {
    $PluginName = $Matches[1].Trim()
} else {
    Write-Host "  [FAIL] Could not extract Plugin Name from headers" -ForegroundColor Red
    exit 1
}

# Extract Version
if ($PluginContent -match "\*\s*Version:\s*([\d\.]+)") {
    $Version = $Matches[1].Trim()
} else {
    Write-Host "  [WARN] Could not extract Version, using 1.0.0" -ForegroundColor Yellow
    $Version = "1.0.0"
}

# Extract Text Domain (use for filename) or derive from plugin file name
if ($PluginContent -match "\*\s*Text Domain:\s*(\S+)") {
    $Slug = $Matches[1].Trim()
} else {
    $Slug = [System.IO.Path]::GetFileNameWithoutExtension($MainPluginFile.Name)
}

Write-Host "  [OK] Plugin: $PluginName" -ForegroundColor Green
Write-Host "  [OK] Version: $Version" -ForegroundColor Green
Write-Host "  [OK] Slug: $Slug" -ForegroundColor Green
Write-Host ""

$ZipName = "$Slug-$Version.zip"

# Step 1: Clean build environment
Write-Host "[1/6] Cleaning build environment..." -ForegroundColor Yellow
if (Test-Path $DistDir) {
    Remove-Item -Path $DistDir -Recurse -Force
    Write-Host "  [OK] Cleaned $DistDir/" -ForegroundColor Green
}
New-Item -Path $DistDir -ItemType Directory -Force | Out-Null
Write-Host "  [OK] Created $DistDir/" -ForegroundColor Green

# Step 2: Build frontend assets
Write-Host ""
Write-Host "[2/6] Building frontend assets..." -ForegroundColor Yellow
if (Test-Path "package.json") {
    npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  [FAIL] Frontend build FAILED" -ForegroundColor Red
        exit 1
    }
    Write-Host "  [OK] Frontend build successful" -ForegroundColor Green
} else {
    Write-Host "  [SKIP] No package.json - skipping frontend build" -ForegroundColor Yellow
}

# Step 3: Install production PHP dependencies
Write-Host ""
Write-Host "[3/6] Installing production PHP dependencies..." -ForegroundColor Yellow
if (Test-Path "composer.json") {
    composer install --no-dev --optimize-autoloader --no-interaction
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  [FAIL] Composer install FAILED" -ForegroundColor Red
        exit 1
    }
    Write-Host "  [OK] Composer dependencies installed" -ForegroundColor Green
} else {
    Write-Host "  [SKIP] No composer.json - skipping PHP dependencies" -ForegroundColor Yellow
}

# Step 4: Create ZIP archive
Write-Host ""
Write-Host "[4/6] Creating ZIP archive..." -ForegroundColor Yellow

# Directories to exclude from the package
$ExcludeDirs = @(
    ".git", ".github", ".vscode", ".idea",
    "node_modules", "tests", "test", "Test", "Tests",
    $DistDir, "frontend", "src-frontend", "assets-src",
    "bin", "scripts", "docs"
)

# File name patterns to exclude (supports wildcards)
$ExcludeFilePatterns = @(
    ".gitignore", ".gitattributes", ".npmrc", ".editorconfig", ".distignore",
    "package.json", "package-lock.json",
    "composer.json", "composer.lock",
    "webpack.config.js", "webpack.config.*.js",
    "tsconfig.json", "tsconfig.*.json", "tsconfig.tsbuildinfo", "jsconfig.json",
    "phpcs.xml", "phpcs.xml.dist", "phpunit.xml", "phpunit.xml.dist",
    "phpstan.neon", "phpstan.neon.dist",
    "tailwind.config.js", "postcss.config.js",
    ".eslintrc*", ".prettierrc*", ".env*",
    "build-plugin.ps1", "build-plugin-new.ps1", "build.ps1",
    "*.map", "*.md", "LICENSE", "CHANGELOG.md"
)

# Create staging directory: dist-package/sikada-auth/
# WordPress requires the ZIP to contain the plugin in a named subfolder
$StagingDir = "$DistDir/$Slug"
if (Test-Path $StagingDir) { Remove-Item -Path $StagingDir -Recurse -Force }
New-Item -Path $StagingDir -ItemType Directory -Force | Out-Null

# Copy plugin files to staging dir, skipping excluded dirs and files
Get-ChildItem -Path "." | Where-Object {
    $name = $_.Name
    if ($_.PSIsContainer -and ($ExcludeDirs -contains $name)) { return $false }
    foreach ($pattern in $ExcludeFilePatterns) {
        if ($name -like $pattern) { return $false }
    }
    return $true
} | ForEach-Object {
    Copy-Item -Path $_.FullName -Destination $StagingDir -Recurse -Force
}

# Create ZIP using .NET ZipFile API with explicit forward-slash paths.
# Compress-Archive stores backslashes in ZIP entry names, which breaks PHP's
# ZipArchive on Linux (it treats the whole backslash path as a filename).
# ZipFile.Open + ZipFileExtensions.CreateEntryFromFile lets us control entry names.
Add-Type -AssemblyName "System.IO.Compression"
Add-Type -AssemblyName "System.IO.Compression.FileSystem"

$ZipPath = (Resolve-Path $DistDir).Path + "\$ZipName"
if (Test-Path $ZipPath) { Remove-Item -Path $ZipPath -Force }

$StagingAbsPath = (Resolve-Path $StagingDir).Path

try {
    $archive = [System.IO.Compression.ZipFile]::Open($ZipPath, [System.IO.Compression.ZipArchiveMode]::Create)

    Get-ChildItem -Path $StagingAbsPath -Recurse -File | ForEach-Object {
        # Build entry name with forward slashes — slug/path/to/file.ext
        $relativePath = $_.FullName.Substring($StagingAbsPath.Length).TrimStart('\', '/') -replace '\\', '/'
        $entryName = "$Slug/$relativePath"
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $_.FullName, $entryName) | Out-Null
    }

    $archive.Dispose()
} catch {
    Write-Host "  [FAIL] ZIP creation FAILED: $_" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $ZipPath)) {
    Write-Host "  [FAIL] ZIP creation FAILED" -ForegroundColor Red
    exit 1
}
Write-Host "  [OK] ZIP archive created (forward-slash paths, Linux-compatible)" -ForegroundColor Green

# Clean up staging directory
Remove-Item -Path $StagingDir -Recurse -Force -ErrorAction SilentlyContinue

# Step 5: Validate ZIP archive
Write-Host ""
Write-Host "[5/6] Validating ZIP archive..." -ForegroundColor Yellow

$TestDir = "$DistDir/test-extract"
if (Test-Path $TestDir) { Remove-Item -Path $TestDir -Recurse -Force }
New-Item -Path $TestDir -ItemType Directory -Force | Out-Null

# Extract ZIP to test using PowerShell
try {
    Expand-Archive -Path $ZipPath -DestinationPath $TestDir -Force
    Write-Host "  [OK] ZIP extracts successfully" -ForegroundColor Green
} catch {
    Write-Host "  [FAIL] ZIP extraction failed: $_" -ForegroundColor Red
    Remove-Item -Path $TestDir -Recurse -Force -ErrorAction SilentlyContinue
    exit 1
}

# Verify slug subfolder exists (WordPress requirement)
$slugFolder = Join-Path $TestDir $Slug
if (-not (Test-Path $slugFolder)) {
    Write-Host "  [FAIL] Missing top-level '$Slug/' folder - WordPress requires plugin-slug/plugin-file.php structure" -ForegroundColor Red
    Remove-Item -Path $TestDir -Recurse -Force -ErrorAction SilentlyContinue
    exit 1
}
Write-Host "  [OK] Top-level folder: $Slug/" -ForegroundColor Green

# Check for required files
$RequiredFiles = @($MainPluginFile.Name)
if (Test-Path "vendor/autoload.php") { $RequiredFiles += "vendor/autoload.php" }
if (Test-Path "src")      { $RequiredFiles += "src" }
if (Test-Path "includes") { $RequiredFiles += "includes" }
if (Test-Path "build")    { $RequiredFiles += "build" }
if (Test-Path "dist")     { $RequiredFiles += "dist" }
if (Test-Path "assets")   { $RequiredFiles += "assets" }

$AllFilesPresent = $true
foreach ($file in $RequiredFiles) {
    $testPath = Join-Path (Join-Path $TestDir $Slug) $file
    if (Test-Path $testPath) {
        Write-Host "  [OK] Found: $file" -ForegroundColor Green
    } else {
        Write-Host "  [FAIL] Missing: $file" -ForegroundColor Red
        $AllFilesPresent = $false
    }
}

if (-not $AllFilesPresent) {
    Write-Host "  [FAIL] Required files missing from package" -ForegroundColor Red
    Remove-Item -Path $TestDir -Recurse -Force -ErrorAction SilentlyContinue
    exit 1
}

# Check for forbidden files (security)
$ForbiddenFiles = @(".env", ".git", "node_modules", "tests")
$ForbiddenFound = $false
foreach ($file in $ForbiddenFiles) {
    $testPath = Join-Path (Join-Path $TestDir $Slug) $file
    if (Test-Path $testPath) {
        Write-Host "  [WARN] Found $file (should be excluded!)" -ForegroundColor Yellow
        $ForbiddenFound = $true
    } else {
        Write-Host "  [OK] Excluded: $file" -ForegroundColor Green
    }
}

# Clean up test directory
Remove-Item -Path $TestDir -Recurse -Force -ErrorAction SilentlyContinue

# Step 6: Generate report
Write-Host ""
Write-Host "[6/6] Build complete!" -ForegroundColor Yellow

$ZipFile = Get-Item "$DistDir/$ZipName"
$ZipSizeMB = [math]::Round($ZipFile.Length / 1MB, 2)

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Build Report" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Plugin: $PluginName" -ForegroundColor White
Write-Host "Slug: $Slug" -ForegroundColor White
Write-Host "Version: $Version" -ForegroundColor White
Write-Host "Build Date: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor White
Write-Host ""
Write-Host "Package Details:" -ForegroundColor Cyan
Write-Host "  File: $DistDir/$ZipName" -ForegroundColor White
Write-Host "  Size: $ZipSizeMB MB" -ForegroundColor White
Write-Host ""
Write-Host "Validation: PASSED" -ForegroundColor Green
Write-Host "  - Valid ZIP file (Compress-Archive)" -ForegroundColor Green
Write-Host "  - Extracts successfully" -ForegroundColor Green
Write-Host "  - Top-level folder: $Slug/" -ForegroundColor Green
Write-Host "  - Required files present" -ForegroundColor Green
if ($ForbiddenFound) {
    Write-Host "  - WARNING: Some forbidden files found" -ForegroundColor Yellow
} else {
    Write-Host "  - Forbidden files excluded" -ForegroundColor Green
}
Write-Host ""
Write-Host "Full path:" -ForegroundColor Cyan
Write-Host "  $($ZipFile.FullName)" -ForegroundColor White
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "  1. Test installation in WordPress" -ForegroundColor White
Write-Host "  2. Deploy to production" -ForegroundColor White
Write-Host "  3. Create git tag: git tag v$Version" -ForegroundColor White
Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
