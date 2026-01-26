<#
.SYNOPSIS
    Packages the Sikada Auth plugin for distribution.
.DESCRIPTION
    This script performs the following steps:
    1. Installs production PHP dependencies (no dev).
    2. Builds production JS/CSS assets.
    3. Creates a temporary build directory.
    4. Copies all necessary plugin files to the build directory.
    5. Zips the build directory into a release archive.
    6. Cleans up temporary files.
#>

$PluginName = "sikada-auth"
$Version = "1.0.0" # You could parse this from plugin.php if desired
$ZipName = "$PluginName-$Version.zip"
$BuildDir = "build_tmp\$PluginName"

Write-Host "📦 Starting Package Process for $PluginName..." -ForegroundColor Cyan

# 1. Build Assets
Write-Host "🏗️  Building Frontend Assets..." -ForegroundColor Yellow
npm install
npm run build
if ($LASTEXITCODE -ne 0) { Write-Error "Build failed"; exit 1 }

# 2. Install Production Composer Dependencies
Write-Host "🎼 Installing Production Composer Dependencies..." -ForegroundColor Yellow
composer install --no-dev --optimize-autoloader
if ($LASTEXITCODE -ne 0) { Write-Error "Composer failed"; exit 1 }

# 3. Create Build Directory
if (Test-Path "build_tmp") { Remove-Item "build_tmp" -Recurse -Force }
New-Item -ItemType Directory -Path $BuildDir | Out-Null

# 4. Copy Files
Write-Host "CX  Copying files..." -ForegroundColor Yellow
$Exclude = @(
    ".git", ".github", ".vscode", ".gitignore", "node_modules", 
    "tests", "scripts", "build_tmp", "*.zip", "Thumbs.db", ".DS_Store",
    "composer.lock", "package-lock.json", "webpack.config.js"
)

# Robust copy using Robocopy is often better, but for simplicity we use Copy-Item with exclusion logic isn't trivial in PS without robo.
# We will explicitly list folders/files to include or use a whitelist approach which is safer.

$IncludeItems = @(
    "src",
    "assets",
    "blocks",
    "build",
    "vendor",
    "languages",
    "templates",
    "sikada-auth.php",
    "README.md",
    "LICENSE"
)

foreach ($Item in $IncludeItems) {
    if (Test-Path $Item) {
        Copy-Item -Path $Item -Destination $BuildDir -Recurse -Force
    }
}

# 5. Zip it up
Write-Host "🤐 Zipping..." -ForegroundColor Yellow
if (Test-Path $ZipName) { Remove-Item $ZipName }
Compress-Archive -Path "build_tmp\$PluginName" -DestinationPath $ZipName

# 6. Cleanup
Write-Host "🧹 Cleanup..." -ForegroundColor Yellow
Remove-Item "build_tmp" -Recurse -Force

# 7. Restore Dev Dependencies (Optional)
Write-Host "🔄 Restoring Dev Dependencies..." -ForegroundColor Yellow
composer install

Write-Host "✅ Done! Package created: $ZipName" -ForegroundColor Green
