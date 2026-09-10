---
paths:
  - '**'
---

# General

## Commit per completed feature, not one big drop
Commit as soon as a section or feature is complete and its tests pass, rather than batching everything into one commit at the end. The point is a reviewable trail: each commit should stand on its own so progress is easy to track and a single change is easy to revert.

Practical shape: run `vendor/bin/pint --dirty` and the narrowest passing test set before each commit, and write a message that says what changed and why — not just what file moved.
