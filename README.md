# Symfony Logistics CRM

A full-stack logistics CRM built with **Symfony 6.4 LTS** (backend) and **Vue 3 + Vuetify 3** (frontend), orchestrated via Docker Compose.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3, Symfony 6.4 LTS |
| Frontend | Vue 3, Vite, Vuetify 3, Pinia |
| Database | PostgreSQL 16 |
| Cache / Queue | Redis 7 |
| Web Server | Nginx 1.25 |
| Mail testing | Mailpit |
| Tests | PHPUnit, Cypress |
| CI/CD | GitHub Actions |

## Prerequisites

- Docker Desktop 24+
- Docker Compose v2
- Node.js 20+ (for local frontend dev without Docker)

## Quick Start

```bash
# 1. Clone and enter project
git clone <repo> symfony_logistics_crm
cd symfony_logistics_crm

# 2. Copy env file
cp .env.example .env

# 3. Start all services
docker compose up -d

# 4. Install Symfony dependencies & run migrations
docker compose exec php composer install
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# 5. Open in browser
# Frontend:  http://localhost:5173
# API:       http://localhost:8080/api
# Mailpit:   http://localhost:8025
```

## Project Structure

```
symfony_logistics_crm/
├── backend/          # Symfony 6.4 LTS application
├── frontend/         # Vue 3 + Vite + Vuetify 3 application
├── docker/           # Docker support files (nginx config)
├── cypress/          # E2E tests
├── .github/          # CI/CD workflows
├── docker-compose.yml
├── package.json      # NPM workspaces root
└── .env.example
```

## Services & Ports

| Service | URL |
|---|---|
| Nginx (API + Frontend) | http://localhost:8080 |
| Vue Dev Server | http://localhost:5173 |
| PostgreSQL | localhost:5432 |
| Redis | localhost:6379 |
| Mailpit Web UI | http://localhost:8025 |

## Parcel Workflow States

```
draft → picked_up → in_sorting_center → out_for_delivery → delivered
                                                         ↘ failed
```

## Development Commands

```bash
# Backend
docker compose exec php bin/console make:entity
docker compose exec php bin/console doctrine:migrations:diff
docker compose exec php bin/console doctrine:migrations:migrate
docker compose exec php bin/phpunit

# Frontend
docker compose exec frontend npm run dev
docker compose exec frontend npm run lint

# E2E Tests
npx cypress open
```
