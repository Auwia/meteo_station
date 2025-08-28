#!/usr/bin/python
import time
import paho.mqtt.publish as publish

publish.single("pressure", "1", hostname="192.168.0.178")
