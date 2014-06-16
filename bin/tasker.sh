#!/bin/bash

while getopts e: option
do
    case "${option}"
    in
        e) ENV=${OPTARG};;
    esac
done

while [ 1 -eq 1 ]; do
    php $(dirname $0)/tasker.php  --env $ENV --limit $TASKER_LIMIT
    sleep $TASKER_SLEEP
done &