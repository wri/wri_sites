#!/usr/bin/env bash
# restore-language-config.sh
#
# Bulk find/replace English UI labels with origlang equivalents across
# Drupal config YAML files. Reads phrase pairs from phrases-en-pt.tsv.
#
# Usage:
#   ./web/profiles/contrib/wri_sites/.ci/restore-language-config.sh              # English -> Original language (default)
#   ./web/profiles/contrib/wri_sites/.ci/restore-language-config.sh --reverse    # Original language -> English
#   ./web/profiles/contrib/wri_sites/.ci/restore-language-config.sh --dry-run    # Preview without writing

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../../../../.." && pwd)"
PHRASES_FILE="$REPO_ROOT/scripts/phrases-from-en.tsv"
CONFIG_DIR="$REPO_ROOT/config"
DELIMITER=" ||| "

REVERSE=false
DRY_RUN=false

for arg in "$@"; do
  case "$arg" in
    --reverse) REVERSE=true ;;
    --dry-run) DRY_RUN=true ;;
    *) echo "Unknown option: $arg"; exit 1 ;;
  esac
done

if [[ ! -f "$PHRASES_FILE" ]]; then
  echo "Error: phrases file not found: $PHRASES_FILE" >&2
  exit 1
fi

if [[ "$DRY_RUN" == "true" ]]; then
  echo "[dry-run] No files will be written."
fi

if [[ "$REVERSE" == "true" ]]; then
  echo "Direction: origlang -> English"
else
  echo "Direction: English -> origlang"
fi
echo ""

total_pairs=0
total_files=0

while IFS= read -r line || [[ -n "$line" ]]; do
  # Skip comments and blank lines
  [[ "$line" =~ ^[[:space:]]*# ]] && continue
  [[ -z "${line// }" ]] && continue

  # Split on the delimiter
  if [[ "$line" != *"$DELIMITER"* ]]; then
    echo "Warning: skipping malformed line: $line" >&2
    continue
  fi

  english="${line%%"$DELIMITER"*}"
  origlang="${line#*"$DELIMITER"}"

  if [[ "$REVERSE" == "true" ]]; then
    find_str="$origlang"
    replace_str="$english"
  else
    find_str="$english"
    replace_str="$origlang"
  fi

  changed=$(python3 -u - "$find_str" "$replace_str" "$CONFIG_DIR" "$DRY_RUN" << 'PYEOF'
import os, re, sys

find_str    = sys.argv[1]
replace_str = sys.argv[2]
config_dir  = sys.argv[3]
dry_run     = sys.argv[4] == "true"

# Anchor to end-of-line so e.g. "Asc" won't match inside "Ascendente"
pattern = re.compile(re.escape(find_str) + r"(?=\n|$)")

changed = 0
for root, dirs, files in os.walk(config_dir):
    # Skip translation override directories
    dirs[:] = sorted(d for d in dirs if d != "language")
    for fname in sorted(files):
        if not fname.endswith(".yml"):
            continue
        path = os.path.join(root, fname)
        with open(path, "r", encoding="utf-8") as f:
            content = f.read()
        if pattern.search(content):
            if not dry_run:
                new_content = pattern.sub(replace_str, content)
                with open(path, "w", encoding="utf-8") as f:
                    f.write(new_content)
            changed += 1
            # File paths to stderr so they print to terminal even inside $()
            sys.stderr.write(f"  {'[dry-run] ' if dry_run else ''}updated: {path}\n")

# Count to stdout for capture by the parent shell
print(changed)
PYEOF
  )

  if [[ "$changed" -gt 0 ]]; then
    echo "Replaced '$find_str'  ->  '$replace_str'  ($changed file(s))"
    total_files=$((total_files + changed))
  fi

  total_pairs=$((total_pairs + 1))
done < "$PHRASES_FILE"

echo ""
echo "Done. $total_pairs phrase pairs checked, $total_files file replacements made."
