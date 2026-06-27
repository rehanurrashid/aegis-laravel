#!/bin/bash
# Run after `php artisan migrate` to confirm all 69 tables exist
php artisan db:show --json | jq '.tables[].name' | sort
