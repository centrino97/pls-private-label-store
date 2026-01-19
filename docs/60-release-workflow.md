# Release Workflow

## Standard Process

### 1. Development
- Make code changes
- Test locally
- Update documentation if needed

### 2. Version Bump (3 files)
- `pls-private-label-store.php`: Header + constant
- `readme.txt`: Stable tag
- `uupd/index.json`: Version + timestamp

### 3. Changelog
- Add to `readme.txt`
- Add to `uupd/index.json`

### 4. Git Operations
```bash
git add -A
git commit -m "Release vX.Y.Z: Description"
git tag vX.Y.Z
git push origin main
git push origin vX.Y.Z
```

### 5. Verify
- Check GitHub Actions completed
- Verify release exists
- Confirm ZIP attached

## Commit Message Format

```
Release vX.Y.Z: Brief description

- Feature 1: Description
- Feature 2: Description
- Updated version to X.Y.Z in all files
```

## Version Format

- Semantic versioning: `MAJOR.MINOR.PATCH`
- Tag format: `vX.Y.Z` (must start with `v`)

## Critical Checklist

- [ ] Version bumped in all 3 files
- [ ] Timestamp updated
- [ ] Changelog added
- [ ] Committed with standard message
- [ ] Tagged and pushed
- [ ] Release verified

**MISSING ANY STEP = Update NOT visible!**
