#!/bin/bash

script_path=$( cd "$(dirname "$0")" || exit 2; pwd -P )
# shellcheck source=scripts/util.sh
source "$script_path/util.sh"

if [ 2 -ne $# ]; then
    echo "usage: $0 <sitelist> <env>"
    exit 2
fi
sitelist="$1"
env="$2"

echo "Creating a backup on \"${env}\" for \"${sitelist}\"."

check_auth

# shellcheck disable=SC2016
run_parallel \
    'Creating a backup on ${env}: ${site}' \
    'terminus backup:create --yes "${site}"."${env}"' \
    "$sitelist"

run_parallel \
    'Next step ${env}: ${site}' \
    'terminus backup:create --yes "${site}"."${env}"' \
    "$sitelist"
