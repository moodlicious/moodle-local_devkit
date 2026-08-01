#!/usr/bin/env bash
# Formats stub files in batches.
#
# Note that we don't use `./vendor/bin/pint phpstan/stubs/**/*.stub`
# in case there are too many stubs to pass to pint.

set -euo pipefail

readonly BATCH_SIZE=50

find phpstan/stubs -type f -name '*.stub' -print0 |
xargs -0 -n "$BATCH_SIZE" ./vendor/bin/pint
