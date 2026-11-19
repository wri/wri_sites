#!/bin/bash
# Creates a YYYY-MM-DD-live-config branch from the latest pantheon_live_* tag.
# Run from a site's repo root.
set -euo pipefail

branch="$(date +%Y-%m-%d)-live-config"

git checkout main
git pull
# --force so a re-pointed remote tag doesn't abort the fetch.
git fetch origin --tags --force --quiet

highest=$(git tag -l 'pantheon_live_*' --sort=-v:refname | head -1)
newest=$(git for-each-ref --sort=-creatordate --count=1 --format='%(refname:short)' 'refs/tags/pantheon_live_*')

if [[ -z $highest ]]; then
  echo "ERROR: no pantheon_live_* tags; check out the last deployed commit on main manually." >&2
  exit 1
fi
if [[ $highest != "$newest" ]]; then
  echo "ERROR: highest tag ($highest) is not the most recent ($newest). Using $highest." >&2
  exit 1
fi

git checkout --quiet "$highest"
git checkout -b "$branch"
echo "Created $branch from $highest."
