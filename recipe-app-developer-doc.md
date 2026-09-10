# Developer Documentation — Recipe & Meal Planner

**Companion to:** `recipe-app-product-doc.md`
**Stack:** Laravel 12 (API) + React 19 (SPA) + MySQL
**Hosting:** Hostinger shared — single origin, SPA served from Laravel's `public/`
**Build window:** 4 weeks committed (~56 hours at 2h/day) + 2 weeks buffer

---

## 1. Architecture

```
┌─────────────────────────┐
│   React SPA (Vite)      │
│   TypeScript            │
│   TanStack Query        │
└───────────┬─────────────┘
            │ JSON over HTTPS
            │ Sanctum token auth
┌───────────▼─────────────┐
│   Laravel API           │
│                         │
│  ┌───────────────────┐  │
│  │ MergeEngine       │  │  ← core domain logic,
│  │ RankingService    │  │    framework-independent,
│  │ IngredientParser  │  │    heavily tested
│  └───────────────────┘  │
│                         │
│  ┌───────────────────┐  │
│  │ MealDbImporter    │  │  ← runs as artisan command,
│  └───────────────────┘  │    not at request time
└───────────┬─────────────┘
            │
┌───────────▼─────────────┐
│   MySQL 8 / MariaDB     │
└─────────────────────────┘
```

**Key decision:** the three domain services are plain PHP classes with no Eloquent dependency. They take DTOs in and return DTOs out. This makes them trivially unit-testable and is the same architectural instinct you already demonstrated in `messenger-commerce-bot` — worth being consistent about, and worth saying so in the interview.

**Why MySQL:** Hostinger doesn't offer PostgreSQL on shared or cloud plans — it's VPS-only. So the hosting decides this, and it's the right call anyway: it's the database you already know, and during a 56-hour build an unfamiliar database is pure cost with no payoff at this scale.

Practical notes:

- Use **`utf8mb4`** everywhere, never `utf8`. MySQL's `utf8` is the broken 3-byte variant and will mangle Bengali ingredient names.
- The default `utf8mb4_unicode_ci` collation is **already case-insensitive**, so `canonical_name` and `alias` lookups need no special handling.
- `enum` columns are native — `source` and `default_dimension` work as written.
- Store raw import payloads in a `json` column or in `storage/imports/`. You'll never query inside them, so either is fine.
- If you add search later, MySQL `FULLTEXT` indexes on `recipes.title` are adequate. Search is #2 on the cut list, so this probably won't come up.

---

## 2. Database schema

### users
Standard Laravel scaffold. `id`, `name`, `email`, `password`, timestamps.

### recipes

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK nullable | null = imported from API |
| source | enum('api','user') | |
| external_id | string nullable | TheMealDB `idMeal`, unique when present |
| title | string | |
| slug | string unique | |
| cuisine | string nullable | maps from TheMealDB `strArea` |
| category | string nullable | maps from `strCategory` |
| instructions | text | |
| image_url | string nullable | |
| servings | smallint default 4 | imported recipes assume 4 |
| source_url | string nullable | attribution for imported recipes |
| created_at / updated_at | | |

Index on `cuisine`, `category`, `source`.

### ingredients

Canonical ingredient records. Deduplicated across all recipes.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| canonical_name | string unique | lowercase, singular — "onion", "chicken breast" |
| default_dimension | enum('mass','volume','count','none') | what unit type this usually takes |

### ingredient_aliases

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| ingredient_id | bigint FK | |
| alias | string unique | "onions", "spring onion", "scallion", "peyaj" |

This table is how "coriander" and "cilantro" become one shopping-list line, and how Bengali ingredient names map onto English canonical names. It grows over time; seed it with the obvious pairs and add as the importer surfaces misses.

### recipe_ingredients

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| recipe_id | bigint FK | |
| ingredient_id | bigint FK nullable | null when parsing failed |
| quantity | decimal(10,3) nullable | null for "to taste" |
| unit | string nullable | normalised unit code: `g`, `ml`, `piece`, `tbsp` |
| raw_text | string | always preserved — "1 1/2 cups plain flour, sifted" |
| position | smallint | display order |

