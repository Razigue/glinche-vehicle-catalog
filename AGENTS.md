# AGENTS.md

Guidelines for agents working on this repository. Product context, setup and technical choices are in `README.md`: read it first.

## Project

Technical recruitment exercise (Full Stack work-study position). The application syncs vehicles from the Glinche V1 partner API into the database and displays them with a brand filter and pagination. Code quality is the main evaluation criterion.

- `backend/`: Laravel 13, API only (no views, no web routes), Pest
- `frontend/`: Vue 3.5 SPA (Composition API, `<script setup>`), Vite, Tailwind CSS 4, Vitest
- `docker/` and `compose.yaml`: `app`, `nginx`, `mysql`, `node`, `scheduler` services

## Commands

```bash
docker compose up -d --build                      # start everything (migrations + first sync included)
docker compose exec app php artisan test          # back-end tests
docker compose exec app ./vendor/bin/pint         # PHP formatting, before every commit
docker compose exec node npm test                 # front-end tests
docker compose exec app php artisan vehicles:sync # manual sync
```

Without Docker: same commands from `backend/` or `frontend/`. Before committing, back-end and front-end tests must pass and Pint must report nothing.

## Conventions

- **Language**: code, names, commits, log messages and this file in English. README and UI text in French.
- **Comments kept to a strict minimum**: only for a non-obvious "why" (e.g. the JSON key order in `VehicleSynchronizer`).
- **No over-engineering**: no repository pattern, no Pinia, no Vue Router, no dependency without a real need.
- **Style**: follow the existing code. PHP formatted by Pint. Artisan commands use the `#[Signature]` and `#[Description]` attributes (Laravel 13 stub). Front end: no semicolons, single quotes.
- **Git flow**: `main`, `develop`, one `feature/*` branch per topic, merged into `develop` with `--no-ff`. Conventional commits (`feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`). Never push or merge into `main` without explicit approval.

## Secrets

- Glinche credentials go **only** in `backend/.env` (ignored by Git). `backend/.env.example` keeps empty values.
- Never commit the PDF brief: it contains the API password.
- Before each commit, check that no unexpected file is staged (e.g. a SQLite database created by a test command).

## Glinche V1 API: verified facts

- The OpenAPI spec is at `https://marketplace-dev.glinche-automobiles.com/docs/openapi.json` (the docs page is rendered in JavaScript).
- `POST /api/partners/login` returns a Sanctum token (`id|secret`), with no known expiry.
- `GET /api/partners/vehicles` returns **the whole stock in a single array, without pagination**. Do not add any in the Glinche client.
- `reference` identifies a listing (`external_id`). `prices.price` (public, VAT included) is optional and missing from current data; `merchantPrice` is the fallback. The `energy` and `gearbox` codes are documented enums.
- The test data contains known inconsistencies (listed in the README). They are displayed as is, without "fixing" them.

## Business rules to preserve

- **Mirror sync**: a vehicle missing from the feed or carrying a `deleteDate` is deleted. An API error changes nothing (the call happens before the transaction).
- **An invalid listing never blocks the others**: the mapper validates it (`ValidationException`), it is logged and skipped, and its last valid copy is kept. Any column constraint (length, numeric range) must therefore be validated in `VehicleMapper`: an SQL error inside the transaction would fail the whole sync.
- **Photos**: an invalid or non-HTTP(S) photo is skipped; it does not reject the vehicle.
- **Brands identified by slug** ("Citroën" = "Citroen"). A manufacturer with no possible slug is rejected.
- **Idempotence**: a sync with no changes counts vehicles as `unchanged`. MySQL reorders JSON keys, hence the non-strict comparison of `raw`.
- **Only one `vehicles:sync` at a time** (cache lock in the command).
- **Prices in cents, VAT included**, integer, converted without floats. A zero or missing price shows "Prix sur demande".
- **Server-side pagination at 6 vehicles per page** (`VehicleController::PER_PAGE`), stable sort brand → model → id.

## Known testing pitfalls

- Back-end tests run on in-memory SQLite, production on MySQL. Anything engine-dependent (JSON, collation, column sizes) must also be checked on MySQL via Docker.
- `Http::fake()` **stacks** stubs and the first match wins. For several syncs in one test, use `fakeGlincheFeed($feed1, $feed2, ...)` (sequence), not two successive calls.
- The client retries 5xx errors: call `Sleep::fake()` in the relevant tests.
- `glincheAdvert($overrides)` (in `tests/Pest.php`) builds a realistic listing. Start from it rather than writing a listing by hand.
- On the front end, automatic URL corrections (unknown brand, out-of-range page) go through `replace()` from `useUrlQuery`, never through direct assignment: otherwise the browser back button loops on the invalid URL.

## Check before announcing "done"

1. Back-end and front-end tests green, Pint clean.
2. Docker stack restarted: running `vehicles:sync` twice in a row gives `unchanged` everywhere.
3. UI checked in a browser, on desktop and mobile, including the error state.
4. README updated if behavior, commands or the number of tests change.

## Parallel work

Several sessions may modify this repository at the same time. Re-read a file before editing it. If `git status` shows changes you did not make, do not overwrite them: integrate them or report them.
