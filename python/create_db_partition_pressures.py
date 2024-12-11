import sqlite3
from datetime import datetime
from dateutil.relativedelta import relativedelta

def get_date_range(conn):
    cursor = conn.cursor()

    # Find the minimum and maximum timestamps in the pressures table
    cursor.execute("SELECT MIN(timestamp), MAX(timestamp) FROM pressures")
    min_timestamp, max_timestamp = cursor.fetchone()

    cursor.close()

    if min_timestamp is None or max_timestamp is None:
        raise Exception("No data found in the 'pressures' table.")

    # Convert timestamps to datetime objects
    min_date = datetime.strptime(min_timestamp, '%Y-%m-%d %H:%M:%S')
    max_date = datetime.strptime(max_timestamp, '%Y-%m-%d %H:%M:%S')

    return min_date, max_date

def create_partitions(conn):
    cursor = conn.cursor()

    # Get the minimum and maximum timestamps from the 'pressures' table
    min_date, max_date = get_date_range(conn)

    # Calculate the date range for partitions
    current_date = min_date
    while current_date <= max_date:
        # Generate the table name for the partition (e.g., pressures_2023_01)
        partition_name = "pressures_{}".format(current_date.strftime('%Y_%m'))

        # Create the partition table
        create_partition_sql = (
            "CREATE TABLE IF NOT EXISTS {} AS "
            "SELECT * FROM pressures "
            "WHERE strftime('%Y-%m', timestamp) = ?;"
        ).format(partition_name)
        cursor.execute(create_partition_sql, (current_date.strftime('%Y-%m'),))
        conn.commit()

        # Move to the next month
        current_date += relativedelta(months=1)

    cursor.close()

if __name__ == '__main__':
    try:
        connection = sqlite3.connect('meteo.db')
        create_partitions(connection)
        connection.close()
        print("Partitions created successfully.")
    except Exception as e:
        print("Error: " + str(e))
