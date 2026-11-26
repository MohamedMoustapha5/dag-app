#!/bin/sh
# Kept for a cron line written before scripts/cron.php existed: the work is
# done in PHP, the hosting's shell lacking basic commands (dirname…).
exec php "${0%/*}/cron.php"
