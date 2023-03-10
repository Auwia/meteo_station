// DHT Temperature & Humidity Sensor
// Unified Sensor Library Example
// Written by Tony DiCola for Adafruit Industries
// Released under an MIT license.

// REQUIRES the following Arduino libraries:
// - DHT Sensor Library: https://github.com/adafruit/DHT-sensor-library
// - Adafruit Unified Sensor Lib: https://github.com/adafruit/Adafruit_Sensor

#include <Adafruit_Sensor.h>
#include <DHT.h>
#include "DHT.h"
#include <DHT_U.h>
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <ESP8266WebServer.h>
#include <ESP8266mDNS.h>
#include <ESP8266HTTPUpdateServer.h>
#include <PubSubClient.h>

#include <Wire.h>
#include <Adafruit_BMP280.h>

// BMP280 sensor
#define BMP280_I2C_ADDRESS 0x76
Adafruit_BMP280 bmp280;

// DHT sensor
#define DHTPIN 2  // Digital pin connected to the DHT sensor
// Feather HUZZAH ESP8266 note: use pins 3, 4, 5, 12, 13 or 14 --
// Pin 15 can work but DHT must be disconnected during program upload.
// Uncomment the type of sensor in use:
//#define DHTTYPE    DHT11     // DHT 11
#define DHTTYPE DHT22  // DHT 22 (AM2302)
//#define DHTTYPE    DHT21     // DHT 21 (AM2301)
// See guide for details on sensor wiring and usage:
//   https://learn.adafruit.com/dht/overview
DHT_Unified dht(DHTPIN, DHTTYPE);
DHT dht2(DHTPIN, DHTTYPE);

// WIFI
const char* ssid = "UPC2522560";
const char* password_wifi = "7FxuryjpTtus";
WiFiClient clientWIFI;
WiFiServer server(23);

// MQTT client
const char* mqttServer = "192.168.0.178";
const int mqttPort = 1883;
const char* mqttUser = "";
const char* mqttPassword = "";
PubSubClient clientMQTT(clientWIFI);

// REMOTE UPDATE OTA
const char* host = "esp8266-webupdate";
ESP8266WebServer httpServer(80);
ESP8266HTTPUpdateServer httpUpdater;

uint32_t delayMS;

void setup() {
  Wire.begin();
  Serial.begin(9600);
  Serial.setTimeout(2000);
  // Initialize device.
  dht.begin();
  dht2.begin();
  bmp280.begin(BMP280_I2C_ADDRESS);
  Serial.println(F("DHTxx Unified Sensor Example"));
  // Print temperature sensor details.
  sensor_t sensor;
  dht.temperature().getSensor(&sensor);
  Serial.println(F("------------------------------------"));
  Serial.println(F("Temperature Sensor"));
  Serial.print(F("Sensor Type: "));
  Serial.println(sensor.name);
  Serial.print(F("Driver Ver:  "));
  Serial.println(sensor.version);
  Serial.print(F("Unique ID:   "));
  Serial.println(sensor.sensor_id);
  Serial.print(F("Max Value:   "));
  Serial.print(sensor.max_value);
  Serial.println(F("°C"));
  Serial.print(F("Min Value:   "));
  Serial.print(sensor.min_value);
  Serial.println(F("°C"));
  Serial.print(F("Resolution:  "));
  Serial.print(sensor.resolution);
  Serial.println(F("°C"));
  Serial.println(F("------------------------------------"));
  // Print humidity sensor details.
  dht.humidity().getSensor(&sensor);
  Serial.println(F("Humidity Sensor"));
  Serial.print(F("Sensor Type: "));
  Serial.println(sensor.name);
  Serial.print(F("Driver Ver:  "));
  Serial.println(sensor.version);
  Serial.print(F("Unique ID:   "));
  Serial.println(sensor.sensor_id);
  Serial.print(F("Max Value:   "));
  Serial.print(sensor.max_value);
  Serial.println(F("%"));
  Serial.print(F("Min Value:   "));
  Serial.print(sensor.min_value);
  Serial.println(F("%"));
  Serial.print(F("Resolution:  "));
  Serial.print(sensor.resolution);
  Serial.println(F("%"));
  Serial.println(F("------------------------------------"));
  // Set delay between sensor readings based on sensor details.
  delayMS = sensor.min_delay / 1000;

  // WIFI
  WiFi.begin(ssid, password_wifi);
  Serial.print("Connecting to WiFi.");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("ok.");
  Serial.println(WiFi.localIP());

  // MQTT client
  clientMQTT.setServer(mqttServer, mqttPort);
  clientMQTT.setCallback(callback);

  Serial.print("Connecting to MQTT.");
  while (!clientMQTT.connected()) {
    Serial.print(".");
    if (clientMQTT.connect("ESP8266Client", mqttUser, mqttPassword)) {
      Serial.println("ok.");
    } else {
      Serial.println("failed with state: ");
      Serial.print(clientMQTT.state());
      delay(2000);
    }
  }

  clientMQTT.publish("moisture", "Hello from ESP8266");
  clientMQTT.subscribe("moisture");
  clientMQTT.subscribe("pressure");

  // REMOTE UPDATE OTA
  MDNS.begin(host);
  httpUpdater.setup(&httpServer);
  httpServer.begin();
  MDNS.addService("http", "tcp", 80);
  Serial.printf("HTTPUpdateServer ready! Open http://%d.local/update in your browser\n", host);
}

