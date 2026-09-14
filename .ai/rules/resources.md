---
paths:
  - 'resources/**'
---

# Resources

## Rebuild public/build whenever frontend sources change
public/build is committed, not ignored: the Hostinger target has no Node, so the compiled assets have to arrive with the repository.

This means the build can go stale silently. Any change under resources/ (React, CSS, or the Filament admin theme) must be followed by `npm run build` in the same commit, or the deployed site keeps serving the old bundle while the source looks correct.

The manifest is content-hashed, so a rebuild with no source change produces no diff. If `git status` shows nothing after building, there was nothing to rebuild.
