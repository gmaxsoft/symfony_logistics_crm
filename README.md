# Symfony Logistics CRM

Full-stack logistics CRM: **Symfony 6.4 LTS** (REST API and parcel workflow) and **Vue 3 + Vuetify 3** (courier panel, Leaflet map). **npm workspaces** monorepo, orchestrated with **Docker Compose**.

Repository: [github.com/gmaxsoft/symfony_logistics_crm](https://github.com/gmaxsoft/symfony_logistics_crm)

## Tech stack

| Layer | Technologies |
|-------|----------------|
| Backend | PHP 8.3, Symfony 6.4 LTS, Doctrine ORM 3, Symfony Workflow (state machine) |
| Frontend | Vue 3, Vite, Vuetify 3, Pinia, Vue Router, Axios, Leaflet |
| Database | PostgreSQL 16 |
| Queue / cache | Redis 7, Symfony Messenger |
| Web | Nginx (reverse proxy to PHP-FPM and static assets) |
| Email (dev) | Mailpit |
| Tests | PHPUnit (unit + integration), Cypress (E2E) |
| Quality (backend) | PHPStan, Psalm, PHP CS Fixer |
| Quality (frontend) | ESLint, TypeScript (`vue-tsc`) |
| CI/CD | GitHub Actions (`.github/workflows/main.yml`) |

## Prerequisites

- Docker Desktop with Compose v2 (recommended for the full stack)
- **Node.js 20+** and npm 10+ — frontend from the host or `docker compose exec frontend`
- Optional: PHP 8.3 + Composer — backend without Docker

## Quick start (Docker)

```bash
git clone https://github.com/gmaxsoft/symfony_logistics_crm.git
cd symfony_logistics_crm

cp .env.example .env

docker compose up -d

# Backend: dependencies and migrations
docker compose exec php composer install
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# Frontend (workspace; lock file lives in the repository root)
npm ci

# URLs
# Vite dev server:  http://localhost:5173
# API via Nginx:    http://localhost:8080/api
# Mailpit:          http://localhost:8025
```

See `.env.example` for ports, `DATABASE_URL`, JWT, `VITE_API_BASE_URL`, and other variables.

## Monorepo layout

```
symfony_logistics_crm/
├── backend/                 # Symfony (src/, config/, migrations/, tests/)
├── frontend/                # Vue 3 + Vite (npm workspace)
├── docker/                  # Nginx and related assets
├── cypress/                 # E2E tests
├── .github/workflows/       # CI/CD
├── package.json             # workspaces: ["frontend"], root scripts + Cypress
├── package-lock.json        # Single lockfile for the whole npm tree
├── docker-compose.yml
└── .env / .env.example
```

## Services and ports

| Service | URL |
|---------|-----|
| Nginx (API + optional frontend build) | http://localhost:8080 |
| Vite (dev) | http://localhost:5173 |
| PostgreSQL | localhost:5432 |
| Redis | localhost:6379 |
| Mailpit (UI) | http://localhost:8025 |

See `docker-compose.yml` for extra services (e.g. Messenger worker).

## Parcel workflow (API)

Places: `draft` → `picked_up` → `in_sorting_center` → `out_for_delivery` → `delivered`, with a `failed` terminal state.

Transitions include: `pick_up`, `sort`, `deliver_start`, `confirm_delivery`, `mark_failed`. Workflow guards use the Expression language component (`symfony/expression-language`).

Main REST base path: `/api/parcels` (list, create, detail, available transitions, `PATCH` to apply a transition).

## Development commands

### Backend (inside `php` container, workdir `/var/www/html`)

```bash
docker compose exec php bin/console doctrine:migrations:diff
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/console cache:warmup --env=dev

# Tests and quality
docker compose exec php vendor/bin/phpunit
docker compose exec php composer test
docker compose exec php composer phpstan
docker compose exec php composer psalm
docker compose exec php composer cs-check   # PHP CS Fixer (dry run)
docker compose exec php composer cs-fix     # apply code style fixes
```

Integration tests need a database matching `backend/.env.test` (in Docker the host is usually `database`). PHPUnit bootstraps via `tests/bootstrap.php` (Dotenv / `.env` loading).

### Frontend

From the repository root (workspaces):

```bash
npm run dev          # Vite — frontend workspace
npm run build
npm run lint         # ESLint with --fix
```

From `frontend/`:

```bash
npm run lint:check   # ESLint, no write
npm run type-check   # vue-tsc --noEmit
```

### E2E (Cypress)

From the repository root (after `npm ci`):

```bash
npm run test:e2e       # headless
npm run test:e2e:open  # interactive
```

## CI/CD (GitHub Actions)

The `main.yml` workflow roughly:

- **Backend:** Composer (with `--no-scripts` in CI), PostgreSQL service, migrations, cache warmup for PHPStan, PHP CS Fixer, PHPStan, Psalm (some steps may use `continue-on-error`), PHPUnit with coverage.
- **Frontend:** `npm ci` from the **root** (monorepo), ESLint, type-check, production build.
- **Docker:** image build smoke test (selected branches).
- **E2E:** backend + frontend dev servers, then Cypress.

## License

Proprietary (see backend `composer.json`).
