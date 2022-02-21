#!/bin/bash
while :
do
    date
    echo "Starting discord-php infobot"
    php -f "infobot.php"
    echo "Script terminated, restarting in 5 seconds..."
    date
    sleep 5 
    clear
done
