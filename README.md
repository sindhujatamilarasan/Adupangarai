# Adupangarai (அடுப்பங்கரை)

A mobile-first kitchen app: **Pantry → What can I cook? → Meal plan → Smart grocery list → Shopping → Cook**,
with healthy options (estimated nutrition, protein tracking) and a voice-driven **AI mode**.

Track what's in your kitchen, see which recipes you can make right now, plan the week, get a grocery
list that subtracts what you already have, add purchases back to the pantry, and deduct ingredients
when you cook.

## Run it

```bash
docker compose up -d
```

- Web: http://localhost:5173
- API: http://localhost:8787 (the web dev server proxies `/api`)
- Postgres: localhost:5434 (`adupangarai` / `secret`)

The first start installs dependencies, creates `api/.env`, migrates and seeds. Seeding is safe to
re-run.

**Demo login:** `demo@adupangarai.test` / `password`. The demo account comes with a stocked
kitchen (some items expiring or low) and meals planned for the next few days.

### Tests and lint

```bash
cd api && php artisan test        # uses the adupangarai_test database in the compose Postgres
cd api && ./vendor/bin/pint --test
cd web && npm run lint && npm run build
```

Running the API tests from the host needs PHP 8.2+ with `pdo_pgsql`, plus the `db` service running.

## AI mode (free)

- **Voice.** The browser's built-in speech recognition turns speech into text, in English or Tamil. It works in Chrome, Edge and Android.
- **AI drafts only.** The AI only turns text into drafts: purchases, a recipe, or a meal plan picked from your own recipes. The server checks every name, unit, recipe id and date, and nothing is saved until you confirm.
- **No AI for simple lists.** A list like "1 kg chicken, a dozen eggs and 2 litres milk" is read instantly by plain code (`Support/QuickParse.php`); the AI is only used for free-form text.
- **Nutrition.** Estimated per serving (by AI for your own recipes; built-in recipes ship with estimates). Health tags (high protein ≥ 15 g, low calorie ≤ 350 kcal, high fiber ≥ 6 g) are calculated in code from those numbers.

**AI provider:** any OpenAI-compatible API works. Configure it in `api/.env`:

| Option | Settings | Notes |
|---|---|---|
| Local Ollama (default) | `AI_BASE_URL=http://localhost:11435/v1`, `AI_MODEL=qwen2.5:3b` | Free, no key, runs in Docker. Slow on a CPU-only laptop (a recipe can take minutes). `qwen2.5:1.5b` is faster but less accurate. |
| Google Gemini (free tier) | `AI_BASE_URL=https://generativelanguage.googleapis.com/v1beta/openai`, `AI_API_KEY=<key from aistudio.google.com>`, `AI_MODEL=<a current Flash model>` | Fast. Free key. Check AI Studio for current free model names. |
| Groq (free tier) | `AI_BASE_URL=https://api.groq.com/openai/v1`, `AI_API_KEY=<key from console.groq.com>`, `AI_MODEL=<a current Llama model>` | Very fast. Free key. Check the Groq console for current model names. |

If you use a hosted provider, also remove the `AI_BASE_URL` override in `docker-compose.yml` (or set it in your shell) so the API container uses it.

## Stack

- **API:** Laravel 12 REST + Sanctum tokens, PostgreSQL 16
- **Web:** React 19 + TypeScript + Vite + Tailwind 4, React Router
- **Infra:** Docker Compose (`db`, `api`, `web`)

## Where the logic lives

All business rules run on the server, in small plain classes under `api/app/Support`. React only
displays the results.

| Concern | File |
|---|---|
| Unit conversion (g↔kg, ml↔L/cup/tbsp/tsp; never across dimensions) | `Support/Unit.php` |
| Serving scaling | `Support/Quantities.php` |
| Recipe ↔ pantry matching, match %, ranking, Use Soon | `Support/RecipeMatcher.php` |
| Meal requirement aggregation + grocery shortfall | `Support/GroceryCalculator.php` |
| Every pantry stock change (locked, never negative, logged) | `Support/PantryLedger.php` |
| Cooking deduction preview + apply | `Support/CookDeduction.php` |
| Expiry status (expired / ≤3 days / fresh) | `Support/ExpiryStatus.php` |
| Nutrition tags, daily totals, AI estimate validation | `Support/Nutrition.php` |
| Voice/AI drafts → checked rows (never saved directly) | `Support/AiDrafts.php`, `Support/QuickParse.php`, `Support/Ai.php` |

Key rules:

- **Stock never goes negative.** The database enforces `CHECK (quantity >= 0)`, and all changes go through `PantryLedger`, which records a transaction (`PURCHASE`, `ADD`, `COOKED`, `ADJUSTMENT`, `EXPIRED` or `DISCARDED`).
- **Units stay comparable.** A recipe or pantry unit must be convertible to the ingredient's default unit, so recipes and pantry stock can always be compared.
- **Expired stock is never usable.** It doesn't count toward matching, grocery subtraction or cooking.
- **Optional ingredients** don't lower the match %, aren't added to the grocery list, and are deducted only if in stock.
- **Match status:**
  - *available* means nothing is short.
  - *almost* means at most 2 required ingredients are short and the match is ≥ 50%.
- **Grocery list:** needs from uncooked planned meals are added up (in base units) before subtracting stock. Piece and packet quantities round up.
- **Pantry updates need confirmation.** Purchases reach the pantry only after the user confirms, in one transaction. Cooking also runs in one transaction against locked rows.
- **Household isolation.** Every query is scoped to the user's household, and other households' records return 404.

## Data model

`households`, `users`, `ingredient_categories`, `ingredients` (unique normalised name), `pantry_items`
(unique per household and ingredient), `pantry_transactions`, `recipes` (`household_id` null means a
built-in, read-only recipe), `recipe_ingredients`, `recipe_steps`, `meal_plans`, `grocery_lists` (one
per household), `grocery_items`.

Units are a PHP enum, not a table: they are a fixed set with fixed conversion factors.

## API overview

All endpoints are under `/api` and need a Bearer token, except register and login.

```
POST register | login | logout      GET/PUT profile        GET dashboard
GET units | ingredient-categories   GET/POST ingredients
GET/POST pantry   GET/PATCH/DELETE pantry/{id}   POST pantry/{id}/adjust   POST pantry/discard-expired
GET/POST recipes  GET/PUT/DELETE recipes/{id}    GET/POST recipes/{id}/cook
POST recipes/{id}/nutrition (AI)   POST/DELETE recipes/{id}/photo   POST pantry/bulk   POST meal-plans/bulk
POST ai/pantry-parse | ai/recipe-parse | ai/meal-plan   (drafts only)
GET cook?filter=all|available|almost|use_soon&meal_type=&max_time=
GET/POST meal-plans?start=   PATCH/DELETE meal-plans/{id}
GET grocery   POST grocery/generate | grocery/items | grocery/clear-purchased | grocery/add-to-pantry
PATCH/DELETE grocery/items/{id}
```

## Images

- **Recipe photos:** you can upload photos for your own recipes (JPG/PNG/WebP, up to 5 MB). They are stored on Laravel's public disk and served from `/storage`.
- **No photo:** recipes without a photo show a meal-type illustration.
- **Ingredient icons:** ingredients and categories show emoji icons.
