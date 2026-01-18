# GitHub Actions & UUPD Workflow Documentation

## Overview

This document explains the complete workflow for releasing plugin updates and how the UUPD (Universal Update Plugin Downloader) system works. **Understanding this workflow is critical** - changes won't be visible to users until all steps are completed.

---

## 🔄 Complete Release Workflow

### Step 1: Make Code Changes
- Edit files in the codebase
- Test changes locally
- Commit changes to git

### Step 2: Update Version Numbers
**CRITICAL:** Version must be bumped in **ALL** of these files:

1. **`pls-private-label-store.php`**
   ```php
   * Version: 0.8.9
   define( 'PLS_PLS_VERSION', '0.8.9' );
   ```

2. **`readme.txt`**
   ```
   Stable tag: 0.8.9
   ```

3. **`uupd/index.json`**
   ```json
   {
     "version": "0.8.9",
     "last_updated": "2026-01-18 22:30:00"
   }
   ```

4. **Update changelog** in both `readme.txt` and `uupd/index.json`

### Step 3: Commit and Tag
```bash
git add -A
git commit -m "v0.8.9: Description of changes"
git tag v0.8.9
git push origin main
git push origin v0.8.9
```

### Step 4: GitHub Actions Automatically Builds Release
When you push a tag (`v0.8.9`), GitHub Actions automatically:
1. **Triggers** the workflow (`.github/workflows/build-release-zip.yml`)
2. **Builds** a ZIP file containing the plugin
3. **Creates** a GitHub Release (if it doesn't exist)
4. **Attaches** the ZIP file to the release

**The ZIP file is available at:**
```
https://github.com/centrino97/pls-private-label-store/releases/latest/download/pls-private-label-store.zip
```

### Step 5: UUPD Detects Update
WordPress checks for updates by fetching:
```
https://raw.githubusercontent.com/centrino97/pls-private-label-store/main/uupd/index.json
```

UUPD compares:
- **Current version** (from `pls-private-label-store.php`)
- **Latest version** (from `uupd/index.json`)

If `uupd/index.json` has a higher version, WordPress shows an update notification.

---

## ⚠️ Why Changes Aren't Visible Until Steps Are Complete

### The Problem
Users won't see updates until **ALL** of these are true:

1. ✅ Version bumped in `pls-private-label-store.php`
2. ✅ Version bumped in `uupd/index.json`
3. ✅ `last_updated` timestamp updated in `uupd/index.json`
4. ✅ Changes committed and pushed to `main` branch
5. ✅ Git tag created and pushed
6. ✅ GitHub Actions workflow completes (builds ZIP)
7. ✅ User clicks "Check for updates" in WordPress

### Common Issues

#### Issue 1: Version Not Bumped in `uupd/index.json`
**Symptom:** No update notification appears
**Reason:** WordPress compares versions from `uupd/index.json`. If it's not updated, WordPress thinks there's no update.
**Fix:** Always update `uupd/index.json` version AND `last_updated` timestamp.

#### Issue 2: Tag Not Pushed
**Symptom:** ZIP file not available or outdated
**Reason:** GitHub Actions only runs when a tag is pushed. Without the tag, no ZIP is built.
**Fix:** Always push both the branch AND the tag:
```bash
git push origin main
git push origin v0.8.9
```

#### Issue 3: GitHub Actions Workflow Not Complete
**Symptom:** ZIP file missing or old version
**Reason:** GitHub Actions takes 1-2 minutes to build the ZIP. If you check immediately, it might not be ready.
**Fix:** Wait 1-2 minutes after pushing the tag, then check the release.

#### Issue 4: User Hasn't Clicked "Check for Updates"
**Symptom:** Update available but user doesn't see it
**Reason:** WordPress caches update checks. Users need to manually click "Check for updates" or wait for the next automatic check.
**Fix:** Instruct users to click "Check for updates" button in WordPress admin.

---

## 📋 UUPD System Details

### How UUPD Works

1. **Plugin Registration** (in `pls-private-label-store.php`):
   ```php
   \UUPD\V1\UUPD_Updater_V1::register( [
       'plugin_file' => plugin_basename( PLS_PLS_FILE ),
       'slug'        => 'pls-private-label-store',
       'name'        => 'PLS – Private Label Store Manager (Woo + Elementor)',
       'version'     => PLS_PLS_VERSION,
       'server'      => 'https://raw.githubusercontent.com/centrino97/pls-private-label-store/main/uupd/index.json',
   ] );
   ```

2. **Update Check**:
   - WordPress fetches `uupd/index.json` from the `server` URL
   - Compares `version` in JSON with `PLS_PLS_VERSION` constant
   - If JSON version > current version → shows update notification

3. **Update Download**:
   - When user clicks "Update", WordPress downloads the ZIP from:
     ```
     https://github.com/centrino97/pls-private-label-store/releases/latest/download/pls-private-label-store.zip
     ```
   - This URL is defined in `uupd/index.json` → `download_url`

### UUPD JSON Structure

The `uupd/index.json` file must contain:

```json
{
  "slug": "pls-private-label-store",
  "name": "PLS – Private Label Store Manager (Woo + Elementor)",
  "version": "0.8.9",
  "author": "Z2HB team",
  "author_homepage": "https://zerotoherobusiness.com",
  "requires_php": "7.4",
  "requires": "6.5",
  "tested": "6.7.0",
  "sections": {
    "description": "Private label store manager for WooCommerce + Elementor.",
    "installation": "Upload the plugin ZIP via Plugins > Add New and activate.",
    "changelog": "#### 0.8.9\n\n* **Feature:** Description..."
  },
  "last_updated": "2026-01-18 22:30:00",
  "download_url": "https://github.com/centrino97/pls-private-label-store/releases/latest/download/pls-private-label-store.zip",
  "banners": {},
  "icons": {}
}
```

**Critical Fields:**
- `version`: Must match the version in `pls-private-label-store.php`
- `last_updated`: Should be updated to current timestamp (helps with cache invalidation)
- `download_url`: Points to the GitHub release ZIP file

---

## 🚀 Quick Release Checklist

When releasing a new version, **ALWAYS** follow this checklist:

- [ ] Make code changes
- [ ] Update version in `pls-private-label-store.php` (both header and constant)
- [ ] Update version in `readme.txt` (`Stable tag:`)
- [ ] Update version in `uupd/index.json`
- [ ] Update `last_updated` timestamp in `uupd/index.json`
- [ ] Add changelog entry in `readme.txt`
- [ ] Add changelog entry in `uupd/index.json`
- [ ] Commit all changes: `git add -A && git commit -m "v0.8.9: Description"`
- [ ] Create tag: `git tag v0.8.9`
- [ ] Push branch: `git push origin main`
- [ ] Push tag: `git push origin v0.8.9`
- [ ] Wait 1-2 minutes for GitHub Actions to complete
- [ ] Verify release exists at: https://github.com/centrino97/pls-private-label-store/releases
- [ ] Verify ZIP is attached to release
- [ ] Test update detection: Click "Check for updates" in WordPress

---

## 🔍 Troubleshooting

### Update Not Detected

1. **Check version numbers match:**
   ```bash
   grep "Version:" pls-private-label-store.php
   grep "Stable tag:" readme.txt
   grep '"version":' uupd/index.json
   ```
   All three should show the same version.

2. **Check tag exists:**
   ```bash
   git tag --list | grep v0.8.9
   ```

3. **Check release exists:**
   Visit: https://github.com/centrino97/pls-private-label-store/releases
   Verify the tag `v0.8.9` exists and has a ZIP file attached.

4. **Check JSON is accessible:**
   Visit: https://raw.githubusercontent.com/centrino97/pls-private-label-store/main/uupd/index.json
   Verify it shows the correct version.

5. **Clear WordPress cache:**
   - Delete transients: `wp transient delete --all` (if WP-CLI available)
   - Or wait for automatic cache expiration (usually 12 hours)

### ZIP File Missing or Wrong Version

1. **Check GitHub Actions workflow:**
   Visit: https://github.com/centrino97/pls-private-label-store/actions
   Verify the workflow ran successfully for the tag.

2. **Manually trigger workflow (if needed):**
   - Delete and recreate the tag
   - Or manually create a release on GitHub

---

## 📝 Important Notes

1. **Always bump version before committing** - Don't forget!
2. **Always push both branch AND tag** - The tag triggers the build
3. **Always update `last_updated` timestamp** - Helps with cache invalidation
4. **Wait 1-2 minutes** after pushing tag before checking for updates
5. **Users must click "Check for updates"** - WordPress doesn't auto-check immediately

---

## 🎯 Summary

**The Golden Rule:** For any change to be visible to users:

1. ✅ Code changes committed
2. ✅ Version bumped in ALL 3 places
3. ✅ Changes pushed to `main` branch
4. ✅ Tag created and pushed
5. ✅ GitHub Actions builds ZIP (automatic, takes 1-2 min)
6. ✅ User clicks "Check for updates" in WordPress

**If ANY step is missing, the update won't be visible!**

---

## 📚 Related Documentation

- `.github/workflows/build-release-zip.yml` - GitHub Actions workflow
- `includes/updater.php` - UUPD updater implementation
- `uupd/index.json` - Update metadata
- `docs/80-onboarding-for-new-developer.md` - General plugin overview
