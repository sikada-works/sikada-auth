# Git Workflow for Updates

Now that your repository is set up, here is the standard process for making updates to your plugin.

## 1. Make Changes
Edit your files, add new features, or fix bugs as needed.

## 2. Check Status
See which files have changed:
```bash
git status
```

## 3. Stage Changes
Prepare your changes for committing. 

To stage **ALL** changes (most common):
```bash
git add .
```

To stage specific files only:
```bash
git add docs/GIT-WORKFLOW.md
```

## 4. Commit Changes
Save your snapshot with a descriptive message. This saves it to your **local** computer.
```bash
git commit -m "Added a new features"
```

## 5. Push to GitHub
Upload your commits to the remote server.
```bash
git push
```
*(Note: You only needed `-u origin main` the first time. From now on, just `git push` is enough).*

---

## Summary Cheat Sheet

```bash
git status          # See what changed
git add .           # Stage everything
git commit -m "Msg" # Save snapshot
git push            # Upload to GitHub
```

## Pro Tip: Pull First
If you edit files directly on GitHub.com (or if another developer pushes code), always download the latest version before you start working:
```bash
git pull
```

## 6. Releasing a New Version
When you are ready to bump the version number (e.g., 1.0.0 -> 1.0.1), make sure to update **ALL** of these files before committing:

1.  **`sikada-auth.php`**:
    *   Update `Version: X.X.X` in the top comment block.
    *   Update `define('SIKADA_AUTH_VERSION', 'X.X.X');` constant.

2.  **`package.json`**:
    *   Update `"version": "X.X.X"` (Used for building assets).

3.  **`CHANGELOG.md`**:
    *   Add a new section `## [X.X.X] - YYYY-MM-DD` at the top.
    *   List your Added/Fixed/Changed items.

4.  **`readme.txt`** (If/When submitted to WordPress.org):
    *   Update `Stable tag: X.X.X`.

Once updated, run the standard Git workflow:
```bash
git add .
git commit -m "Bump version to X.X.X"
git push
```
