#!/bin/bash
# deploy/install-cron.sh
# Installs the managed crontab entries of the Oceanovia application.
#
# Run as root:  bash deploy/install-cron.sh
#
# The installation is idempotent. The managed block of deploy/crontab is
# delimited by marker comments; the installer removes a previously installed
# block from the current crontab and appends the current one, so running it
# twice does not create a duplicate entry. Crontab entries that are not part
# of the managed block are preserved.
#
# The entries are installed for the application user, so the scheduler runs
# with the same identity as the application. The scheduled tasks themselves
# are defined in routes/console.php; the reconciliation task is registered
# with --dry-run and never writes.
#
# Environment:
#   APP_DIR      the application directory            (default /var/www/oceanovia)
#   APP_USER     the owner of the crontab             (default www-data)
#   PHP_BIN      the PHP executable for the scheduler  (default /usr/bin/php)
#   CRONTAB_CMD  how the crontab is read and written  (default "crontab -u <APP_USER>")
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/oceanovia}"
APP_USER="${APP_USER:-www-data}"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"
CRONTAB_CMD="${CRONTAB_CMD:-crontab -u $APP_USER}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATE="$SCRIPT_DIR/crontab"
MARKER_BEGIN="# BEGIN oceanovia managed entries"
MARKER_END="# END oceanovia managed entries"

if [ ! -f "$TEMPLATE" ]; then
    echo "The crontab template $TEMPLATE is missing." >&2
    exit 1
fi

# The paths are validated before anything is written. A crontab that points at
# an application directory that does not exist, or at a PHP binary that is not
# there, would fail on every single run and would still look installed.
if [ ! -d "$APP_DIR" ]; then
    echo "The application directory $APP_DIR does not exist." >&2
    exit 1
fi

if [ ! -f "$APP_DIR/artisan" ]; then
    echo "The application directory $APP_DIR does not contain the artisan file." >&2
    exit 1
fi

if [ ! -f "$PHP_BIN" ]; then
    echo "The PHP executable $PHP_BIN does not exist." >&2
    exit 1
fi

if [ ! -x "$PHP_BIN" ]; then
    echo "The PHP executable $PHP_BIN is not executable." >&2
    exit 1
fi

# The managed block of the template, with the placeholders resolved. The
# substitution is a bash string replacement and not a sed expression, so a
# path that contains a space or a special character cannot break the command.
template_block="$(awk -v begin="$MARKER_BEGIN" -v end="$MARKER_END" '
    $0 == begin { inside = 1 }
    inside { print }
    $0 == end { inside = 0 }
' "$TEMPLATE")"

managed="${template_block//@APP_DIR@/$APP_DIR}"
managed="${managed//@PHP_BIN@/$PHP_BIN}"

if [ -z "$(printf '%s' "$managed" | tr -d '[:space:]')" ]; then
    echo "The crontab template $TEMPLATE contains no managed block." >&2
    exit 1
fi

# The current crontab without a previously installed managed block.
current="$($CRONTAB_CMD -l 2>/dev/null || true)"

kept="$(printf '%s\n' "$current" | awk -v begin="$MARKER_BEGIN" -v end="$MARKER_END" '
    $0 == begin { inside = 1; next }
    $0 == end { inside = 0; next }
    inside { next }
    { print }
')"

# The entries that are not managed, followed by the managed block.
if ! {
    if [ -n "$(printf '%s' "$kept" | tr -d '[:space:]')" ]; then
        printf '%s\n' "$kept"
    fi
    printf '%s\n' "$managed"
} | $CRONTAB_CMD -; then
    echo "The cron entries could not be written with \"$CRONTAB_CMD\"." >&2
    exit 1
fi

# The installation is only successful when the entries are really in place.
installed="$($CRONTAB_CMD -l 2>/dev/null || true)"

case "$installed" in
    *"schedule:run"*) ;;
    *)
        echo "The cron entries were written but the scheduler entry is missing for $APP_USER." >&2
        exit 1
        ;;
esac

managed_blocks="$(printf '%s\n' "$installed" | grep -c "^$MARKER_BEGIN$" || true)"

if [ "$managed_blocks" != "1" ]; then
    echo "The crontab of $APP_USER holds $managed_blocks managed blocks instead of exactly one." >&2
    exit 1
fi

echo "The Oceanovia cron entries are installed for $APP_USER."
echo "Scheduler log: $APP_DIR/storage/logs/scheduler.log"
