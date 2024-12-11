#!/bin/bash
nohup php -S 192.168.0.178:8002 -t /home/pi/meteo_station > /home/pi/meteo_station/logs/webserver.log 2>&1 &
