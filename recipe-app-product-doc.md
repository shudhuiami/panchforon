# Product Documentation — Recipe & Meal Planner

**Working name:** TBD (see Naming, §11)
**Owner:** Ahmed Zobayer
**Status:** Planning
**Build window:** 4 weeks committed + 2 weeks buffer
**Purpose:** Portfolio flagship for remote international full-stack roles

---

## 1. One-liner

A community recipe platform where users browse recipes by cuisine, contribute their own, rate each other's, and turn a week's chosen meals into a single merged shopping list.

---

## 2. The problem

Two problems, stacked:

**Meal planning is annoying.** Picking five meals for the week means manually reconciling five ingredient lists into one shopping list. Three recipes each need onion — you have to notice that, add up the quantities, and write one line instead of three. People do this on paper, badly, and end up buying duplicates or forgetting things.

**Recipe sites are unreliable and regionally thin.** Anyone can publish a bad recipe. And global recipe databases have deep coverage of Western and East Asian cuisine but very little Bangladeshi or regional South Asian food.

## 3. The solution

- **Seeded catalogue.** Recipes imported from a public API so the app is useful on day one, not an empty shell.
- **User contributions.** Anyone can add a recipe, which is how the gaps — Bangladeshi and regional dishes the API doesn't have — get filled.
- **Community quality control.** Ratings and reviews push good recipes up and bad ones down, using a ranking that doesn't let a single five-star vote outrank a well-reviewed recipe.
- **Smart shopping list.** Pick your week's meals, get one list with quantities merged across recipes and grouped sensibly.

---

## 4. Target users

**Primary:** Home cooks who plan meals for the week and shop once. Comfortable with web apps, cooking for 2–5 people.

**Secondary:** People looking for regional cuisine — specifically the Bangladeshi and South Asian home cooks whose food is underrepresented in existing recipe databases.

This is a portfolio project, so the honest primary audience is a hiring manager clicking a link. Every product decision below is also a decision about what that person sees in the first thirty seconds.

---

## 5. Scope

### In scope for v1

| # | Feature | Why it's in |
|---|---|---|
| 1 | Browse recipes, sorted by quality ranking | Landing experience; showcases the ranking algorithm |
| 2 | Filter by cuisine/region and category | Cheap to build, immediately demonstrable, supports the regional angle |
| 3 | Recipe detail view | Table stakes |
| 4 | User registration and login | Required for ratings and meal plans |
| 5 | Add your own recipe | Proves write-path, validation, and the ingredient parser |
| 6 | Rate and review a recipe (1–5 stars, optional text) | Feeds the ranking algorithm |
| 7 | Build a weekly meal plan (select N recipes, adjust servings) | The bridge to the shopping list |
| 8 | Generate merged shopping list | The centrepiece feature |
| 9 | Check off shopping list items | Small, makes the list feel real in a demo |
| 10 | Seed catalogue from public recipe API | Makes the app look alive; showcases integration + data cleaning |

### Explicitly out of scope for v1

Cut now, mention in the README as "possible extensions" — this signals scoping discipline rather than omission:

- Nutrition and calorie tracking
- Recipe photos uploaded by users (use URLs only; file storage is a distraction)
- Comments threads (ratings + short review text is enough)
- Following users, social feeds, notifications
- Pantry tracking ("I already have onions")
- Recipe scaling beyond a simple servings multiplier
- Multi-week planning, meal calendars, drag-and-drop scheduling
- Mobile app (this is the *later* React Native companion, not v1)
- Admin moderation panel
- Internationalisation / multi-language UI

---

## 6. The three engineering showcases

These are the reason this project is worth building rather than a CRUD app. Each is a talking point you should be able to defend for ten minutes in an interview.

### 6.1 The ingredient merge engine

Combining ingredient quantities across multiple recipes is harder than summing numbers:

- **Unit dimensions differ.** 200g chicken + 1 cup chicken doesn't add. Mass, volume, and count are separate dimensions; only same-dimension quantities merge.
- **Unit conversion within a dimension.** 500g + 1kg = 1.5kg. Requires a conversion table and a canonical unit per dimension.
- **Ingredient identity is fuzzy.** "onion", "Onions", "1 large onion, chopped", "red onion" — which of these are the same shopping-list line? Requires name normalisation and an alias table.
- **Some quantities aren't quantities.** "salt to taste", "a pinch of turmeric", "oil for frying". These can't be summed and must be carried through as unmerged notes rather than dropped or faked.

Full specification in the developer doc, §4.

### 6.2 The weighted rating system

A naive average is wrong. A recipe with one 5-star rating would outrank a recipe with 200 ratings averaging 4.6 — obviously the wrong result.

