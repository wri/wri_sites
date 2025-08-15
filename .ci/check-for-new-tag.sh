#!/bin/bash

# check for latest commit used in composer.lock file
lockfile_commit=$(composer show "wri/wri_sites" | grep '^source' | head -n 1 | awk '{print $NF}')

# check out the main branch from wri_sites to get its latest commit
PACKAGE_PATH='web/profiles/contrib/wri_sites'
BRANCH_NAME='main'

cd "$PACKAGE_PATH"

git fetch origin

# check for latest tag
latest_tag=$(git tag --sort=-creatordate --merged "$BRANCH_NAME" | head -n 1)
# pull commit associated with latest tag
ltag_commit=$(git rev-list -n 1 "$latest_tag")

# if latest tag is a descendant of the latest tag commit, return pass
if [ $(git merge-base --is-ancestor $lockfile_commit $ltag_commit) 0 ]; then
  echo "New tags found. Running pre-deploy.sh"
  source "$script_path/pre-deploy.sh"
else
  echo "No new tags found."
fi

# move back to site folder
cd -


