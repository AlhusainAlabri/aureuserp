#!/usr/bin/env bash
# Deploy AureusERP to production (VPS / shared hosting).
#
# Modes:
#   changed   Only git-modified / untracked files (default)
#   all       Full app sync (safe excludes; never overwrites .env / storage data)
#   folders   Sync specific folders/files you list
#
# Examples:
#   bash scripts/deploy-prod.sh
#   bash scripts/deploy-prod.sh changed
#   bash scripts/deploy-prod.sh all
#   bash scripts/deploy-prod.sh folders app plugins/webkul/document-archive
#   bash scripts/deploy-prod.sh folders database/migrations
#   bash scripts/deploy-prod.sh all --yes
#
# Flags:
#   --no-migrate    Skip php artisan migrate --force
#   --no-optimize   Skip optimize / Filament component cache
#   --no-shield     Skip php artisan shield:generate --all
#   --no-down       Skip maintenance mode (down/up)
#   --no-composer   Skip composer install on the server
#   --sync-vendor   Upload local vendor/ via rsync (skip remote composer)
#   --remote-composer  Force remote composer even when DEPLOY_SYNC_VENDOR=1
#   --build         Run npm ci && npm run build on the server after composer
#   --yes           Skip confirmation prompt
#
# Production notification / queue checklist (after deploy):
#   QUEUE_CONNECTION=database|redis
#   MAIL_* configured for real SMTP
#   NOTIFICATION_OPS_EMAIL=ops@example.com
#   Supervisor/systemd: php artisan queue:work --queue=default,mail,notifications
#   Cron: * * * * * php artisan schedule:run
#   Verify: php artisan notifications:report-daily --dry-run
#   Monitor: php artisan notifications:health (exit 1 when thresholds breached)
#
# Production safety (required in server .env):
#   ALLOW_DESTRUCTIVE_ARTISAN_COMMANDS=false
#   Never run on production: migrate:fresh, db:seed, erp:install --force, {plugin}:uninstall
#   Deploy runs migrate --force only (never db:seed). Recovery: temporarily set
#   ALLOW_DESTRUCTIVE_ARTISAN_COMMANDS=true — see auth.md — then set back to false.
#
# Test artifacts (never deployed — also removed on server after each deploy):
#   tests/, plugins/webkul/*/tests/, phpunit.xml, .env.testing.example, database/factories/
#
# Hostinger (PHP 8.2 default CLI, no hPanel access):
#   Set DEPLOY_HOSTINGER=1 in .env.deploy — auto-detects php83 CLI, syncs vendor
#   from your Mac, and patches public/.htaccess for web PHP 8.3.
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_ROOT"

REQUIRED_PHP_VERSION="${REQUIRED_PHP_VERSION:-8.3}"

MODE="changed"
DO_MIGRATE=1
DO_OPTIMIZE=1
DO_SHIELD=1
DO_DOWN=1
DO_BUILD=0
DO_COMPOSER=1
SYNC_VENDOR=0
ASSUME_YES=0
FOLDER_ARGS=()

while [[ $# -gt 0 ]]; do
  case "$1" in
    changed|all|folders)
      MODE="$1"
      shift
      if [[ "$MODE" == "folders" ]]; then
        while [[ $# -gt 0 && "$1" != --* ]]; do
          FOLDER_ARGS+=("$1")
          shift
        done
      fi
      ;;
    --no-migrate) DO_MIGRATE=0; shift ;;
    --no-optimize) DO_OPTIMIZE=0; shift ;;
    --no-shield) DO_SHIELD=0; shift ;;
    --no-down) DO_DOWN=0; shift ;;
    --no-composer) DO_COMPOSER=0; shift ;;
    --sync-vendor) SYNC_VENDOR=1; DO_COMPOSER=0; shift ;;
    --remote-composer) SYNC_VENDOR=0; DO_COMPOSER=1; shift ;;
    --build) DO_BUILD=1; shift ;;
    --yes|-y) ASSUME_YES=1; shift ;;
    -h|--help)
      sed -n '2,28p' "$0"
      exit 0
      ;;
    *)
      echo "Unknown argument: $1"
      echo "Usage: bash scripts/deploy-prod.sh [changed|all|folders <paths...>] [--no-migrate] [--no-optimize] [--no-shield] [--no-down] [--no-composer] [--sync-vendor] [--build] [--yes]"
      exit 1
      ;;
  esac
done

