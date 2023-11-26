# meteo_station
Meteo Station

dependencies:
1. DHT sensor library (includes Adafruit sensor)
2. EspMQTTClient

run php server:
nohup php -S 192.168.0.178:8002 &

create daily partition with contrab:
~/grovy/pi $ crontab -l
0 0 * * * /usr/bin/python3 create_db_partition.py

migration existing data to table partitions:
python3 migration_db_data_to_partition.py
