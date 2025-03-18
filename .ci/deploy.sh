#!/bin/bash

# load common functions
script_path=$( cd "$(dirname "$0")" || exit 2; pwd -P )
# shellcheck source=scripts/util.sh
source "$script_path/util.sh"

if [ 2 -ne $# ]; then
    echo "usage: $0 <sitelist> <description>"
    exit 2
fi
sitelist="$1"
description="$2"

echo "Deploying to \"live\" for \"${sitelist}\"."
echo "${description}"

check_auth

# shellcheck disable=SC2016
run_parallel \
    'Deploying to live: ${site}' \
    '${TERMINUS} env:deploy "${site}".live --cc --note="${description}" --yes' \
    "${sitelist}"
