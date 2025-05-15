#!/bin/bash

set -eo pipefail

# Authenticate with Terminus
terminus -n auth:login --machine-token="$TERMINUS_TOKEN"

# Wipe all content from the 'live-backup' environment
if ! terminus multidev:delete --delete-branch "$TERMINUS_SITE".live-backup; then
  echo "live-backup not found. Skipping this step."
fi

# Clone the live site's database/files to a `live-backup` environment
terminus multidev:create "$TERMINUS_SITE".live live-backup

# Start the process of cloning the live database into test
terminus backup:create "$TERMINUS_SITE".test && terminus env:clone-content "$TERMINUS_SITE".live test -y && terminus drush "$TERMINUS_SITE".test -- sapi-sc pantheon

# Check to see if there is live config to export
if [ -n "$(drush config:status --format=list)" ]; then
  echo "Config changes detected. Creating live config branch..."

  # Getting latest Pantheon tag
  latest_pantheon_tag=$(git ls-remote --refs --tags origin '*_live_*' | cut -d/ -f3 | sort -V | tail -n 1)
  echo "Latest live pantheon tag is $latest_pantheon_tag"
  git fetch origin "refs/tags/$latest_pantheon_tag"

  # Creating branch name variable
  BRANCH_NAME=$(date +'%Y-%m-%d'-'live-config')

  # Creating new live config branch from the most recent tag
  git checkout "tags/$latest_pantheon_tag" -b "$BRANCH_NAME"

  # Composer install and robo pull:config against live config branch
  composer install && robo pull:config

  # Add changes, push them up, open PR
  git add .
  git push origin "$BRANCH_NAME"
  gh pr create --base main --head "$BRANCH_NAME" --title "Live config from $(date +'%Y-%m-%d')."
else
  echo "No config changes detected. Skipping live config sync steps."
fi
