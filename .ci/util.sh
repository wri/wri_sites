#!/bin/bash

# source this script as follows:
# script_path=$( cd "$(dirname "$0")" || exit 2; pwd -P )
# # shellcheck source=scripts/util.sh
# source "$script_path/util.sh"

script_path=$( cd "$(dirname "$0")" || exit 2; pwd -P )
AFF_BASE="$(dirname "$script_path")"
AFF_VENDOR="${AFF_BASE}/vendor"
if [ ! -d "${AFF_VENDOR}" ]; then
    echo "No vendor directory. Run composer install."
    exit 1
fi
TERMINUS="${AFF_VENDOR}/bin/terminus"

function check_auth() {
    ${TERMINUS} auth:login > /dev/null 2>&1
    ret="$?"
    if [ "$ret" -ne "0" ]; then
        echo "terminus auth failed"
        # exit 1
    fi
}

function run_parallel() {
    message="$1"
    cmd=$2
    sitelist=$3

    while read -r site; do
        eval "echo ${message}"
        eval "${cmd}" &
        pids=(${pids[@]} $!)
        names=(${names[@]} $site)
    done < "$sitelist"

    for (( i = 0; i < ${#pids[@]}; i++ )); do
        wait "${pids[$i]}" || failed=(${failed[@]} $i)
    done

    if [ "${#failed[@]}" -gt "0" ]; then
        echo "ERROR: ${#failed[@]} failed sites:"
        for i in "${failed[@]}"; do
            echo "  ${names[$i]}"
        done
        exit 1
    fi
}
