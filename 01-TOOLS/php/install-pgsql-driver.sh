#!/usr/bin/env bash
#
# Instala la extensión pdo_pgsql en el PHP del contenedor de desarrollo.
#
# Por qué existe
# --------------
# El PHP de este Codespace vive en /usr/local/php/<version> y viene compilado
# sin pdo_pgsql. Sin esa extensión, `php artisan migrate`, los seeders y los
# tests contra PostgreSQL fallan con "could not find driver", aunque el
# contenedor de 01-TOOLS/postgres/ esté sano.
#
# No se puede resolver con apt: este PHP no es el de los repositorios, así que
# un php8.4-pgsql de apt sería de otra ABI. Tampoco sirve compilar dentro de un
# contenedor php:8.4-cli, porque los contenedores de Docker aquí no tienen
# salida a Internet.
#
# La solución es la misma que ya usa la imagen: compilar la extensión contra
# este mismo PHP y dejarla en su extension_dir, junto a opcache.so, sodium.so y
# xdebug.so.
#
# Es idempotente: si pdo_pgsql ya está cargado, no hace nada.
#
# Uso (desde este directorio)
# ---------------------------
#   ./install-pgsql-driver.sh          # compila y activa para el PHP activo
#   ./install-pgsql-driver.sh --check  # solo comprueba, sin cambiar nada
#
set -euo pipefail

CHECK_ONLY=0
[[ "${1:-}" == "--check" ]] && CHECK_ONLY=1

say() { printf '%s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

# 1. ¿Ya está cargada? ----------------------------------------------------
if php -m | grep -qx pdo_pgsql; then
  say "pdo_pgsql ya está cargado con $(php -r 'echo PHP_VERSION;'). Nada que hacer."
  exit 0
fi

if [[ "$CHECK_ONLY" == "1" ]]; then
  die "pdo_pgsql NO está cargado. Ejecuta ./install-pgsql-driver.sh"
fi

# 2. Herramientas necesarias ----------------------------------------------
for tool in phpize php-config make gcc curl tar; do
  command -v "$tool" >/dev/null 2>&1 || die "falta '$tool' en el PATH"
done

# pg_config viene con las cabeceras de libpq (paquete libpq-dev).
command -v pg_config >/dev/null 2>&1 \
  || die "falta pg_config: instala las cabeceras de libpq, p. ej. 'sudo apt-get install -y libpq-dev'"

PHP_CONFIG="$(command -v php-config)"
PHP_VERSION="$("$PHP_CONFIG" --version)"
EXT_DIR="$("$PHP_CONFIG" --extension-dir)"
CONF_D="$(dirname "$EXT_DIR")/ini/conf.d"
PG_PREFIX="$(dirname "$(dirname "$(command -v pg_config)")")"

say "PHP           : $PHP_VERSION"
say "extension_dir : $EXT_DIR"
say "libpq         : $(pg_config --version)  ($PG_PREFIX)"

# El directorio de extensiones y el de conf.d son de grupo escribible en esta
# imagen, así que no hace falta sudo.
[[ -w "$EXT_DIR" ]] || die "sin permiso de escritura en $EXT_DIR"
mkdir -p "$CONF_D"
[[ -w "$CONF_D" ]] || die "sin permiso de escritura en $CONF_D"

# 3. Código fuente de la MISMA versión de PHP -----------------------------
# La extensión debe compilarse contra las cabeceras exactas de este PHP; mezclar
# versiones rompe la ABI (el intérprete rechazaría cargar el .so).
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

TARBALL="php-$PHP_VERSION.tar.gz"
URL="https://www.php.net/distributions/$TARBALL"

say "Descargando $URL ..."
curl -fsSL --retry 3 -o "$WORK/$TARBALL" "$URL" \
  || die "no se pudo descargar el código fuente de PHP $PHP_VERSION"

tar -xzf "$WORK/$TARBALL" -C "$WORK"

SRC="$WORK/php-$PHP_VERSION/ext/pdo_pgsql"
[[ -d "$SRC" ]] || die "no se encontró ext/pdo_pgsql dentro del tarball"

# 4. Compilar solo esa extensión ------------------------------------------
# No se recompila PHP entero: se usa el tarball solo como fuente de ext/pdo_pgsql.
cd "$SRC"
say "phpize ..."
phpize >/dev/null
say "configure ..."
./configure --with-pdo-pgsql="$PG_PREFIX" --with-php-config="$PHP_CONFIG" >/dev/null
say "make (esto tarda uno o dos minutos) ..."
make -j"$(nproc)" >/dev/null
say "make install ..."
make install >/dev/null

# 5. Activarla para todas las ejecuciones de PHP ---------------------------
# conf.d ya se escanea por defecto (ahí vive xdebug.ini), así que anotar la
# extensión aquí la deja activa sin tener que pasar -d en cada comando.
INI="$CONF_D/pdo_pgsql.ini"
printf 'extension=pdo_pgsql.so\n' > "$INI"
say "Activada en $INI"

# 6. Verificar ------------------------------------------------------------
php -m | grep -qx pdo_pgsql || die "pdo_pgsql no se carga después de instalarla"
say "OK — $(php -r 'echo "drivers PDO: ".implode(",", PDO::getAvailableDrivers());')"
say "Ya puedes ejecutar 'php artisan migrate'."