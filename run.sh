#!/usr/bin/env bash
set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color
BOLD='\033[1m'

echo -e "${CYAN}${BOLD}"
echo "  ╔═══════════════════════════════════════╗"
echo "  ║     🍞 Erles Bakery ERP Launcher      ║"
echo "  ╚═══════════════════════════════════════╝"
echo -e "${NC}"

COMMAND=${1:-dev}
ARG2=$2

setup_env_files() {
  echo -e "${YELLOW}Checking .env files...${NC}"
  for dir in . backend admin public; do
    if [ -f "$dir/.env.example" ] && [ ! -f "$dir/.env" ]; then
      cp "$dir/.env.example" "$dir/.env"
      echo -e "${GREEN}Created $dir/.env from .env.example${NC}"
    fi
  done
}

cmd_dev() {
  setup_env_files

  # 1. Check Docker daemon
  echo -e "${YELLOW}[1/6]${NC} Checking Docker daemon..."
  if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}✗ Docker daemon is not running. Please start Docker first.${NC}"
    exit 1
  fi
  echo -e "${GREEN}✓ Docker daemon is running${NC}"

  # 2. Update submodules
  echo -e "${YELLOW}[2/6]${NC} Updating git submodules..."
  git submodule update --init --recursive
  echo -e "${GREEN}✓ Submodules updated${NC}"

  # 3. Build and start containers
  echo -e "${YELLOW}[3/6]${NC} Building and starting containers..."
  docker compose up --build -d
  echo -e "${GREEN}✓ Containers started${NC}"

  # 4. Wait for DB to be healthy
  echo -e "${YELLOW}[4/6]${NC} Waiting for database to be ready..."
  RETRIES=30
  until docker compose exec -T db pg_isready -U postgres -d erles_bakery > /dev/null 2>&1 || [ $RETRIES -eq 0 ]; do
    echo -e "  Waiting for PostgreSQL... (${RETRIES} retries left)"
    RETRIES=$((RETRIES - 1))
    sleep 2
  done

  if [ $RETRIES -eq 0 ]; then
    echo -e "${RED}✗ Database did not become ready in time.${NC}"
    exit 1
  fi
  echo -e "${GREEN}✓ Database is ready${NC}"

  # 5. Fix Laravel permissions
  echo -e "${YELLOW}[5/6]${NC} Fixing Laravel permissions..."
  docker compose exec -T backend chown -R www-data:www-data storage bootstrap/cache || true
  docker compose exec -T backend chmod -R 775 storage bootstrap/cache || true
  echo -e "${GREEN}✓ Permissions fixed${NC}"

  # 6. Run Laravel migrations
  echo -e "${YELLOW}[6/6]${NC} Running Laravel setup & migrations..."
  if ! grep -q "^APP_KEY=base64:" backend/.env; then
    echo "Generating APP_KEY..."
    docker compose exec -T backend php artisan key:generate --force
  fi
  docker compose exec -T backend php artisan migrate --force
  echo -e "${GREEN}✓ Migrations completed${NC}"

  # Summary
  echo ""
  echo -e "${CYAN}${BOLD}═══════════════════════════════════════════${NC}"
  echo -e "${GREEN}${BOLD}  ✓ Erles Bakery ERP is up and running!${NC}"
  echo -e "${CYAN}${BOLD}═══════════════════════════════════════════${NC}"
  echo ""
  echo -e "  ${BOLD}Backend API:${NC}   http://localhost:${CYAN}8000${NC}/api"
  echo -e "  ${BOLD}Health Check:${NC}  http://localhost:${CYAN}8000${NC}/api/health"
  echo -e "  ${BOLD}Admin Panel:${NC}   http://localhost:${CYAN}5174${NC}"
  echo -e "  ${BOLD}Public Site:${NC}   http://localhost:${CYAN}5173${NC}"
  echo -e "  ${BOLD}PostgreSQL:${NC}    localhost:${CYAN}5432${NC} (internal)"
  echo ""
}

cmd_stop() {
  echo -e "${YELLOW}Stopping all services...${NC}"
  docker compose down
  echo -e "${GREEN}✓ Services stopped${NC}"
}

cmd_restart() {
  echo -e "${YELLOW}Restarting services...${NC}"
  cmd_stop
  cmd_dev
}

cmd_logs() {
  echo -e "${CYAN}Showing logs... (Press Ctrl+C to stop)${NC}"
  if [ -n "$ARG2" ]; then
    docker compose logs -f "$ARG2"
  else
    docker compose logs -f
  fi
}

cmd_fresh() {
  echo -e "${YELLOW}Running migrate:fresh --seed...${NC}"
  docker compose exec backend php artisan migrate:fresh --seed
  echo -e "${GREEN}✓ Database refreshed${NC}"
}

cmd_help() {
  echo -e "${BOLD}Usage:${NC} ./run.sh [command]"
  echo ""
  echo -e "${BOLD}Commands:${NC}"
  echo -e "  ${CYAN}dev${NC}       - (Default) Build and start all services, wait for DB, and migrate"
  echo -e "  ${CYAN}stop${NC}      - Stop and remove all containers"
  echo -e "  ${CYAN}restart${NC}   - Restart all services (stop, then dev)"
  echo -e "  ${CYAN}logs${NC}      - View logs for all services (or specify a service: ./run.sh logs backend)"
  echo -e "  ${CYAN}fresh${NC}     - Drop all tables, re-run all migrations, and run seeders"
  echo -e "  ${CYAN}help${NC}      - Show this help message"
  echo ""
}

case "$COMMAND" in
  dev)
    cmd_dev
    ;;
  stop|down)
    cmd_stop
    ;;
  restart)
    cmd_restart
    ;;
  logs)
    cmd_logs
    ;;
  fresh)
    cmd_fresh
    ;;
  help)
    cmd_help
    ;;
  *)
    echo -e "${RED}Unknown command: $COMMAND${NC}"
    cmd_help
    exit 1
    ;;
esac
