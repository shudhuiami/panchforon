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

## Filament modal contents are not in the component's rendered HTML
`assertSee` on a Livewire component will not find anything inside a mounted action's modal. Use `assertMountedActionModalSee` / `assertMountedActionModalSeeHtml`. A form-component action is addressed as `TestAction::make('name')->schemaComponent('field_name')`, and a nested action needs its wrapping `Actions` component to carry an explicit `->key()`.

## A Filament hidden field bound to an array arrives back as "[object Object]"
The browser stringifies it. Keep hidden state scalar — the studio's video picker pages through YouTube by storing a space-joined string of page tokens rather than an array of them.

## Filament's own JS/CSS is generated, not committed — and the SPA fallback used to hide that
`public/js/filament`, `public/css/filament` and `public/fonts/filament` are gitignored. They are produced by `php artisan filament:assets`, so a fresh clone and a `git reset --hard` deploy both start without them.

The failure is silent rather than loud: a request for a missing `/js/filament/support/support.js` reaches PHP, and the SPA catch-all in `routes/web.php` used to match it and return the storefront's HTML with a **200**. The browser gets a page where it asked for a script, so the only symptom is `filamentDropdown is not defined` in the console — every panel renders but no dropdown opens, no action modal mounts, no table filter works.

Two things now keep it fixed, and both should stay: `composer.json`'s `post-autoload-dump` runs `filament:upgrade` (which publishes the assets) so every `composer install` regenerates them, and the catch-all excludes `build/ css/ fonts/ icons/ js/` so a missing asset is a plain 404 again. `SpaEntryTest` covers both.
