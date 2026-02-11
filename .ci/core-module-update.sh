#!/bin/bash

# Helper function for looping updb and config command
updb_loop () {
  local UDB_OUTPUT
  local UDB_CHANGED
  local C_CHANGED
  local CHANGES_CURRENT=""
  local CHANGES_PREVIOUS=""

  # Update database and config. Continue running these until both result in no changes OR the changed config array is the same two loops in a row
  # so that this doesn't turn into an infinite loop.
  while true; do
    echo "Running drush updb -y..."
    UDB_OUTPUT="$(drush updb -y 2>&1)"

    # Check if database updates were made
    if echo "$UDB_OUTPUT" | grep "No pending updates"; then
       UDB_CHANGED=false
    else
      UDB_CHANGED=true
    fi

    # Checking config status and creating an array of changes
    echo "Checking config status"
    CHANGES_PREVIOUS="$CHANGES_CURRENT"
    CHANGES_CURRENT=$(drush config:status --format=json)

    # Setting a status of "false" if all three arrays (new, changes, deleted) are empty, "true" if changes have been detected.
    if [ "$CHANGES_CURRENT" = "[]" ]; then
      echo 'Config change set is empty.'
      C_CHANGED=false
    elif echo "$CHANGES_CURRENT" | jq -e '.new == [] and .changed == [] and .deleted == []' > /dev/null; then
      echo 'No config changes.'
      C_CHANGED=false
    else
      echo "Config changes detected"
      C_CHANGED=true

      # Check if config changes are the same twice in a row
      if [ "$CHANGES_CURRENT" != "$CHANGES_PREVIOUS"  ]; then
        echo "Running drush $1 -y"
        drush $1 -y
      else
        echo "Identical config changes detected. Please troubleshoot."
        exit
      fi
    fi

    echo "updb = $UDB_CHANGED and config = $C_CHANGED."

    # If both result in no changes, break loop and continue on
    if [ "$UDB_CHANGED" = false ] && [ "$C_CHANGED" = false ]; then
      echo "No remaining database or config changes."
      break
    fi

    echo "Changes detected, repeating cycle"
  done
}

set -eo pipefail

#
# This script begins the deployment steps for updating core and modules for deployment
#

# Check out main and pull the latest
git checkout main && git pull
# Robo install
printf "y\nlive\n" | robo install

# Running updb/cim
updb_loop cim

# Check out develop
git checkout develop
git pull

if [[ "$TERMINUS_SITE" == "wriflagship" ]]
then
  # Update the release and modules if this is flagship
  composer update wri/wri_sites -W --prefer-source
else
  # Update just the release if this is an IO
  composer require wri/wri_sites
  composer update wri/wri_sites -W --prefer-source
fi

# Running updb/cex
updb_loop cex

# Commit changes
git add .
git commit -am "Module updates and config changes for deployment of $(git describe --tags $(git rev-list --tags --max-count=1)) on $(date +'%Y-%m-%d')"
git push

# Ready to deploy!
echo "Time to test your module updates and log results in the spreadsheet."
