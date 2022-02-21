#!/bin/bash
while :
do
    date
    echo "Starting discord-php bot"
    php -f "bot.php"
    echo "Script terminated, restarting in 5 seconds..."
    date
    sleep 5 
    clear
done
