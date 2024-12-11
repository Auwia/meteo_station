import mysql.connector
from mysql.connector import Error
from datetime import datetime, timedelta

def find_missing_partitions():
    try:
        # Connect to MariaDB
        connection = mysql.connector.connect(
            host='localhost',  # Adjust if necessary
            database='meteo',  # Database name
            user='pi',         # Username
            password='pi_db_meteo'  # Password
        )

        cursor = connection.cursor()

        # Fetch all existing partitions
        cursor.execute("""
            SELECT PARTITION_NAME, PARTITION_DESCRIPTION 
            FROM INFORMATION_SCHEMA.PARTITIONS 
            WHERE TABLE_SCHEMA = 'meteo' AND TABLE_NAME = 'humidities';
        """)
        partitions = cursor.fetchall()

        # Extract partition dates from the fetched data
        existing_dates = set()
        for partition_name, partition_description in partitions:
            try:
                date_str = partition_name[1:]  # Skip the 'p' prefix
                date_obj = datetime.strptime(date_str, '%Y%m%d')
                existing_dates.add(date_obj)
            except ValueError:
                continue  # Skip non-date partitions like pmax

        # Find the range of dates covered by the partitions
        start_date = min(existing_dates)
        end_date = max(existing_dates)

        # Generate all expected dates between start and end date
        all_dates = set(start_date + timedelta(days=i) for i in range((end_date - start_date).days + 1))

        # Find missing dates
        missing_dates = sorted(all_dates - existing_dates)

        # Generate SQL commands to add missing partitions
        sql_commands = []
        for missing_date in missing_dates:
            next_day = missing_date + timedelta(days=1)
            sql_command = (
                "ALTER TABLE humidities REORGANIZE PARTITION p" + next_day.strftime('%Y%m%d') + " INTO ("
                "PARTITION p" + missing_date.strftime('%Y%m%d') + " VALUES LESS THAN (UNIX_TIMESTAMP('" + next_day.strftime('%Y-%m-%d') + " 00:00:00')), "
                "PARTITION p" + next_day.strftime('%Y%m%d') + " VALUES LESS THAN (UNIX_TIMESTAMP('" + (next_day + timedelta(days=1)).strftime('%Y-%m-%d') + " 00:00:00')));"
            )
            sql_commands.append(sql_command)

        # Print missing dates and the SQL commands to fix them
        if missing_dates:
            print("Missing Partitions:")
            for date in missing_dates:
                print(date.strftime('%Y-%m-%d'))
            print("\nSQL Commands to Fix Missing Partitions:\n")
            for command in sql_commands:
                print(command)
        else:
            print("No missing partitions found.")

    except Error as e:
        print(f"Error: {e}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

# Run the function to find missing partitions and generate SQL commands
find_missing_partitions()

