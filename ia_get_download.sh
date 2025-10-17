#!/bin/bash

for i in $(seq 1 100); do echo "Running job $i"; php artisan wikistats:getcategorycount; done