**Always keep `raw_text`.** When parsing fails, the shopping list falls back to showing it verbatim. Honest degradation beats silent data loss, and it's a good thing to point at in an interview.

### ratings

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK | |
| recipe_id | bigint FK | |
| stars | smallint | 1–5, validated |
| review | text nullable | max 1000 chars |
| created_at / updated_at | | |

Unique constraint on `(user_id, recipe_id)` — one rating per user per recipe, updated in place.

### recipe_stats

Denormalised ranking cache. Recomputed on rating write.

| Column | Type | Notes |
|---|---|---|
| recipe_id | bigint PK FK | |
| ratings_count | int default 0 | |
| ratings_avg | decimal(3,2) nullable | |
| bayesian_score | decimal(5,4) nullable | the actual sort key |
| updated_at | | |

Index on `bayesian_score DESC`. This is what the browse page sorts by — do not compute it at query time.

### meal_plans

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK | |
| name | string default 'This week' | |
| is_active | boolean | one active plan per user in v1 |

### meal_plan_items

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| meal_plan_id | bigint FK | |
| recipe_id | bigint FK | |
| servings | smallint | user's desired servings, scales quantities |

Unique on `(meal_plan_id, recipe_id)`.

### shopping_list_items

Generated output, persisted so check-off state survives a refresh.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| meal_plan_id | bigint FK | |
| ingredient_id | bigint FK nullable | |
| display_name | string | |
| quantity | decimal(10,3) nullable | |
| unit | string nullable | |
| is_unmerged | boolean | true for "to taste" / unparsed items |
| source_note | string nullable | e.g. "from 3 recipes" |
| is_checked | boolean default false | |

---

## 3. The ingredient parser

Input: a raw string. Output: `ParsedIngredient { quantity, unit, name, raw }`.

### Pipeline

1. **Extract quantity.** Handle integers (`2`), decimals (`1.5`), vulgar fractions (`½`), mixed numbers (`1 1/2`), and ranges (`2-3` → take the midpoint or lower bound; pick one and document it).
2. **Extract unit.** Match against a known unit vocabulary with aliases: `g/gram/grams/gm`, `kg/kilo/kilogram`, `ml/millilitre`, `l/litre`, `tsp/teaspoon`, `tbsp/tablespoon`, `cup/cups`, `oz/ounce`, `lb/pound`, `clove/cloves`, `piece/pieces/pcs`.
3. **Strip descriptors.** Remove leading/trailing preparation words: `chopped`, `sliced`, `diced`, `minced`, `fresh`, `dried`, `large`, `small`, `finely`, `roughly`. Keep a stopword list.
4. **Normalise name.** Lowercase, trim, singularise, collapse whitespace.
5. **Resolve identity.** Look up in `ingredients.canonical_name`, then `ingredient_aliases.alias`. On miss, create a new `ingredients` row and log it for review.
6. **On failure**, return `quantity: null, unit: null, ingredient_id: null` and preserve `raw_text`.

### Do not use fuzzy string matching in v1

Levenshtein distance on ingredient names looks clever and produces wrong merges ("butter" / "peanut butter" are close in edit distance and very different in a shopping basket). The alias table is deterministic, explainable, and correct. This is a good "why I chose the boring option" answer.

---

## 4. The merge engine

**Signature:** takes a collection of `(ParsedIngredient, servingsMultiplier)` from all recipes in a plan, returns a collection of `ShoppingListLine`.

### Unit dimensions and canonical units

| Dimension | Canonical | Members |
|---|---|---|
| mass | gram | g, kg, oz, lb |
| volume | millilitre | ml, l, tsp, tbsp, cup, fl oz |
| count | piece | piece, clove, slice, whole |
| none | — | "to taste", "for frying", unparsed |

