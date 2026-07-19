#!/usr/bin/env bash

PINT_PATH="pint.json"
PINT_TMP_PATH="$PINT_PATH.tmp"

jq \
    --indent 4 \
    -S 'walk(if type == "array" then sort else . end)' "$PINT_PATH" \
    > "$PINT_TMP_PATH"
truncate -s -1 "$PINT_TMP_PATH"
node "$PINT_TMP_PATH"
mv "$PINT_TMP_PATH" "$PINT_PATH"
