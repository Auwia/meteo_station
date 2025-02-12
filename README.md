# meteo_station
Meteo Station

dependencies:
1. DHT sensor library (includes Adafruit sensor)
2. EspMQTTClient

run php server:
nohup php -S 192.168.0.178:8002 &

installation:
---------------------------
sudo apt-get update && sudo apt-get upgrade
sudo apt-get intall mosquitto moquitto-clients
sudo apt-get install php
pip3 install python-dateutil
pip install paho-mqtt
pip install mysql-connector-python 

mosquitto conf.
---------------------------
sudo nano /etc/mosquitto/mosquitto.conf

# add following rows, save and exit:
listener 1883
allow_anonymous true

crontab configuration:
----------------------------
*/1 * * * * /home/pi/meteo_station/python/getTemp.py
0 0 * * * /usr/bin/nohup /bin/sh -c "/bin/sleep 15 && /usr/bin/python3 /home/pi/meteo_station/python/create_db_partition_temperatures.py" >> /home/pi/meteo_station/logs/partition_temperatures.log 2>&1 &
0 0 * * * /usr/bin/nohup /bin/sh -c "/bin/sleep 15 && /usr/bin/python3 /home/pi/meteo_station/python/create_db_partition_humidities.py" >> /home/pi/meteo_station/logs/partition_humidities.log 2>&1 &
0 0 * * * /usr/bin/nohup /bin/sh -c "/bin/sleep 15 && /usr/bin/python3 /home/pi/meteo_station/python/create_db_partition_pressures.py" >> /home/pi/meteo_station/logs/partition_pressures.log 2>&1 &
0 0 * * * /usr/bin/nohup /bin/sh -c "/bin/sleep 30 && /home/pi/meteo_station/scripts/backup_db.sh" >> /home/pi/meteo_station/logs/backup_db.log 2>&1 &
@reboot sleep 30 && /usr/bin/nohup /home/pi/meteo_station/scripts/nuovo_server_meteo_station.sh &> /home/pi/meteo_station/logs/webserver.log &
@reboot /usr/bin/nohup /home/pi/meteo_station/scripts/boot.sh &> /home/pi/meteo_station/logs/boot.log &