### Conversion table (to canonical)

```
kg   → g   × 1000
oz   → g   × 28.3495
lb   → g   × 453.592
l    → ml  × 1000
tsp  → ml  × 4.92892
tbsp → ml  × 14.7868
cup  → ml  × 236.588
floz → ml  × 29.5735
```

`cup` is US customary. Note this in the docs — it's a real ambiguity (US vs metric vs imperial cup) and an interviewer who cooks will appreciate that you noticed.

### Algorithm

```
group by (ingredient_id, dimension)

for each group:
    if dimension == 'none' or ingredient_id is null:
        emit one line per source, is_unmerged = true
        (never fabricate a quantity for "salt to taste")
    else:
        total = Σ (quantity × servingsMultiplier × conversionFactor(unit))
        emit one line:
            quantity = prettify(total, dimension)
            unit     = chosen display unit
            note     = "from N recipes" if N > 1
```

### Two rules that matter

**Never merge across dimensions.** If one recipe wants 200g of chicken and another wants 2 pieces of chicken, that's two lines. Guessing a conversion is worse than showing both. Say this out loud in an interview — "I chose to under-merge rather than produce a confidently wrong number."

**Prettify the output.** 1500g should display as 1.5kg, not 1500g. 0.25 pieces of onion should round up to 1 — you can't buy a quarter onion. Rounding rules differ by dimension: mass and volume round to sensible precision, counts round *up* to whole numbers.

### Test cases to write first

Write these before the implementation. They double as your specification.

1. Two recipes, same ingredient, same unit → one merged line
2. Two recipes, same ingredient, different units same dimension → one line, converted
3. Two recipes, same ingredient, different dimensions → two lines
4. "Salt to taste" from three recipes → three unmerged lines, no quantity invented
5. Servings multiplier of 2 doubles quantities
6. Unparsed ingredient → passthrough line with raw text
7. Alias resolution: "coriander" + "cilantro" → one line
8. Prettify: 1500g → 1.5kg; 0.25 onion → 1 onion
9. Empty meal plan → empty list, no error
10. Single recipe → no merging, quantities preserved exactly

---

## 5. The ranking algorithm

### Bayesian average

```
score = (v / (v + m)) × R  +  (m / (v + m)) × C

v = this recipe's rating count
R = this recipe's mean rating
m = minimum-votes threshold (start at 10)
C = global mean rating across all rated recipes
```

**What this does:** a recipe with 1 five-star rating and `m = 10`, `C = 3.8` scores `(1/11)×5 + (10/11)×3.8 = 3.91`. A recipe with 200 ratings averaging 4.6 scores `(200/210)×4.6 + (10/210)×3.8 = 4.56`. The well-reviewed recipe wins, correctly.

As `v` grows, the score converges on the true average. As `v → 0`, it converges on the global mean. That's the whole point: unknown recipes are treated as average until proven otherwise.

**Recompute on:** every rating create/update/delete, for that recipe only. Recompute `C` nightly via a scheduled command — it barely moves, and recomputing it per-write is wasteful.

**Choosing `m`:** it's a tuning knob controlling how much evidence a recipe needs before its own average dominates. Document why you picked your value. "I chose 10 because the seeded catalogue averages fewer than 20 ratings per recipe; a higher `m` would flatten the ranking entirely" is a strong interview answer.

### Test cases

1. Recipe with 0 ratings → score equals global mean
2. Recipe with 1 five-star rating scores below a recipe with 200 ratings averaging 4.6
3. Recipe with 1000 ratings → score ≈ raw average (within 0.01)
4. Rating update changes the score; rating count doesn't double-increment
5. All recipes unrated → no division by zero

---

## 6. API endpoints

Sanctum token auth. `Public` = no auth required.

