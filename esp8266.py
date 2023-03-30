!/usr/bin/python
# -*- coding: utf-8 -*-

import sys
import time
import paho.mqtt.client as mqtt
import paho.mqtt.publish as publish
import mysql.connector
import datetime
import logging
import subprocess
import sqlite3

Broker = '192.168.0.178'

logging.basicConfig(filename='/home/pi/grovy/pi/esp8266.log',level=logging.DEBUG)


def on_connect(client, userdata, flags, rc, ):
        logging.debug ('Connected with result code ' + str(rc))
        client.subscribe('moisture_resp')
        client.subscribe('pressure_resp')

# when receiving a mqtt message do this;
def on_message(client, userdata, msg):
        try:
                logging.debug("Messaggio ricevuto")

                conn = sqlite3.connect('/home/pi/grovy/pi/meteo.db')
                c = conn.cursor()

                message = str(msg.payload)

                logging.debug("MSG DEBUG: " + msg.topic + ":" + message)

                if str(msg.topic) == 'moisture_resp':
                        logging.debug (str(datetime.datetime.now()) + ' ' + 'Temperature & Humidity: ' + message)
                        (temperature, humidity) = message.split('|')
                        c.execute("insert into temperatures (timestamp, location, temperature) values (strftime('%Y-%m-%d %H:%M:%S','now', 'localtime'), 'outside', ?)", (temperature,))
                        c.execute("insert into humidities (timestamp, location, humidity) values (strftime('%Y-%m-%d %H:%M:%S','now', 'localtime'), 'outside', ?)", (humidity,))

                if str(msg.topic) == 'pressure_resp':
                        logging.debug (str(datetime.datetime.now()) + ' ' + 'Pressure & Temperature & Altitude: ' + message)
                        (pressure, temperature, altitude) = message.split('|')
                        c.execute("insert into pressures (timestamp, location, pressure, temperature, altitude) values (strftime('%Y-%m-%d %H:%M:%S','now', 'localtime'), 'outside', " + pressure + "," + temperature + "," + altitude + ")")

                conn.commit()
                c.close
                conn.close
        except:
                logging.debug (str(datetime.datetime.now()) + ' ' + 'Error: ' + sys.exc_info()[0])

while True:
        try:
                logging.debug (str(datetime.datetime.now()) + ' ' + "ciao")
                client = mqtt.Client()
                client.on_connect = on_connect
                client.on_message = on_message
                client.connect(Broker, 1883, 60)
                client.loop_forever()
        except:
                logging.debug (str(datetime.datetime.now()) + ' ' + 'Error mqtt')

        time.sleep(5)
