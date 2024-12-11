import sqlite3
import datetime

db_file = 'meteo.db'

# Function to create a new partition table for a specific date
def create_partition_table(cursor, date):
    partition_table_name = 'humidities_' + date.replace("-", "_")

    create_partition_table_sql = '''
    CREATE TABLE IF NOT EXISTS {} (
        timestamp DATE,
        location VARCHAR(255),
        temperature REAL
    )
    '''.format(partition_table_name)

    cursor.execute(create_partition_table_sql)

# Connect to the SQLite database
conn = sqlite3.connect(db_file)
cursor = conn.cursor()

try:
    # Get the list of unique dates from the 'humidities' table
    cursor.execute("SELECT DISTINCT date(timestamp) FROM humidities;")
    unique_dates = [row[0] for row in cursor.fetchall() if row[0] is not None]

    # Create partition tables for dates that do not have partitions yet
    for date in unique_dates:
        create_partition_table(cursor, date)

    # Move data from the 'humidities' table to their respective partitions
    for date in unique_dates:
        cursor.execute("INSERT INTO humidities_{} SELECT * FROM humidities WHERE date(timestamp) = ?;".format(date.replace('-', '_')), (date,))
        print("Moved data for {} to partition humidities_{}".format(date, date.replace('-', '_')))
        print("Number of rows moved:", cursor.rowcount)

    # Commit the changes and close the database connection
    conn.commit()
    conn.close()

except sqlite3.Error as e:
    print("SQLite error:", e)

except Exception as ex:
    print("Error:", ex)
