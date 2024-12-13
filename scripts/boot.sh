#!/bin/sh

until python /home/pi/meteo_station/python/esp8266.py; do
    echo "Server raspberry crashed with exit code $?.  Respawning.." >&2
    sleep 10
done

