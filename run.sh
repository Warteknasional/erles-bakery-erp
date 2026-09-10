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

# 1. Check Docker daemon
echo -e "${YELLOW}[1/5]${NC} Checking Docker daemon..."
if ! docker info > /dev/null 2>&1; then
  echo -e "${RED}✗ Docker daemon is not running. Please start Docker first.${NC}"
  exit 1
fi
echo -e "${GREEN}✓ Docker daemon is running${NC}"

# 2. Update submodules
echo -e "${YELLOW}[2/5]${NC} Updating git submodules..."
git submodule update --init --recursive
echo -e "${GREEN}✓ Submodules updated${NC}"

# 3. Build and start containers
echo -e "${YELLOW}[3/5]${NC} Building and starting containers..."
docker compose up --build -d
echo -e "${GREEN}✓ Containers started${NC}"

# 4. Wait for DB to be healthy and run migrations
echo -e "${YELLOW}[4/5]${NC} Waiting for database to be ready..."
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

echo -e "${YELLOW}[5/5]${NC} Running Laravel migrations..."
docker compose exec -T backend php artisan key:generate --force
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
echo -e "  ${BOLD}PostgreSQL:${NC}    localhost:${CYAN}5432${NC}"
echo ""
echo -e "  ${YELLOW}Useful commands:${NC}"
echo -e "  docker compose ps        - View container status"
echo -e "  docker compose logs -f   - Follow logs"
echo -e "  docker compose down      - Stop all services"
echo ""
