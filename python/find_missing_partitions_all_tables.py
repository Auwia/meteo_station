import mysql.connector
from mysql.connector import Error
from datetime import datetime, timedelta, date as dt_date

def find_missing_partitions_for_table(cursor, table_name):
    print(f"\nChecking missing partitions for table: {table_name}")

    # Fetch all existing partitions for the table
    cursor.execute(f"""
        SELECT PARTITION_NAME, PARTITION_DESCRIPTION
        FROM INFORMATION_SCHEMA.PARTITIONS
        WHERE TABLE_SCHEMA = 'meteo' AND TABLE_NAME = '{table_name}';
    """)
    partitions = cursor.fetchall()

    # Extract partition dates from the fetched data
    existing_dates = set()
    max_partition = None
    for partition_name, partition_description in partitions:
        if partition_name.startswith('p') and partition_name != 'pmax':
            try:
                date_str = partition_name[1:]  # Skip the 'p' prefix
                date_obj = datetime.strptime(date_str, '%Y%m%d').date()
                existing_dates.add(date_obj)
            except ValueError:
                continue  # Skip non-date partitions
        elif partition_name == 'pmax':
            max_partition = partition_name

    # Determine the full range of dates
    today = dt_date.today()
    if existing_dates:
        start_date = min(existing_dates)
        end_date = max(existing_dates)
    else:
        start_date = today
        end_date = today

    # Include today's date in the range
    if today > end_date:
        end_date = today

    # Generate all expected dates in the range
    all_dates = set(start_date + timedelta(days=i) for i in range((end_date - start_date).days + 1))

    # Identify missing dates
    missing_dates = sorted(all_dates - existing_dates)

    # Generate SQL commands to add missing partitions
    sql_commands = []
    for missing_date in missing_dates:
        next_day = missing_date + timedelta(days=1)
        if max_partition:
            sql_command = (
                f"ALTER TABLE {table_name} REORGANIZE PARTITION {max_partition} INTO ("
                f"PARTITION p{missing_date.strftime('%Y%m%d')} VALUES LESS THAN (UNIX_TIMESTAMP('{next_day.strftime('%Y-%m-%d')} 00:00:00')), "
                f"PARTITION {max_partition} VALUES LESS THAN MAXVALUE);"
            )
        else:
            sql_command = (
                f"ALTER TABLE {table_name} "
                f"ADD PARTITION (PARTITION p{missing_date.strftime('%Y%m%d')} "
                f"VALUES LESS THAN (UNIX_TIMESTAMP('{next_day.strftime('%Y-%m-%d')} 00:00:00')));"
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

def find_missing_partitions():
    try:
        # Connect to MariaDB
        connection = mysql.connector.connect(
            host='127.0.0.1',  # Adjust if necessary
            database='meteo',  # Database name
            user='pi',         # Username
            password='pi_db_meteo'  # Password
        )

        cursor = connection.cursor()

        # Tables to check for missing partitions
        tables = ['humidities', 'temperatures', 'pressures']

        # Check each table for missing partitions
        for table in tables:
            find_missing_partitions_for_table(cursor, table)

    except Error as e:
        print(f"Error: {e}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

# Run the function to find missing partitions and generate SQL commands
if __name__ == "__main__":
    find_missing_partitions()
