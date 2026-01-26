# Packaging Guide

This document outlines the process for packaging the **Sikada Auth** plugin for distribution (e.g., to install on another site or sell).

## Prerequisites

Ensure you have the following installed on your machine:
*   [Node.js & NPM](https://nodejs.org/) (for building blocks/assets)
*   [Composer](https://getcomposer.org/) (for PHP dependencies)
*   [7-Zip](https://www.7-zip.org/) or standard Zip utilities.

---

## Option 1: Automated Script (Recommended)

We have provided a PowerShell script to automate the entire process. This script handles building assets, stripping development files, and creating a clean ZIP file.

### Usage

1.  Open PowerShell or your terminal.
2.  Navigate to the plugin root directory.
3.  Run the script:

```powershell
./scripts/package.ps1
```

### What the script does:
1.  Runs `npm run build` to compile optimized JS/CSS assets.
2.  Runs `composer install --no-dev` to include only production PHP libraries.
3.  Copies relevant files to a temporary directory (excluding `node_modules`, tests, git files, etc.).
4.  Zips the directory into `sikada-auth-1.0.0.zip`.
5.  Restores your development dependencies (runs `composer install` again).

---

## Option 2: Manual Packaging

If you prefer to package the plugin manually, follow these steps strictly to ensure the plugin works on the destination site.

### 1. Build Production Assets
First, compile the Gutenberg blocks and assets for production (minified).

```bash
npm install
npm run build
```

### 2. Prepare PHP Dependencies
Remove developer tools (like testing frameworks) from the `vendor` folder to keep the plugin light.

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Create the Package Folder
1.  Create a new folder on your desktop named `sikada-auth`.
2.  Copy the following files/folders from your project into this new folder:
    *   `src/`
    *   `build/` (IMPORTANT: Contains the compiled assets)
    *   `vendor/` (IMPORTANT: Contains Autoloader)
    *   `blocks/` (Source files are needed if you want them editable, or just `build` if purely distribution, but keeping `blocks` is safer for structure references)
    *   `assets/` (Images/CSS)
    *   `languages/` (If exists)
    *   `templates/` (If exists)
    *   `sikada-auth.php`
    *   `README.md`
    *   `LICENSE`

### 4. Cleanup
**Do NOT copy** the following files/folders, as they are not needed for production:
*   `node_modules/` (Heavy and unnecessary)
*   `tests/`
*   `.git/`
*   `.github/`
*   `scripts/`
*   `webpack.config.js`
*   `package.json` / `package-lock.json`
*   `composer.json` / `composer.lock` (Optional: keep these if users need to reinstall deps, but `vendor` is usually sufficient)
*   `docs/`

### 5. Zip
Right-click the `sikada-auth` folder and select **Send to > Compressed (zipped) folder**.

Rename the resulting file to `sikada-auth.zip`.

### 6. Restore Development State
Since Step 2 removed your dev dependencies, run this to get them back for your local work:

```bash
composer install
```