v1 uses a Bayesian average, which pulls low-vote recipes toward the global mean until they've earned enough votes to stand on their own. Specification in the developer doc, §5.

This is the single best "show me you think about correctness, not just features" item in the project.

### 6.3 External API integration and data cleaning

Imported recipe data is messy: ingredients arrive as free text ("1 1/2 cups plain flour, sifted") in numbered columns rather than a structured list, units are inconsistent, and some fields are empty strings rather than nulls. The importer has to parse, normalise, and reject rather than trust.

The honest framing for an interview: *"I didn't control the input format, so I wrote a parser with a rejection path and logged what it couldn't handle."*

---

## 7. User stories

**Discovery**
- As a visitor, I can browse recipes without an account, so the app is useful before I commit.
- As a visitor, I can filter recipes by cuisine so I can find Bangladeshi or Italian food specifically.
- As a visitor, I see the best-rated recipes first, so I don't have to sift.

**Contribution**
- As a registered user, I can add a recipe with a title, cuisine, instructions, and a list of ingredients with quantities and units.
- As a registered user, I can edit and delete recipes I created.
- As a registered user, I cannot edit recipes I didn't create.

**Quality**
- As a registered user, I can rate any recipe 1–5 stars and optionally leave a short review.
- As a registered user, I can change my rating, but I can only have one rating per recipe.
- As a visitor, I can see a recipe's average rating and how many people rated it.

**Planning**
- As a registered user, I can add recipes to my current meal plan.
- As a registered user, I can set how many servings I want of each recipe, and quantities scale.
- As a registered user, I can remove a recipe from my plan.

**Shopping**
- As a registered user, I can generate a shopping list from my meal plan.
- The list merges the same ingredient across recipes into one line with a combined quantity.
- Ingredients that can't be merged (different units, "to taste") appear as separate clearly-labelled lines rather than being silently combined.
- I can check items off as I shop.

---

## 8. Key screens

| Screen | Contents |
|---|---|
| **Home / Browse** | Recipe grid, cuisine filter chips, category filter, sort control, search |
| **Recipe detail** | Image, cuisine + category, ingredient list, instructions, rating summary, review list, rate control, "Add to plan" button |
| **Add recipe** | Title, cuisine, category, servings, image URL, instructions, dynamic ingredient rows (quantity / unit / name) |
| **My meal plan** | Selected recipes with servings steppers, remove control, "Generate shopping list" |
| **Shopping list** | Merged items grouped by dimension or aisle, checkboxes, unmerged notes section, print view |
| **Auth** | Login, register |

Six screens. If you're behind schedule, the print view and search go first.

---

## 9. Success criteria

This is a portfolio piece, so success isn't user numbers.

**Must be true when it's done:**
- Deployed and publicly reachable at a real URL, with seeded data visible immediately
- A visitor can go from landing page to a generated shopping list in under 90 seconds without signing up (guest plan in local state, or a demo account with credentials in the README)
- Test suite covering the merge engine and the ranking calculation, green in CI
- README with a demo GIF, an architecture diagram, and a written explanation of the merge algorithm
- Commit history showing four-plus weeks of incremental work, not one dump
- You can explain and modify every line live

**Nice to have:**
- `docs/decisions.md` recording why Bayesian average over raw mean, why canonical-unit normalisation over pairwise conversion, why the alias table over fuzzy string matching
- A short written case study on ahmedzobayer.com linking to the repo

---

## 10. Risks

| Risk | Mitigation |
|---|---|
| Ingredient parsing eats the whole schedule | Timebox to Week 1. Anything unparseable falls back to raw-text passthrough on the shopping list. It's allowed to be imperfect if it's honest about what it couldn't parse. |
| Scope creep via "just one more feature" | The out-of-scope list in §5 is a contract. Additions go in the README as future work, not in the build. |
| Frontend takes longer than expected | Use a component library (shadcn/ui or Mantine) rather than hand-rolling. Design isn't what's being assessed here; the algorithms are. |
| API rate limits or the API disappearing | Import once into your own database as a seeder, don't call the API live at request time. This is also better architecture. |
| Four weeks proves optimistic | Buffer weeks 5–6 exist for exactly this. Cut order: print view → search → review text → check-off state persistence → user recipe editing. |

---

## 11. Naming

The name matters more than it seems — it's on the repo, the URL, and your CV. Avoid anything generic ("RecipeApp", "MealPlanner").

Candidates worth considering: **Panchforon** (the Bengali five-spice blend — regional, memorable, ties to the cuisine angle), **Mise** (from *mise en place* — short, clean, domain-appropriate), **Cookbook** variants, or something referencing the merge idea directly.

Pick one before the first commit so the repo name doesn't need changing later.