| Method | Path | Auth | Purpose |
|---|---|---|---|
| POST | `/api/register` | Public | |
| POST | `/api/login` | Public | |
| POST | `/api/logout` | Auth | |
| GET | `/api/recipes` | Public | List. Query: `cuisine`, `category`, `q`, `sort`, `page` |
| GET | `/api/recipes/{slug}` | Public | Detail with ingredients, stats, recent reviews |
| POST | `/api/recipes` | Auth | Create |
| PUT | `/api/recipes/{id}` | Auth + owner | Update |
| DELETE | `/api/recipes/{id}` | Auth + owner | Delete |
| GET | `/api/cuisines` | Public | Distinct cuisines with counts, for filter chips |
| PUT | `/api/recipes/{id}/rating` | Auth | Upsert this user's rating |
| DELETE | `/api/recipes/{id}/rating` | Auth | Remove rating |
| GET | `/api/meal-plan` | Auth | Active plan with items |
| POST | `/api/meal-plan/items` | Auth | Add recipe `{recipe_id, servings}` |
| PATCH | `/api/meal-plan/items/{id}` | Auth | Change servings |
| DELETE | `/api/meal-plan/items/{id}` | Auth | Remove |
| POST | `/api/meal-plan/shopping-list` | Auth | Generate (idempotent — regenerates, preserving check state where lines match) |
| GET | `/api/meal-plan/shopping-list` | Auth | Fetch current |
| PATCH | `/api/shopping-list/items/{id}` | Auth | Toggle `is_checked` |

Use Laravel API Resources for all responses. Don't return Eloquent models directly — same principle as the DTO boundary in your messenger bot package.

---

## 7. The importer

```bash
php artisan recipes:import --source=themealdb --limit=300
```

**Source:** TheMealDB (`https://www.themealdb.com/api/json/v1/1/`). Free, no key required for the test key `1`. Endpoints: `filter.php?a={area}` to list by area, `lookup.php?i={id}` for full detail.

**Quirks to handle:**
- Ingredients arrive as 20 numbered column pairs (`strIngredient1`..`strIngredient20`, `strMeasure1`..`strMeasure20`), not an array. Iterate and stop at the first empty pair.
- Empty fields are `""` or `" "`, not `null`. Trim and treat blank as null.
- Measures are free text: `"1 1/2 cups"`, `"to taste"`, `"Dash"`, `""`.
- Some recipes have a measure with no ingredient, or vice versa. Skip the pair.

**Rules:**
- Idempotent — upsert on `external_id`, so re-running doesn't duplicate.
- Log every ingredient the parser couldn't resolve to a file. Review that log and grow the alias table. This log is genuinely good evidence of engineering process — consider committing a sanitised version.
- Store the raw API payload in a JSON column or a `storage/imports/` file during development so you can re-parse without re-fetching.
- Attribute the source in `source_url` and credit TheMealDB in the README. Free API, but attribution is correct and costs nothing.

**Coverage note:** TheMealDB has `Indian` as an area but no `Bangladeshi`. That gap is the product rationale for user-contributed recipes — seed with what exists, then add 10–15 Bangladeshi recipes yourself as user-submitted content so the feature is demonstrably used, not just theoretically available.

---

## 8. Frontend

**Stack:** Vite + React 19 + TypeScript + TanStack Query + React Router + Tailwind + shadcn/ui.

**Use TypeScript.** I flagged it as your single most important skill gap in the job-search plan. This project is where you close it. Define the API response types explicitly rather than reaching for `any`.

**Use a component library.** Design is not what's being assessed. shadcn/ui gets you a competent-looking app in hours instead of days, and those days belong to the merge engine.

**Structure:**
```
src/
  api/          # typed fetch wrappers, one file per resource
  components/   # shared UI
  features/
    recipes/
    ratings/
    meal-plan/
    shopping-list/
  hooks/
  types/        # shared API types
  pages/
```

**State:** TanStack Query for all server state. No Redux, no Zustand — for this app they'd be over-engineering, and being able to say "I didn't add a state library because server cache was the only state I had" is a better signal than adding one.

---

## 9. Testing

