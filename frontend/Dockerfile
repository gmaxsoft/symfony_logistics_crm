# =============================================================================
# Multi-stage Dockerfile for Vue 3 + Vite frontend (npm workspaces monorepo)
# Build from repository root: docker build -f frontend/Dockerfile .
# =============================================================================

# ─── Development Stage ────────────────────────────────────────────────────────
FROM node:22-alpine AS dev

WORKDIR /app

# Root lockfile + workspace manifests (lock lives at monorepo root)
COPY package.json package-lock.json ./
COPY frontend/package.json ./frontend/

RUN npm ci

WORKDIR /app/frontend

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

# ─── Build Stage ──────────────────────────────────────────────────────────────
FROM node:22-alpine AS builder

WORKDIR /app

COPY package.json package-lock.json ./
COPY frontend/package.json ./frontend/

RUN npm ci

COPY frontend/ ./frontend/

RUN npm run build --workspace=frontend

# ─── Production Stage (nginx serves static files) ─────────────────────────────
FROM nginx:1.25-alpine AS prod

COPY --from=builder /app/frontend/dist /usr/share/nginx/html
COPY frontend/docker/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
