#!/bin/bash

# Hardcoded to your exact Laravel root directory
DIR="/Users/satriyahandhawibowo/Documents/Skripsi-C14220311/Laravel"

echo "Launching Skripsi services from: $DIR"

# Window 1: PHP Artisan Serve
osascript -e "tell application \"Terminal\" to do script \"cd '$DIR' && php artisan serve\""

# Window 2: PHP Artisan Queue
osascript -e "tell application \"Terminal\" to do script \"cd '$DIR' && php artisan queue:work --timeout=3700 --tries=1\""

# Window 3: Python API Server
osascript -e "tell application \"Terminal\" to do script \"cd '$DIR' && python3 python_scripts/api_server.py\""