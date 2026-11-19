#!/bin/bash
# Copies the latest pantheon_live_* tag from Pantheon onto GitHub's main branch.
# Run from a site's repo root.
# Usage: tag-live-release.sh [pantheon-site-name] [tag-message]
# Site name defaults to the repo folder name; message defaults to today's date.
set -euo pipefail

site=${1:-$(basename "$(git rev-parse --show-toplevel)")}
message=${2:-$(date +%Y-%m-%d)}

pantheon_url=$(terminus connection:info "$site.dev" --field=git_url)
# ls-remote lists annotated tags twice (tag and tag^{}); strip and dedupe.
tag=$(git ls-remote --tags "$pantheon_url" 'pantheon_live_*' \
  | awk '{print $2}' | sed 's#refs/tags/##; s#\^{}$##' | sort -uV | tail -1)

if [[ -z $tag ]]; then
  echo "ERROR: no pantheon_live_* tags found on Pantheon for $site." >&2
  exit 1
fi

git checkout main
git pull
git fetch origin --tags --quiet

if git rev-parse -q --verify "refs/tags/$tag" >/dev/null; then
  echo "ERROR: $tag already exists on GitHub/locally; nothing to do." >&2
  exit 1
fi

git tag -a "$tag" -m "$message"
echo "Created $tag on main ($(git rev-parse --short HEAD)) with message \"$message\"."

read -r -p "Push $tag to origin? [y/N] " answer
if [[ $answer == [yY] ]]; then
  git push origin "$tag"
else
  echo "Not pushed. Run: git push origin $tag"
fi
