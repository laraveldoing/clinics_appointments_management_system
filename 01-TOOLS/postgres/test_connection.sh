#!/usr/bin/env bash
# Smoke-test de la conexión a PostgreSQL del contenedor de desarrollo.
#
# Comprueba que el contenedor responde y que las credenciales de `.env`
# autentican ejecutando un `SELECT 1` en el contenedor (no requiere psql
# instalado en el host). No recibe parámetros.
#
# Exit code: 0 si la conexión funciona, ≠ 0 con detalle en stderr.
#
# Usage:
#   ./test_connection.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_PATH="$SCRIPT_DIR/.env"

[ -f "$ENV_PATH" ] || { echo "ERROR: falta $ENV_PATH (copia .env.example)" >&2; exit 1; }

set -a
# shellcheck disable=SC1090
source "$ENV_PATH"
set +a

: "${POSTGRES_DB:?POSTGRES_DB no definido en $ENV_PATH}"
: "${POSTGRES_USER:?POSTGRES_USER no definido en $ENV_PATH}"
: "${POSTGRES_PASSWORD:?POSTGRES_PASSWORD no definido en $ENV_PATH}"

echo "Esperando a que el contenedor 'clinics_postgres' esté sano..."
for _ in $(seq 1 30); do
  status="$(docker inspect -f '{{.State.Health.Status}}' clinics_postgres 2>/dev/null || echo missing)"
  [ "$status" = "healthy" ] && break
  [ "$status" = "missing" ] && { echo "ERROR: el contenedor no existe. ¿Ejecutaste 'docker compose up -d'?" >&2; exit 1; }
  sleep 1
done
[ "${status:-}" = "healthy" ] || { echo "ERROR: el contenedor no llegó a 'healthy' (estado: ${status:-?})" >&2; exit 1; }

# SELECT 1 dentro del contenedor usando las credenciales del .env.
result="$(docker exec -e PGPASSWORD="$POSTGRES_PASSWORD" clinics_postgres \
  psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -tAc "SELECT 1;")"

[ "$result" = "1" ] || { echo "ERROR: la consulta devolvió '$result' (esperado '1')" >&2; exit 1; }

echo "OK — PostgreSQL responde y las credenciales son válidas ($POSTGRES_USER@$POSTGRES_DB)."