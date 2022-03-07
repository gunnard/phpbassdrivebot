#!/bin/bash
while :
do
    date >> out.log
    echo "Starting discord-php infobot" >> out.log
    php -f "infobot.php" &
    echo "Script terminated, restarting in 5 seconds..." >> out.log
    date >> out.log
    sleep 5 
    clear
done
