# Panchforon — Recipe & Meal Planner

A community recipe platform where users browse recipes by cuisine, contribute their own, rate each other's, and turn a week's chosen meals into a single merged shopping list.

## What it does

- **Seeded & Regional Catalogue**: Seeded from public recipe APIs with deep support for South Asian and Bangladeshi regional cuisines.
- **Fair Community Ranking**: Bayesian average rating system that prevents recipes with single 5-star reviews from unfairly outranking established community favorites.
- **Smart Merged Shopping List**: Select recipes for your weekly meal plan, customize desired servings, and automatically generate an intelligent shopping list that converts units and merges ingredients across dishes.

---

## Demo Account Credentials

For reviewers and demo evaluations, the database seeds an active demo account pre-populated with an active meal plan and generated shopping list:

- **Email:** `demo@panchforon.com`
- **Password:** `password`

---

## The Engineering Showcases

### 1. Merging Ingredients Across Recipes (`MergeEngine`)
Combining ingredient quantities across multiple recipes is harder than simple addition:
- **Unit Dimensions:** `200g` of chicken and `2 pieces` of chicken cannot be combined. Mass, volume, and count are distinct dimensions; only quantities within the same dimension can be merged. We chose to **under-merge** rather than guess cross-dimensional conversions.
- **Unit Conversions:** `500g` + `1kg` = `1.5kg`. Within a dimension, units are converted to canonical units (`g`, `ml`, `piece`), combined with user-selected servings multipliers, and formatted with sensible rounding rules (counts round *up* to whole numbers; mass and volume round cleanly).
- **Identity & Aliases:** Deterministic alias resolution (`ingredient_aliases`) maps terms like "cilantro" and "coriander" or Bengali names ("peyaj" -> "onion", "ada" -> "ginger") to a single canonical ingredient record without using unpredictable fuzzy string matching.
- **Non-Quantifiable Items:** Items like "salt to taste" or "oil for frying" are preserved as separate unmerged lines with full attribution rather than inventing numbers.

### 2. Bayesian Quality Ranking (`RankingService`)
A naive average fails because 1 five-star rating (avg 5.0) would outrank 200 ratings averaging 4.6. We implement a Bayesian weighted average:

$$\text{Score} = \left(\frac{v}{v + m}\right) R + \left(\frac{m}{v + m}\right) C$$

- $v$: recipe rating count
- $R$: recipe mean rating
- $m$: minimum votes threshold ($m = 10$)
- $C$: global mean rating across all rated recipes

As ratings increase, the score converges toward the recipe's true mean; with few ratings, it remains anchored near the global mean. Recomputed synchronously on rating writes and synchronized nightly via Laravel's task scheduler (`recipes:recalculate-global-mean`).

### 3. External Recipe Importer & Data Cleaning (`MealDbImporter`)
- Consumes TheMealDB API, extracting ingredients from 20 numbered column pairs.
- Automatically handles free-text quantities, vulgar fractions (`½`, `¼`), mixed numbers, and ranges (`2-3`).
- Rejection path: Unresolved ingredients are logged to `storage/logs/unresolved_ingredients.log` for continuous alias vocabulary expansion.

---

## Architecture

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
│  │ MergeEngine       │  │  ← framework-independent domain services,
│  │ RankingService    │  │    DTO in / DTO out,
│  │ IngredientParser  │  │    100% unit-testable
│  └───────────────────┘  │
│                         │
│  ┌───────────────────┐  │
│  │ MealDbImporter    │  │  ← idempotent Artisan command
│  └───────────────────┘  │
└───────────┬─────────────┘
            │
┌───────────▼─────────────┐
│   MySQL 8 (utf8mb4)     │
└─────────────────────────┘
```

The core domain services (`MergeEngine`, `RankingService`, `IngredientParser`) are pure PHP classes independent of Eloquent. They consume and produce strongly-typed DTOs (`ParsedIngredient`, `ShoppingListLine`, `RecipePlanItemInput`), making them isolated, performant, and unit-testable.

---

## Stack

- **Backend:** Laravel 12 / 13 (PHP 8.3+)
- **Authentication:** Laravel Sanctum (API Tokens)
- **Database:** MySQL 8 with `utf8mb4` encoding
- **Testing:** Pest PHP (Unit & Feature suites)
- **Code Quality:** Larastan (PHPStan Level 6) & Laravel Pint

---

## Running Locally

### Prerequisites
- PHP 8.3+ with `pdo_mysql`, `mbstring`, `intl`, `bcmath`, `curl`
- Composer 2.x
- MySQL 8.x

### Setup

```bash
# Clone the repository
git clone <repo-url>
cd panchforon

# Install dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure MySQL credentials in .env, then migrate & seed
php artisan migrate:fresh --seed

# (Optional) Import external recipes from TheMealDB
php artisan recipes:import --source=themealdb --limit=50

# Recalculate global mean rating
php artisan recipes:recalculate-global-mean

# Serve the application
php artisan serve
```

---

## Testing & Quality Verification

```bash
# Run Pest test suite
vendor/bin/pest

# Run PHPStan static analysis (Level 6)
vendor/bin/phpstan analyse --no-progress

# Run Laravel Pint code style check
vendor/bin/pint --test
```

---

## API Endpoints

### Public Routes
- `POST /api/register` — User registration
- `POST /api/login` — Token authentication
- `GET /api/recipes` — Browse recipes (supports `cuisine`, `category`, `q`, `sort`, `page`)
- `GET /api/recipes/{slug}` — Recipe detail with ingredients, stats, and reviews
- `GET /api/cuisines` — Distinct cuisines with recipe counts
- `GET /api/categories` — Distinct categories with recipe counts

### Authenticated Routes (Sanctum Bearer Token)
- `POST /api/logout` — Revoke token
- `GET /api/user` — Authenticated user details
- `POST /api/recipes` — Create recipe with dynamic ingredients
- `PUT /api/recipes/{id}` — Update user's recipe
- `DELETE /api/recipes/{id}` — Delete user's recipe
- `PUT /api/recipes/{id}/rating` — Upsert user rating & review (1–5 stars)
- `DELETE /api/recipes/{id}/rating` — Remove rating
- `GET /api/meal-plan` — Get active meal plan with items
- `POST /api/meal-plan/items` — Add recipe to meal plan
- `PATCH /api/meal-plan/items/{id}` — Update servings
- `DELETE /api/meal-plan/items/{id}` — Remove item from meal plan
- `POST /api/meal-plan/shopping-list` — Generate merged shopping list (idempotent)
- `GET /api/meal-plan/shopping-list` — Retrieve active shopping list
- `PATCH /api/shopping-list/items/{id}` — Toggle item checked state

---

## Credits
- Recipe data integration powered by [TheMealDB](https://www.themealdb.com/).