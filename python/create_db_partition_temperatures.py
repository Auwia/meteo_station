import mysql.connector
from mysql.connector import Error
from datetime import datetime, timedelta
import time

def create_db_partition():
    connection = None
    try:
        # Database connection
        connection = mysql.connector.connect(
            host='127.0.0.1',
            database='meteo',
            user='pi',
            password='pi_db_meteo')

        cursor = connection.cursor()

        # Calculate the timestamp for the midnight of the current and next day
        today = datetime.now()
        midnight_today = datetime(today.year, today.month, today.day)
        midnight_next_day = midnight_today + timedelta(days=1)
        partition_value = int(time.mktime(midnight_next_day.timetuple()))

        partition_name = f'p{midnight_today.strftime("%Y%m%d")}'
        readable_timestamp = today.strftime("%Y-%m-%d %H:%M:%S")

        # Check if today's partition already exists
        cursor.execute(f"SELECT PARTITION_NAME FROM INFORMATION_SCHEMA.PARTITIONS WHERE TABLE_SCHEMA = 'meteo' AND TABLE_NAME = 'temperatures' AND PARTITION_NAME = '{partition_name}'")
        partition_exists = cursor.fetchone()

        if partition_exists:
            print(f"{readable_timestamp} : TEMPERATURE: Partition {partition_name} already exists.")
            return

        # Check if pmax exists
        cursor.execute("SELECT PARTITION_NAME FROM INFORMATION_SCHEMA.PARTITIONS WHERE TABLE_SCHEMA = 'meteo' AND TABLE_NAME = 'temperatures' AND PARTITION_NAME = 'pmax'")
        pmax_exists = cursor.fetchone()

        if pmax_exists:
            # Add new partition and reorganize pmax
            alter_query = f"""
            ALTER TABLE temperatures 
            REORGANIZE PARTITION pmax INTO (
                PARTITION {partition_name} VALUES LESS THAN ({partition_value}),
                PARTITION pmax VALUES LESS THAN MAXVALUE
            );
            """
        else:
            # Simply add the new partition
            alter_query = f"ALTER TABLE temperatures ADD PARTITION (PARTITION {partition_name} VALUES LESS THAN ({partition_value}))"

        cursor.execute(alter_query)
        connection.commit()
        print(f"{readable_timestamp} : TEMPERATURE: Partition {partition_name} added successfully.")

    except Error as e:
        print(f"{readable_timestamp} : TEMPERATURE: Error: {e}")
    finally:
        if (connection and connection.is_connected()):
            cursor.close()
            connection.close()
            print(f"{readable_timestamp} : TEMPERATURE: MySQL connection is closed")

# Execute the function
readable_timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
create_db_partition()
