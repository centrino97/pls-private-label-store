# Agent Prompt: PLS Plugin v0.8.0 Development

## Context
You are working on the **PLS (Private Label Store) WordPress Plugin**, version 0.8.0. This is a WordPress plugin that manages a private-label WooCommerce store with Elementor integration.

## Your Task
Complete the plugin development for version 0.8.0 based on client specifications and the current codebase state.

## First Steps (Do This First!)

1. **Read the onboarding guide:** `docs/80-onboarding-for-new-developer.md` - This contains everything you need to know
2. **Review the codebase structure** - Understand the file organization
3. **Review client specifications** - Look for any client requirements document or specifications
4. **Check current implementation status** - See what's done and what's missing

## Key Information

### What This Plugin Does
- Manages private-label products with pack tiers (50, 100, 250, 500, 1000 units)
- Syncs to WooCommerce as variable products with variations
- Provides attribute/swatch management system
- Integrates with Elementor for frontend display
- Supports bundle/offer system (partially implemented)

### Current State (v0.7.1)
- ✅ Product management fully working
- ✅ WooCommerce sync working
- ✅ Attributes/swatches working
- 🚧 Bundle system partially implemented (needs completion)
- ❌ Frontend offers system is stub

### Architecture
- **Custom database tables** store structured data
- **Repository classes** handle data access
- **WooCommerce sync layer** converts PLS data to WooCommerce objects
- **Elementor widgets** render on frontend
- **Admin interface** uses modal-based UI

### Important Files to Review
- `pls-private-label-store.php` - Main plugin file
- `includes/class-pls-plugin.php` - Plugin bootstrap
- `includes/wc/class-pls-wc-sync.php` - WooCommerce sync logic
- `includes/admin/class-pls-admin-ajax.php` - Admin AJAX handlers
- `includes/data/repo-*.php` - Repository classes
- `docs/50-implementation-spec.md` - Detailed implementation spec

## Development Rules

1. **Never modify WooCommerce core** - Use hooks/filters only
2. **Always validate/sanitize input** - Security is critical
3. **Keep sync idempotent** - Can run multiple times safely
4. **Test after each change** - Don't break existing functionality
5. **Follow WordPress coding standards** - Use WPCS
6. **Maintain backreferences** - Store WooCommerce IDs in PLS tables

## Version 0.8.0 Goals

### Primary Tasks
1. **Complete Bundle System**
   - Finish bundle management admin UI
   - Implement bundle sync to WooCommerce
   - Implement cart logic for bundles
   - Build offer eligibility system

2. **Client Requirements**
   - Review and implement client-specific features
   - Address any custom requirements

3. **Code Quality**
   - Improve error handling
   - Add comprehensive logging
   - Test all functionality

## Workflow

1. **Make changes** on branch `feature/v0.8.0-upgrade`
2. **Test thoroughly** before committing
3. **Commit with clear messages**
4. **When ready:** Merge to main and create release

## Version Bump Process (When Ready)

When version 0.8.0 is complete:
1. Update version in `pls-private-label-store.php` (line 5 and 20)
2. Update version in `uupd/index.json` (line 4)
3. Update "Stable tag" in `readme.txt` (line 7)
4. Add changelog entry to both files
5. Commit: `git commit -m "Bump version to 0.8.0"`
6. Merge to main: `git checkout main && git merge feature/v0.8.0-upgrade`
7. Push: `git push origin main`
8. Create tag: `git tag v0.8.0 && git push origin v0.8.0`
9. GitHub Actions will build and release automatically

## Questions to Answer

Before starting development, answer these:
1. What are the specific client requirements for v0.8.0?
2. What bundle features need to be completed?
3. Are there any custom features needed?
4. What testing is required?

## Getting Help

- Review `docs/80-onboarding-for-new-developer.md` for comprehensive guide
- Check `docs/50-implementation-spec.md` for implementation details
- Review existing code for patterns and examples
- Check WordPress/WooCommerce/Elementor documentation

## Current Branch
You are working on: `feature/v0.8.0-upgrade`

## Next Action
1. Read `docs/80-onboarding-for-new-developer.md` completely
2. Review client specifications (if available)
3. Identify specific tasks for v0.8.0
4. Start implementation

---

**Remember:** This is a production plugin. Test everything thoroughly before committing changes.
