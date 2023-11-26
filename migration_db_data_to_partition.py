import sqlite3
import datetime

db_file = 'meteo.db'

# Function to create a new partition table for a specific date
def create_partition_table(cursor, date):
    partition_table_name = 'temperatures_' + date.replace("-", "_")

    create_partition_table_sql = '''
    CREATE TABLE IF NOT EXISTS {} (
        timestamp DATE,
        location VARCHAR(255),
        temperature REAL
    );
    '''.format(partition_table_name)

    cursor.execute(create_partition_table_sql)

# Connect to the SQLite database
conn = sqlite3.connect(db_file)
cursor = conn.cursor()

# Get the list of existing partitions
cursor.execute("PRAGMA database_list;")
databases = cursor.fetchall()

# Iterate through each database to get the partition names
partition_names = []
for db in databases:
    db_name = db[1]
    alias_name = 'temp_' + db_name
    cursor.execute("ATTACH DATABASE ? AS ?;", (db_name, alias_name))
    cursor.execute("SELECT name FROM {}.sqlite_master WHERE type='table' AND name LIKE 'temperatures_%';".format(alias_name))
    partitions = cursor.fetchall()
    for partition in partitions:
        partition_names.append(partition[0])

# Get the list of existing dates in the partitions
existing_dates = [name.split("_")[1] for name in partition_names]

# Get the list of unique dates from the 'temperatures' table
cursor.execute("SELECT DISTINCT date(timestamp) FROM temperatures;")
unique_dates = [row[0] for row in cursor.fetchall() if row[0] is not None]

# Create partition tables for dates that do not have partitions yet
for date in unique_dates:
    if date not in existing_dates:
        create_partition_table(cursor, date)

# Move data from the 'temperatures' table to their respective partitions
for date in existing_dates:
    cursor.execute("INSERT INTO temperatures_{} SELECT * FROM temperatures WHERE date(timestamp) = ?;".format(date.replace('-', '_')), (date,))
    cursor.execute("DELETE FROM temperatures WHERE date(timestamp) = ?;", (date,))

# Commit the changes and close the database connection
conn.commit()
conn.close()
