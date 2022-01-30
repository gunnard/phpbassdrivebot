#!/bin/bash
DIR="/home/section31/bassdrive_bot"
SERVICE="python3"
now=$(date)

if pgrep -x "$SERVICE" >/dev/null
then
    echo "***** SERVICE runnung $now ****" >> $DIR/out.log
else
    python3 bot.py &
    echo "***** RESTARTED SERVICE $now ****" >> $DIR/out.log
    # uncomment to start nginx if stopped
    # systemctl start nginx
    # mail  
fi