| Layer | Tool | Coverage target |
|---|---|---|
| Merge engine | Pest, unit | Every case in §4. This is non-negotiable. |
| Ranking service | Pest, unit | Every case in §5. |
| Ingredient parser | Pest, unit | ~20 real strings from the import log |
| API endpoints | Pest, feature | Happy path + auth failure + ownership failure for each |
| Frontend | Vitest | Merge display formatting, servings stepper. Don't chase coverage here. |

**Write the merge engine tests before the merge engine.** Not for purity — because the test list *is* the specification, and you'll discover the dimension-mismatch problem while writing test 3 rather than while debugging a wrong shopping list in week 4.

---

## 10. CI/CD and deployment

**GitHub Actions** on push and PR:
```
- PHP 8.3, composer install
- vendor/bin/pint --test
- vendor/bin/phpstan analyse (level 6)
- vendor/bin/pest
- Node 22, npm ci, tsc --noEmit, vitest run, npm run build
```

You already have this shape working in Encapsula — lift the workflow file and adapt it. That's legitimate reuse, not cheating.

Note that CI runs tests and builds — it does **not** deploy. Deployment on shared hosting is a separate, semi-manual step (below).

### Deployment target: Hostinger shared, single origin

Both the API and the built SPA are served from the same domain. This is the simplest option and it eliminates CORS entirely — no preflight configuration, no `stateful_domains`, no cross-domain cookie rules.

**Layout:**
```
domain root  →  Laravel public/
                ├── index.php          ← Laravel front controller
                ├── .htaccess          ← Laravel rules FIRST, SPA fallback AFTER
                ├── index.html         ← React build output
                └── assets/            ← React JS/CSS bundles
```

Set the domain's document root to Laravel's `public/` directory in hPanel, then drop the contents of `dist/` alongside `index.php`.

**`.htaccess` — order matters.** Laravel's own rules must come first; the SPA fallback goes after:

```apache
# --- Laravel front controller (keep Laravel's existing block) ---
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/api/
RewriteRule ^ index.php [L]

# --- SPA fallback: anything not a real file/dir → index.html ---
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.html [L]
```

Without the fallback, refreshing on `/recipes/chicken-biryani` returns a 404 — the classic SPA deployment bug. Test a hard refresh on a deep route before calling deployment done.

**Do not let the SPA build overwrite Laravel's `.htaccess`.** Keep the merged version in version control and copy it deliberately, or the first `npm run build` upload will silently break every API route.

### Shared hosting constraints to design around

| Constraint | Consequence |
|---|---|
| **No long-running processes** (no supervisor) | Don't use queues at all. The ranking recompute is a handful of arithmetic ops — do it synchronously in the request. |
| **Cron is available** | Nightly global-mean recalculation via a cron entry calling `php artisan schedule:run` every minute, with the job registered in the scheduler. |
| **No Node on the server** | Build the SPA locally, upload `dist/`. No automated frontend deploys. |
| **SSH depends on plan tier** | Premium/Business have it; Single doesn't. **Check this before Week 4** — without SSH there's no server-side Composer, which makes Laravel deployment genuinely painful. |
| **hPanel Git integration** | Pulls from a GitHub repo on demand. Set this up once instead of uploading over FTP repeatedly. |

### Alternative if SSH turns out to be unavailable

Fall back to a split: SPA on Vercel or Netlify (free, auto-deploys from the repo), API on Hostinger under `api.yourdomain.com`. Costs you CORS configuration and Sanctum stateless token mode, but removes the server-side Composer requirement. Keep this in your back pocket rather than planning for it.

### Regardless of target

**Seed on deploy** so the live demo is never empty — a production seeder importing 200–300 recipes on first boot.

**Demo account** with credentials in the README, pre-loaded with an active meal plan, so a reviewer sees a populated shopping list in one click without registering.

**Budget half a day**, not two. If deployment eats more than that, take the Vercel fallback and move on.

---

## 11. Four-week build plan

~14 hours per week. Each week ends with something merged, tested, and pushed.

