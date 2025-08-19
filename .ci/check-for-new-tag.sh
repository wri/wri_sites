#!/bin/bash
script_path=$( cd "$(dirname "$0")" || exit 2; pwd -P )
outdated_files=$(composer outdated)

# if latest tag is a descendant of the latest tag commit, return pass
if [[ "$outdated_files" == *"wri/wri_sites"* ]]; then
  echo "New tags found. Running pre-deploy.sh"
  source "$script_path/pre-deploy.sh"
else
  echo "No new tags found."
fi