if [[ "$MODE" == "folders" && ${#FOLDER_ARGS[@]} -eq 0 ]]; then
  echo "folders mode requires at least one path, e.g.:"
  echo "  bash scripts/deploy-prod.sh folders app database/migrations"
  exit 1
fi

ENV_FILE="${DEPLOY_ENV_FILE:-$APP_ROOT/.env.deploy}"
if [[ "$ENV_FILE" != /* ]]; then
  ENV_FILE="$APP_ROOT/$ENV_FILE"
fi
if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing $ENV_FILE"
  echo "Copy: cp .env.deploy.example .env.deploy"
  echo "Or: DEPLOY_ENV_FILE=.env.deploy.zseeb bash scripts/deploy-prod.sh"
  exit 1
fi

# shellcheck disable=SC1090
set -a
source "$ENV_FILE"
set +a

: "${DEPLOY_HOST:?DEPLOY_HOST is required in .env.deploy (user@host)}"
: "${LARAVEL_PATH:?LARAVEL_PATH is required in .env.deploy (server path to app root)}"

LARAVEL_PATH="${LARAVEL_PATH%/}"
DEPLOY_PHP_BIN="${DEPLOY_PHP_BIN:-php}"
DEPLOY_COMPOSER_BIN="${DEPLOY_COMPOSER_BIN:-}"

if [[ "${DEPLOY_HOSTINGER:-0}" == "1" ]]; then
  DEPLOY_SYNC_VENDOR="${DEPLOY_SYNC_VENDOR:-1}"
  DEPLOY_WEB_PHP_HANDLER="${DEPLOY_WEB_PHP_HANDLER:-php84}"
fi

if [[ "${DEPLOY_SYNC_VENDOR:-0}" == "1" && $SYNC_VENDOR -eq 0 ]]; then
  SYNC_VENDOR=1
  DO_COMPOSER=0
fi

SSH_CONTROL_DIR="${HOME}/.ssh"
SSH_CONTROL_PATH="${SSH_CONTROL_DIR}/cm-aureuserp-%r@%h:%p"
mkdir -p "$SSH_CONTROL_DIR"
chmod 700 "$SSH_CONTROL_DIR" 2>/dev/null || true

SSH_OPTS=(-o StrictHostKeyChecking=accept-new)
SSH_OPTS+=(
  -o ControlMaster=auto
  -o "ControlPath=${SSH_CONTROL_PATH}"
  -o ControlPersist=600
)

build_rsync_ssh() {
  local -a parts=(ssh)
  local opt
  for opt in "${SSH_OPTS[@]}"; do
    parts+=("$opt")
  done
  printf '%s' "$(printf '%q ' "${parts[@]}")"
}

RSYNC_SSH="$(build_rsync_ssh)"

cleanup_ssh() {
  ssh "${SSH_OPTS[@]}" -O exit "$DEPLOY_HOST" 2>/dev/null || true
}
trap cleanup_ssh EXIT

if [[ -n "${DEPLOY_PORT:-}" ]]; then
  SSH_OPTS+=(-p "$DEPLOY_PORT")
  RSYNC_SSH="$(build_rsync_ssh)"
fi

if [[ -n "${DEPLOY_SSH_KEY:-}" ]]; then
  KEY_PATH="${DEPLOY_SSH_KEY/#\~/$HOME}"
  SSH_OPTS+=(-i "$KEY_PATH")
  RSYNC_SSH="$(build_rsync_ssh)"
fi

open_ssh_master() {
  if ssh "${SSH_OPTS[@]}" -O check "$DEPLOY_HOST" 2>/dev/null; then
    return 0
  fi

  echo "==> Opening SSH connection (enter password once)..."
  ssh "${SSH_OPTS[@]}" -fN "$DEPLOY_HOST"
}

version_gte() {
  local current="$1"
  local required="$2"
  local IFS=.
  read -r current_major current_minor _ <<<"$current"
  read -r required_major required_minor _ <<<"$required"

  if (( current_major > required_major )); then
    return 0
  fi

  if (( current_major == required_major && current_minor >= required_minor )); then
    return 0
  fi

  return 1
}

remote_php_version_for() {
  local bin="$1"
  ssh "${SSH_OPTS[@]}" "$DEPLOY_HOST" \
    "$(printf '%q' "$bin") -r 'echo PHP_MAJOR_VERSION.\".\".PHP_MINOR_VERSION;'" 2>/dev/null
}

remote_php_version() {
  remote_php_version_for "$DEPLOY_PHP_BIN"
}

probe_remote_php() {
  ssh "${SSH_OPTS[@]}" "$DEPLOY_HOST" "bash -s" -- "$REQUIRED_PHP_VERSION" "${DEPLOY_PHP_BIN:-php}" <<'REMOTE'
set -euo pipefail
required="$1"
preferred="$2"

version_gte() {
  local current="$1"
  local required="$2"
  local current_major current_minor required_major required_minor
  IFS=. read -r current_major current_minor _ <<<"$current"
  IFS=. read -r required_major required_minor _ <<<"$required"
  if (( current_major > required_major )); then
    return 0
  fi
  if (( current_major == required_major && current_minor >= required_minor )); then
    return 0
  fi
  return 1
}

php_version_for() {
  local bin="$1"
  if [[ -x "$bin" ]] || command -v "$bin" >/dev/null 2>&1; then
    "$bin" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || true
  fi
}

default_version="$(php_version_for php)"
if [[ -n "$default_version" ]]; then
  echo "DEFAULT:$default_version"
fi

candidates=(
  "$preferred"
  php84 php83 php8.4 php8.3
  /opt/alt/php84/usr/bin/php
  /opt/alt/php83/usr/bin/php
  /usr/bin/php84
  /usr/bin/php83
)

declare -A seen=()
for bin in "${candidates[@]}"; do
  [[ -n "$bin" ]] || continue
  [[ -n "${seen[$bin]+x}" ]] && continue
  seen[$bin]=1

  version="$(php_version_for "$bin")"
  [[ -n "$version" ]] || continue

  if version_gte "$version" "$required"; then
    echo "SELECTED:$bin:$version"
    exit 0
  fi
done

exit 1
REMOTE
}

discover_remote_php() {
  local line bin version
  while IFS= read -r line; do
    case "$line" in
      SELECTED:*)
        bin="${line#SELECTED:}"
        version="${bin##*:}"
        bin="${bin%:*}"
        DEPLOY_PHP_BIN="$bin"
        echo "Auto-detected PHP CLI: $DEPLOY_PHP_BIN ($version)"
        return 0
        ;;
    esac
  done < <(probe_remote_php)

  return 1
}

check_remote_php() {
  local remote_version line default_version=""

  open_ssh_master

  while IFS= read -r line; do
    case "$line" in
      DEFAULT:*)
        default_version="${line#DEFAULT:}"
        echo "Default remote php: $default_version"
        ;;
      SELECTED:*)
        DEPLOY_PHP_BIN="${line#SELECTED:}"
        remote_version="${DEPLOY_PHP_BIN##*:}"
        DEPLOY_PHP_BIN="${DEPLOY_PHP_BIN%:*}"
        ;;
    esac
  done < <(probe_remote_php || true)

  if [[ -z "${remote_version:-}" ]] || ! version_gte "$remote_version" "$REQUIRED_PHP_VERSION"; then
    echo "Probing Hostinger / alt PHP binaries (need >= $REQUIRED_PHP_VERSION)..."
    if ! discover_remote_php; then
      echo ""
      echo "ERROR: No PHP $REQUIRED_PHP_VERSION+ CLI found on the server."
      echo ""
      echo "Hostinger usually still has alt binaries — SSH and check:"
      echo "  php84 -v"
      echo "  /opt/alt/php84/usr/bin/php -v"
      echo ""
      echo "If none exist, this host cannot run Laravel 13 (needs PHP 8.3+ at runtime)."
      exit 1
    fi
    remote_version="$(remote_php_version)"
  fi

  echo "Remote PHP ($DEPLOY_PHP_BIN): $remote_version (required >= $REQUIRED_PHP_VERSION)"
}

remote_artisan() {
  printf '%s artisan' "$(printf '%q' "$DEPLOY_PHP_BIN")"
}

remote_composer_cmd() {
  if [[ -n "$DEPLOY_COMPOSER_BIN" ]]; then
    printf '%s' "$DEPLOY_COMPOSER_BIN"
    return
  fi

  printf '%s $(command -v composer 2>/dev/null || echo composer)' "$(printf '%q' "$DEPLOY_PHP_BIN")"
}

ensure_local_vendor() {
  if [[ $SYNC_VENDOR -ne 1 ]]; then
    return 0
  fi

  if [[ -d "$APP_ROOT/vendor" && -f "$APP_ROOT/vendor/autoload.php" ]]; then
    return 0
  fi

  echo "==> Building local vendor/ (production) for sync..."
  if ! command -v composer >/dev/null 2>&1; then
    echo "ERROR: composer not found locally. Install dependencies first:"
    echo "  composer install --no-dev --optimize-autoloader"
    exit 1
  fi

  composer install --no-dev --optimize-autoloader --no-interaction
}

ensure_hostinger_web_php_handler() {
  local handler="${DEPLOY_WEB_PHP_HANDLER:-}"
  if [[ -z "$handler" ]]; then
    return 0
  fi

  echo "==> Ensuring web PHP handler ($handler) in .htaccess files..."
  ssh "${SSH_OPTS[@]}" "$DEPLOY_HOST" "bash -s" -- "$LARAVEL_PATH" "$handler" <<'REMOTE'
set -euo pipefail
laravel_path="$1"
handler="$2"
begin="# BEGIN AureusERP PHP handler"
end="# END AureusERP PHP handler"

patch_htaccess() {
  local file="$1"
  [[ -f "$file" ]] || return 0

  local tmp
  tmp="$(mktemp)"

  awk -v begin="$begin" -v end="$end" '
    $0 == begin { skip=1; next }
    $0 == end { skip=0; next }
    !skip { print }
  ' "$file" >"$tmp"

  {
    echo "$begin"
    echo "<IfModule mime_module>"
    echo "  AddHandler application/x-httpd-${handler} .php"
    echo "</IfModule>"
    echo "$end"
    echo ""
    cat "$tmp"
  } >"$file"

  rm -f "$tmp"
  echo "  patched: $file"
}

patch_htaccess "$laravel_path/public/.htaccess"
patch_htaccess "$laravel_path/.htaccess"
REMOTE
}

# Paths never uploaded / never deleted on remote
RSYNC_EXCLUDES=(
  --exclude '.env'
  --exclude '.env.backup'
  --exclude '.env.production'
  --exclude '.env.testing'
  --exclude '.env.deploy'
  --exclude '.env.deploy.example'
  --exclude 'vendor/'
  --exclude 'node_modules/'
  --exclude '.git/'
  --exclude '.idea/'
  --exclude '.vscode/'
  --exclude '.fleet/'
  --exclude '.cursor/'
  --exclude '.claude/'
  --exclude 'storage/logs/'
  --exclude 'storage/debugbar/'
  --exclude 'storage/framework/cache/'
  --exclude 'storage/framework/sessions/'
  --exclude 'storage/framework/views/'
  --exclude 'storage/framework/testing/'
  --exclude 'storage/app/'
  --exclude 'storage/pail/'
  --exclude 'storage/*.key'
  --exclude 'bootstrap/cache/*.php'
  --exclude 'public/storage'
  --exclude 'public/hot'
  --exclude 'tests/'
  --exclude 'plugins/webkul/*/tests/'
  --exclude '/database/factories/'
  --exclude '.phpunit.result.cache'
  --exclude '.phpunit.cache/'
  --exclude 'phpunit.xml'
  --exclude '.env.testing.example'
  --exclude 'Homestead.json'
  --exclude 'Homestead.yaml'
  --exclude 'auth.json'
  --exclude 'auth.md'
  --exclude 'public/adminer.php'
  --exclude '.DS_Store'
  --exclude '*.log'
  --exclude '*.sql'
)

is_safe_rel_path() {
  local p="$1"
  [[ -n "$p" ]] || return 1
  [[ "$p" != /* ]] || return 1
  [[ "$p" != *".."* ]] || return 1
  case "$p" in
    .env|.env.*|vendor|vendor/*|node_modules|node_modules/*|storage/logs|storage/logs/*|storage/app|storage/app/*|tests|tests/*|phpunit.xml|.env.testing.example|database/factories|database/factories/*|auth.md|public/adminer.php|.git|.git/*)
      return 1
      ;;
  esac
  if [[ "$p" =~ ^plugins/webkul/[^/]+/tests(/|$) ]]; then
    return 1
  fi
  return 0
}

list_changed_files() {
  (
    cd "$APP_ROOT"
    git status --porcelain --untracked-files=all \
      | awk '{
          line=$0
          sub(/^.../, "", line)
          if (line ~ / -> /) {
            split(line, parts, / -> /)
            line=parts[2]
          }
          gsub(/^"|"$/, "", line)
          print line
        }' \
      | while read -r rel; do
          if is_safe_rel_path "$rel" && [[ -e "$APP_ROOT/$rel" || -L "$APP_ROOT/$rel" ]]; then
            if [[ -f "$APP_ROOT/$rel" ]]; then
              printf '%s\n' "$rel"
            fi
          fi
        done
  )
}

CHANGED_FILES=()
if [[ "$MODE" == "changed" ]]; then
  while IFS= read -r f; do
    [[ -n "$f" ]] && CHANGED_FILES+=("$f")
  done < <(list_changed_files | sort -u)

  if [[ ${#CHANGED_FILES[@]} -eq 0 ]]; then
    echo "No local git changes to deploy."
    echo "Tip: use 'all' or 'folders' if files are already committed."
    exit 0
  fi
fi

echo ""
echo "AureusERP deploy"
echo "  Mode:     $MODE"
echo "  Host:     $DEPLOY_HOST${DEPLOY_PORT:+ (port $DEPLOY_PORT)}"
echo "  Remote:   $LARAVEL_PATH"
echo "  Env:      $ENV_FILE"
echo "  Migrate:  $([[ $DO_MIGRATE -eq 1 ]] && echo yes || echo no)"
echo "  Optimize: $([[ $DO_OPTIMIZE -eq 1 ]] && echo yes || echo no)"
echo "  Shield:   $([[ $DO_SHIELD -eq 1 ]] && echo yes || echo no)"
echo "  Down/Up:  $([[ $DO_DOWN -eq 1 ]] && echo yes || echo no)"
echo "  Composer: $([[ $DO_COMPOSER -eq 1 ]] && echo remote || echo skip)"
echo "  Vendor:   $([[ $SYNC_VENDOR -eq 1 ]] && echo sync-from-local || echo no)"
echo "  PHP bin:  $DEPLOY_PHP_BIN (auto-detect if needed)"
echo "  Web PHP:  ${DEPLOY_WEB_PHP_HANDLER:-default}"
echo "  NPM build:$([[ $DO_BUILD -eq 1 ]] && echo yes || echo no)"
echo ""
check_remote_php
ensure_local_vendor
echo ""
echo "Never uploads: .env, vendor/ (unless --sync-vendor), storage data, tests/, phpunit.xml"
echo ""

case "$MODE" in
  changed)
    echo "Files (${#CHANGED_FILES[@]}):"
    printf '  - %s\n' "${CHANGED_FILES[@]}"
    ;;
  folders)
    echo "Paths:"
    printf '  - %s\n' "${FOLDER_ARGS[@]}"
    ;;
  all)
    echo "Sync: full app tree (with safe excludes; no --delete)"
    ;;
esac

echo ""
if [[ $ASSUME_YES -ne 1 ]]; then
  read -r -p "Continue deploy? [y/N] " confirm
  case "$confirm" in
    y|Y|yes|YES) ;;
    *)
      echo "Cancelled."
      exit 0
      ;;
  esac
fi

echo "==> Ensuring remote directory exists..."
ssh "${SSH_OPTS[@]}" "$DEPLOY_HOST" "mkdir -p $(printf '%q' "$LARAVEL_PATH")"

upload_changed() {
  local tmp
  tmp="$(mktemp)"
  printf '%s\n' "${CHANGED_FILES[@]}" >"$tmp"
  rsync -avz \
    -e "$RSYNC_SSH" \
    --files-from="$tmp" \
    "$APP_ROOT/" \
    "$DEPLOY_HOST:$LARAVEL_PATH/"
  rm -f "$tmp"
}

upload_folders() {
  local path remote_dir
  for path in "${FOLDER_ARGS[@]}"; do
    path="${path#/}"
    if ! is_safe_rel_path "$path"; then
      echo "Refusing unsafe path: $path"
      exit 1
    fi
    if [[ ! -e "$APP_ROOT/$path" ]]; then
      echo "Local path not found: $APP_ROOT/$path"
      exit 1
    fi
    if [[ -d "$APP_ROOT/$path" ]]; then
      echo "==> Sync folder: $path/"
      rsync -avz \
        -e "$RSYNC_SSH" \
        "${RSYNC_EXCLUDES[@]}" \
        "$APP_ROOT/$path/" \
        "$DEPLOY_HOST:$LARAVEL_PATH/$path/"
    else
      echo "==> Upload file: $path"
      remote_dir="$(dirname "$path")"
      ssh "${SSH_OPTS[@]}" "$DEPLOY_HOST" "mkdir -p $(printf '%q' "$LARAVEL_PATH/$remote_dir")"
      rsync -avz -e "$RSYNC_SSH" \
        "$APP_ROOT/$path" \
        "$DEPLOY_HOST:$LARAVEL_PATH/$path"
    fi
  done
}

upload_all() {
  rsync -avz \
    -e "$RSYNC_SSH" \
    "${RSYNC_EXCLUDES[@]}" \
    "$APP_ROOT/" \
    "$DEPLOY_HOST:$LARAVEL_PATH/"
}

case "$MODE" in
  changed) upload_changed ;;
  folders) upload_folders ;;
  all) upload_all ;;
esac

ensure_hostinger_web_php_handler

if [[ $SYNC_VENDOR -eq 1 ]]; then
  echo "==> Syncing local vendor/ to server (skipping remote composer)..."
  rsync -avz \
    -e "$RSYNC_SSH" \
    --delete \
    "$APP_ROOT/vendor/" \
    "$DEPLOY_HOST:$LARAVEL_PATH/vendor/"
fi

echo "==> Remote post-deploy steps..."
COMPOSER_FLAGS="${COMPOSER_INSTALL_FLAGS:---no-dev --optimize-autoloader --no-interaction}"
if [[ "${COMPOSER_IGNORE_PLATFORM:-0}" == "1" ]]; then
  COMPOSER_FLAGS+=" --ignore-platform-reqs"
fi

ARTISAN="$(remote_artisan)"
COMPOSER_CMD="$(remote_composer_cmd)"

REMOTE_CMDS="cd $(printf '%q' "$LARAVEL_PATH") && set -e"

# Stale bootstrap cache may reference dev-only providers (e.g. laravel/boost).
REMOTE_CMDS+=" && rm -f bootstrap/cache/*.php"

if [[ $DO_DOWN -eq 1 && -n "${DEPLOY_DOWN_SECRET:-}" ]]; then
  REMOTE_CMDS+=" && ${ARTISAN} down --secret=$(printf '%q' "$DEPLOY_DOWN_SECRET")"
fi

if [[ $DO_COMPOSER -eq 1 && -f "$APP_ROOT/composer.json" ]]; then
  REMOTE_CMDS+=" && ${COMPOSER_CMD} install ${COMPOSER_FLAGS}"
fi

if [[ $DO_BUILD -eq 1 && -f "$APP_ROOT/package.json" ]]; then
  REMOTE_CMDS+=" && if command -v npm >/dev/null 2>&1; then npm ci && npm run build; else echo 'npm not found — skip asset build'; fi"
fi

if [[ $DO_MIGRATE -eq 1 ]]; then
  REMOTE_CMDS+=" && ${ARTISAN} migrate --force"
fi

REMOTE_CMDS+=" && ${ARTISAN} permissions:sync-extensions"

if [[ $DO_SHIELD -eq 1 ]]; then
  REMOTE_CMDS+=" && ${ARTISAN} shield:generate --all --option=permissions --panel=admin --no-interaction"
fi

if [[ $DO_OPTIMIZE -eq 1 ]]; then
  REMOTE_CMDS+=" && ${ARTISAN} optimize"
  REMOTE_CMDS+=" && ${ARTISAN} filament:cache-components"
fi

REMOTE_CMDS+=" && ${ARTISAN} queue:restart"
REMOTE_CMDS+=" && if [ ! -L public/storage ]; then ${ARTISAN} storage:link || true; fi"

# Remove test artifacts if they exist from older deploys (never needed in production).
REMOTE_CMDS+=" && rm -rf tests phpunit.xml .phpunit.cache .phpunit.result.cache .env.testing.example database/factories"
REMOTE_CMDS+=" && find plugins/webkul -type d -name tests 2>/dev/null | while read -r test_dir; do rm -rf \"\$test_dir\"; done || true"

# Remove sensitive artifacts that must never exist on production.
REMOTE_CMDS+=" && rm -rf .git auth.md public/adminer.php"
REMOTE_CMDS+=" && if [ -f .env ]; then chmod 600 .env; fi"

if [[ $DO_DOWN -eq 1 && -n "${DEPLOY_DOWN_SECRET:-}" ]]; then
  REMOTE_CMDS+=" && ${ARTISAN} up"
fi

REMOTE_CMDS+=" && echo 'Remote steps OK'"

ssh "${SSH_OPTS[@]}" "$DEPLOY_HOST" "$REMOTE_CMDS"

echo ""
echo "==> Done. Deployed to $DEPLOY_HOST:$LARAVEL_PATH"
