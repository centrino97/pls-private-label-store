# Branch synchronization guide

When GitHub shows a branch as "ahead/behind" (e.g., **1 commit ahead and 4 commits behind main**), it means:

- **Behind**: main has 4 new commits you don’t have locally.
- **Ahead**: your branch has 1 commit that is not on main.

To merge safely without hitting “Update branch” blockers:

1. **Add the remote** (if missing):
   ```bash
   git remote add origin <your-repo-url>
   git fetch origin
   ```
2. **Update main locally**:
   ```bash
   git checkout main
   git pull origin main
   ```
3. **Rebase your work branch onto main** (preferred for a clean history):
   ```bash
   git checkout work
   git rebase main
   ```
   - If conflicts appear, resolve them, then `git rebase --continue`.
4. **Push the rebased branch** (force-with-lease to avoid overwriting others):
   ```bash
   git push --force-with-lease origin work
   ```
   - If your org disallows force-push, use a merge instead:
     ```bash
     git checkout work
     git merge main
     git push origin work
     ```

Once the branch is up to date with main, GitHub will allow you to create or update the PR without the ahead/behind blocker.
