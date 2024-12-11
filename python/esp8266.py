#!/usr/bin/python
# -*- coding: utf-8 -*-

import sys
import time
import paho.mqtt.client as mqtt
import mysql.connector
import datetime
import logging
import subprocess

Broker = '192.168.0.178'
logging.basicConfig(filename='/home/pi/grovy/pi/esp8266.log', level=logging.DEBUG)

# Update with your MySQL database credentials
mysql_config = {
    'user': 'pi',
    'password': 'pi_db_meteo',
    'host': '127.0.0.1',
    'database': 'meteo',
    'raise_on_warnings': True,
}

def on_connect(client, userdata, flags, rc):
    logging.debug('Connected with result code ' + str(rc))
    client.subscribe('moisture_resp')
    client.subscribe('pressure_resp')

def on_message(client, userdata, msg):
    try:
        logging.debug("Messaggio ricevuto")

        conn = mysql.connector.connect(**mysql_config)
        c = conn.cursor()

        message = str(msg.payload.decode("utf-8"))

        logging.debug("MSG DEBUG: " + msg.topic + ":" + message)

        if str(msg.topic) == 'moisture_resp':
            logging.debug(str(datetime.datetime.now()) + ' ' + 'Temperature & Humidity: ' + message)
            temperature, humidity = message.split('|')
            c.execute("INSERT INTO temperatures (timestamp, location, temperature) VALUES (NOW(), 'outside', %s)", (temperature,))
            c.execute("INSERT INTO humidities (timestamp, location, humidity) VALUES (NOW(), 'outside', %s)", (humidity,))

        if str(msg.topic) == 'pressure_resp':
            logging.debug(str(datetime.datetime.now()) + ' ' + 'Pressure & Temperature & Altitude: ' + message)
            pressure, temperature, altitude = message.split('|')
            c.execute("INSERT INTO pressures (timestamp, location, pressure, temperature, altitude) VALUES (NOW(), 'outside', %s, %s, %s)", (pressure, temperature, altitude))

        conn.commit()
        c.close()
        conn.close()
    except Exception as e:
        logging.debug(str(datetime.datetime.now()) + ' ' + 'Error: ' + str(e))

while True:
    try:
        logging.debug(str(datetime.datetime.now()) + ' ' + "Starting MQTT client loop")
        client = mqtt.Client()
        client.on_connect = on_connect
        client.on_message = on_message
        client.connect(Broker, 1883, 60)
        client.loop_forever()
    except Exception as e:
        logging.debug(str(datetime.datetime.now()) + ' ' + 'Error in MQTT client loop: ' + str(e))
        time.sleep(5)