### Week 1 — Foundation and parsing (14h)

| | |
|---|---|
| Days 1–2 | Repo, Laravel install, MySQL (utf8mb4), all migrations, models, factories. Pint + PHPStan + Pest + GitHub Actions wired up on day one. |
| Days 3–5 | Ingredient parser, with tests. Unit vocabulary, alias table, normalisation, failure passthrough. |
| Days 6–7 | TheMealDB importer. Run it. Review the unresolved-ingredient log. Grow the alias table. |

**Done when:** `php artisan recipes:import` populates 200+ recipes with mostly-resolved ingredients, and CI is green.

### Week 2 — The core domain (14h)

| | |
|---|---|
| Days 1–3 | Merge engine. Tests first, all ten cases from §4. |
| Days 4–5 | Ranking service, Bayesian average, `recipe_stats` recompute, scheduled global-mean job. Tests. |
| Days 6–7 | API: auth, recipe list/detail with filters and sort, ratings upsert. Feature tests. |

**Done when:** both algorithms are fully tested and the API returns correctly ranked, filtered recipes. **This is the week that matters most.** If something slips, it should not be this.

### Week 3 — Frontend (14h)

| | |
|---|---|
| Days 1–2 | Vite + TS + Tailwind + shadcn scaffold, routing, typed API client, auth flow |
| Days 3–4 | Browse page: grid, cuisine filter chips, sort, pagination. Recipe detail page. |
| Days 5–6 | Rating control, review display. Meal plan: add, servings stepper, remove. |
| Day 7 | Shopping list view: merged lines, unmerged section, check-off. |

**Done when:** the full flow works end to end in a browser — browse, add to plan, generate list.

### Week 4 — Contribution, polish, ship (14h)

| | |
|---|---|
| Days 1–2 | Add-recipe form with dynamic ingredient rows, ownership rules, edit/delete |
| Day 3 | Add 10–15 Bangladeshi recipes through the UI as real user content |
| Days 4–5 | Deploy: document root, merged `.htaccess`, database, `dist/` upload, cron entry, production seeder, demo account. **Verify SSH access on your Hostinger plan before this week starts.** |
| Days 6–7 | README: demo GIF, architecture diagram, merge algorithm explanation. `docs/decisions.md`. Tag v1.0. |

**Done when:** it's live, the README is good, and you'd be happy sending the link cold to a hiring manager.

### Weeks 5–6 — Buffer

If you're on schedule, use them for: search, print view, better empty states, a written case study on your site, and posting it somewhere (r/laravel, r/reactjs, LinkedIn).

### Cut order if you fall behind

Cut from the bottom up. Never cut from the top.

1. Print view
2. Search
3. Review text (keep star ratings)
4. Check-off persistence (local state only)
5. Recipe editing (keep create + delete)
6. Pagination (cap the list at 50)

**Never cut:** the merge engine tests, the ranking algorithm, deployment, or the README. Those four are the entire portfolio value. A deployed app with fewer features and a great README beats a feature-complete app nobody can see.

---

## 12. README structure

The README is the deliverable a hiring manager actually reads. Budget real time for it.

```
# [Name]

One-line description.
[Live demo] · [Demo account credentials]

![demo gif]                          ← above everything. Non-negotiable.

## What it does
Three bullets, plain language.

## The interesting part
### Merging ingredients across recipes
The dimension problem, the alias problem, the "to taste"
problem, and why you chose to under-merge.

### Ranking recipes fairly
Why a raw average is wrong. The Bayesian formula.
How you chose m.

## Architecture
Diagram. Why the domain services are framework-independent.

## Stack
## Running locally
## Testing
## What's not built and why
   ← links to the out-of-scope list. Signals judgment.
## Credits
   TheMealDB attribution.
```

The "what's not built and why" section is unusual and worth including. It tells a reviewer you scoped deliberately rather than ran out of time — which is the difference between a junior and a senior read of the same unfinished feature list.
