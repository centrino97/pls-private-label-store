# Branching and Release Guardrails

This policy keeps `main` deploy-ready while enabling parallel feature and bugfix work.

## Goals
- `main` stays stable and always releasable.
- Work happens in short-lived branches with clear ownership.
- Conflicts are resolved early via frequent rebases onto `main`.
- Every change is tested and reviewed before merging.

## Branch naming
- Features: `feature/<slug>` (e.g., `feature/wc-sync-ui`).
- Bugfixes: `bugfix/<slug>` (e.g., `bugfix/attribute-sync-meta`).
- Hotfixes for production: `hotfix/<slug>`.

## Standard workflow
1. **Sync main**
   - `git checkout main`
   - `git pull origin main`
2. **Create branch**
   - `git checkout -b feature/<slug>`
3. **Develop**
   - Commit small, logical units (`git commit -m "scope: summary"`).
   - Follow existing docs in `/docs` for data model, sync, and Elementor conventions.
4. **Rebase often**
   - `git fetch origin`
   - `git rebase origin/main` to bring in upstream changes and resolve conflicts locally.
5. **Test before PR**
   - Run lint/unit checks relevant to touched areas (e.g., `php -l file.php`, Woo/Elementor smoke tests if applicable).
6. **Open PR**
   - Push branch: `git push origin feature/<slug>`.
   - Use the repository template: summarize changes, list tests, note risk areas.
7. **Merge**
   - Prefer **squash merge** to keep history clean.
   - Delete branch after merge.

## Deleting branches safely
- It is safe to delete a feature/hotfix branch locally and on the remote **after** it has been merged to `main` (or the current release branch) and the branch tip is fully contained there.
- If GitHub shows the branch as "behind" or with open conflicts, do **not** delete it until you rebase/merge and push the resolved branch; otherwise you will lose those changes.
- To confirm a branch is merged before deleting:
  - `git checkout main && git pull` to ensure you have the latest `main`.
  - `git branch --merged main` will list branches already merged into `main`.
  - Only delete branches that appear in that list: `git branch -d <branch>` locally and `git push origin --delete <branch>` remotely.
- Keeping `main` stable relies on this flow: short-lived feature branches → PR → merge → delete branch. Avoid long-lived branches that diverge far from `main`.

## Handling conflicts
- Always resolve conflicts on your branch via `git rebase origin/main` (avoid merging `main` into feature unless necessary).
- After resolving conflicts, rerun tests and force-push the rebased branch: `git push -f origin feature/<slug>`.

## Stability safeguards
- No direct commits to `main`; use PRs.
- Require one approval and green checks before merge.
- For risky changes, gate behind feature flags or config toggles where possible.
- Version bumps happen only when preparing a release branch or tag.

## Release cadence
- Cut a release branch (e.g., `release/0.2.x`) once a set of features is ready.
- Perform final regression testing on the release branch, then merge to `main` and tag.
- Hotfixes cherry-pick from `main` into the latest release branch as needed.
