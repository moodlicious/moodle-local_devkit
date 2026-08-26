#!/usr/bin/env bash
# Formats stub files in batches.
# Optionally --check to run in CI mode.
#
# Note that we don't use `./vendor/bin/pint phpstan/stubs/**/*.stub`
# in case there are too many stubs to pass to pint.

set -euo pipefail

readonly BATCH_SIZE=50

pint_args=()

case "${1:-}" in
    "")
        ;;
    --check)
        pint_args+=(--test)
        ;;
    *)
        echo "Usage: $0 [--check]" >&2
        exit 1
        ;;
esac

status=0

set +e
find phpstan/stubs -type f -name '*.stub' -print0 |
    xargs -0 -n "$BATCH_SIZE" ./vendor/bin/pint "${pint_args[@]}"
xargs_status=$?
set -e

# xargs returns 123 if any invocation exited with status 1-125.
if [ "$xargs_status" -ne 0 ]; then
    status=1
fi

exit "$status"
