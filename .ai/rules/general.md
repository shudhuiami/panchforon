---
paths:
  - '**'
---

# General

## Commit per completed feature, not one big drop
Commit as soon as a section or feature is complete and its tests pass, rather than batching everything into one commit at the end. The point is a reviewable trail: each commit should stand on its own so progress is easy to track and a single change is easy to revert.

Practical shape: run `vendor/bin/pint --dirty` and the narrowest passing test set before each commit, and write a message that says what changed and why — not just what file moved.

## CI gates: Pint on the whole tree, PHPStan level 6, Pest — run all three before pushing
`.github/workflows/ci.yml` runs `vendor/bin/pint --test` across the whole tree (not just changed files), `vendor/bin/phpstan analyse` at level 6 over `app/`, and `vendor/bin/pest`. `pint --dirty` alone is not enough, and PHPStan catches things tests do not: Larastan cannot resolve Eloquent scopes on a closure parameter typed as a bare `Builder`, and a PHPDoc placed on a call argument does not bind to the arrow function inside it — express the condition inline or type the builder some other way.

Trap for the Claude sandbox specifically: `phpstan/phpstan` is dist-only from `api.github.com`, which the egress policy blocks, so `composer install` fails and `vendor/bin/phpstan` is absent. The release PHAR at `github.com/phpstan/phpstan/releases/download/<version>/phpstan.phar` is reachable, and Larastan plus its dependencies can be git-fetched at the SHA pinned in `composer.lock`; register them with a small `--autoload-file` shim. Do not commit any of that — the manifests are correct as they are.

## A Laravel HTTP call poisons the Livewire harness that follows it in the same test
Calling `$this->postJson(...)` (or any of the HTTP test helpers) and then driving a Filament screen with `Livewire::test(...)->callTableAction(...)` in the same test fails with `Attempt to read property "mountedActions" on null`. The HTTP request leaves the container in a state the Livewire test harness does not re-initialise.

Seed the row with a factory and drive Livewire, or exercise the endpoint in its own test. Do not interleave the two styles.

## Larastan reads an attribute's type from $fillable unless a @property says otherwise
A `casts()` entry is invisible to it. A model with `'reviewed_at' => 'datetime'` in `casts()` but no `@property Carbon|null $reviewed_at` is read as `string`, and any `->format()` on it is a level-6 error. Document every cast attribute with a `@property` line — the other models in this app already do, which is why they pass.
