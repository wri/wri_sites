#!/bin/bash

# load common functions
script_path=$( cd "$(dirname "$0")" || exit 2; pwd -P )
# shellcheck source=scripts/util.sh
source "$script_path/util.sh"

# check for latest commit
latest_commit=$(git rev-parse HEAD)
# check for latest tag
latest_tag=$(git tag --sort=-taggerdate | head -n 1)
# pull commit associated with latest tag
ltag_commit=$(git rev-list -n 1 "$latest_tag")

# if latest tag is a descendant of the latest tag commit, return pass
if [ $(git merge-base --is-ancestor "$ltag_commit" "$latest_commit") 0 ]; then
  echo "New tags found. Running pre-deploy.sh"
  ./pre-deploy.sh
else
  echo "No new tags found."
fi