void loop() {

  // MQTT client
  if (!clientMQTT.connected()) {
    reconnect();
  }
  clientMQTT.loop();

  // REMOTE UPDATE OTA
  httpServer.handleClient();

  delay(5000);

  //ESP.deepSleep(9e8); // 15 minutes
  // ESP.deepSleep(6e7); // 1 minute
}

void callback(char* topic, byte* payload, unsigned int length) {
  String message;
  Serial.print("Message arrived in topic: ");
  Serial.println(topic);
  Serial.print("Message:");
  for (int i = 0; i < length; i++) {
    Serial.print((char)payload[i]);
    message += (char)payload[i];
  }
  Serial.println("");
  delay(1000);

  if (String(topic).equals("moisture")) {
    Serial.print("moisture: ");
    int moisture = getMoisture();
    Serial.println(moisture);
  }
  if (String(topic).equals("pressure")) {
    Serial.print("pressure: ");
    float pressure = getPressure();
    Serial.println(pressure);
  }
  Serial.println();
  Serial.println("-----------------------");
}

int getMoisture() {
  int moisture = 10;
  Serial.println("Recording data of moisture.");
  // Get temperature event and print its value.
  sensors_event_t event;
  dht.temperature().getEvent(&event);
  if (isnan(event.temperature)) {
    Serial.println(F("Error reading temperature!"));
  } else {
    Serial.print(F("Temperature: "));
    Serial.print(event.temperature);
    Serial.println(F("°C"));
  }
  // Get humidity event and print its value.
  String temperature = String(event.temperature);
  dht.humidity().getEvent(&event);
  if (isnan(event.relative_humidity)) {
    Serial.println(F("Error reading humidity!"));
  } else {
    Serial.print(F("Humidity: "));
    Serial.print(event.relative_humidity);
    Serial.println(F("%"));
  }
  String message = temperature + "|" + String(event.relative_humidity);
  char charBuf[50];
  int length = message.length();
  message.toCharArray(charBuf, 50);
  boolean retained = true;
  Serial.println("Invio messaggio..." + message);
  clientMQTT.publish("moisture_resp", (byte*)message.c_str(), length, retained);
  Serial.println("...invio ok.");

  return moisture;
}

float getPressure() {
  float temperature = bmp280.readTemperature();
  Serial.print("Temperature = ");
  Serial.print(temperature);
  Serial.println(" *C");

  float pressure = bmp280.readPressure();
  Serial.print("Pressure = ");
  Serial.print(pressure);
  Serial.println(" Pa");

  float altitude = bmp280.readAltitude(1013.25);
  Serial.print("Approx altitude = ");
  Serial.print(altitude);  // this should be adjusted to your local forcase
  Serial.println(" m");
  Serial.println();

  char temperature_buffer[15];
  char altitude_buffer[15];
  char pressure_buffer[15];
  dtostrf(pressure, 6, 2, pressure_buffer);
  dtostrf(temperature, 6, 2, temperature_buffer);
  dtostrf(altitude, 6, 2, altitude_buffer);

  //String message = temperature_buffer + "|" + temperature_buffer + "|" + temperature_buffer;
  String message = String(pressure_buffer) + "|" + String(temperature_buffer) + "|" + String(altitude_buffer);

  char charBuf[50];
  int length = message.length();
  message.toCharArray(charBuf, 50);
  boolean retained = true;
  Serial.println("Invio messaggio..." + message);
  clientMQTT.publish("pressure_resp", (byte*)message.c_str(), length, retained);
  Serial.println("...invio ok.");

  return pressure;
}

void reconnect() {
  // Loop until we're reconnected
  while (!clientMQTT.connected()) {
    Serial.print("Attempting MQTT connection...");

    // Attempt to connect
    if (clientMQTT.connect("ESP8266Client", mqttUser, mqttPassword)) {
      Serial.println("connected");
      clientMQTT.subscribe("moisture");
      clientMQTT.subscribe("pressure");
    } else {
      Serial.print("failed, rc=");
      Serial.print(clientMQTT.state());
      Serial.println(" try again in 1 seconds");
      delay(1000);
    }
  }
}

String getTimestamp() {
  // TIME TIMESTAMP
  String timestamp = " ";
  WiFiClient wifiClient;
  HTTPClient clientHTTP;
  clientHTTP.begin(wifiClient, "http://weinzuhause.altervista.org/ws/getDateTime.php");
  int httpCode = clientHTTP.GET();
  if (httpCode > 0) {
    timestamp = clientHTTP.getString();
  } else {
    Serial.printf("[HTTP] GET... failed, error: %s\n", clientHTTP.errorToString(httpCode).c_str());
    timestamp = "ERROR";
  }
  clientHTTP.end();
  return timestamp;
}
