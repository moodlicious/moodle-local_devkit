#!/usr/bin/env bash
# Formats each stub.
#
# Note that we don't use `./vendor/bin/pint phpstan/stubs/**/*.stub`
# in case there are too many stubs to pass to pint.

set -euo pipefail

find phpstan/stubs -type f -name '*.stub' -print0 |
while IFS= read -r -d '' stub; do
    ./vendor/bin/pint "$stub"
done
