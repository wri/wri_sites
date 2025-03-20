#!/bin/bash

set -eo pipefail

# load common functions
script_path=$( cd "$(dirname "$0")" || exit 2; pwd -P )
# shellcheck source=scripts/util.sh
source "$script_path/util.sh"

# Authenticate with Terminus
terminus -n auth:login --machine-token="$TERMINUS_TOKEN"

# Authenticate with AWS


# Wipe all content from the 'live-backup' environment
if ! terminus multidev:delete --delete-branch "$TERMINUS_SITE".live-backup; then
  echo "live-backup not found. Skipping this step."
fi

# Clone the live site's database/files to a `live-backup` environment
terminus multidev:create "$TERMINUS_SITE".live live-backup

# Start the process of cloning the live database into test
terminus backup:create "$TERMINUS_SITE".test && terminus env:clone-content "$TERMINUS_SITE".live test -y && terminus drush "$TERMINUS_SITE".test -- sapi-sc pantheon

# If this is Flagship, pull files on AWS from the live to the dev bucket
if [[ "$TERMINUS_SITE" == "wriflagship" ]]; then
  aws s3 sync s3://wriorg/d8/s3fs-public s3://wriorg-dev/d8/s3fs-public --acl public-read
fi
