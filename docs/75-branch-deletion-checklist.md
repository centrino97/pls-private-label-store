# Branch deletion quick checklist

This is the shortest possible set of steps to confirm a branch is safe to delete without losing work. Follow them exactly, in order. Replace `<branch>` with the branch name you want to delete.

## 1) Make sure `main` is up to date locally
```bash
git checkout main
git pull
```

## 2) Confirm the branch is already merged
```bash
git branch --merged main
```
Look for `<branch>` in the output. If it is **not** listed, stop here—merge it first (rebase/merge onto `main`, push, and complete the PR) before deleting.

## 3) Delete the local branch (only if merged)
```bash
git branch -d <branch>
```

## 4) Delete the remote branch (only if merged)
```bash
git push origin --delete <branch>
```

## 5) Verify cleanup
```bash
git fetch --prune
```
Make sure `<branch>` no longer appears in `git branch -a`.

## What if the branch is not merged?
* Keep it. Rebase or merge it onto `main` to resolve conflicts.
* Push the updated branch.
* Merge it through your normal PR flow.
* Then repeat the checklist above.
