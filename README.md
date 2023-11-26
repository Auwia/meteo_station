# meteo_station
Meteo Station

dependencies:
1. DHT sensor library (includes Adafruit sensor)
2. EspMQTTClient

run php server:
nohup php -S 192.168.0.178:8002 &

create daily partition with contrab:
~/grovy/pi $ crontab -l
0 0 * * * /usr/bin/python3 /home/pi/grovy/pi/create_db_partition_temperatures.py
0 0 * * * /usr/bin/python3 /home/pi/grovy/pi/create_db_partition_humidities.py
0 0 * * * /usr/bin/python3 /home/pi/grovy/pi/create_db_partition_pressures.py

migration existing data to table partitions:
python3 migration_db_data_to_partition_temperatures.py
python3 migration_db_data_to_partition_humidites.py
python3 migration_db_data_to_partition_pressures.py

python date utiliy:
pip3 install python-dateutil
