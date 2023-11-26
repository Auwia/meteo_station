import sqlite3
import datetime

db_file = 'meteo.db'

# Get the current date for the partition name
current_date = datetime.datetime.now().strftime('%Y-%m-%d')

# Create a new partition table for the current date
partition_table_name = 'humidities_{}'.format(current_date.replace("-", "_"))

# SQL statement to create the partition table
create_partition_table_sql = '''
CREATE TABLE IF NOT EXISTS {} (
    timestamp DATE,
    location VARCHAR(255),
    temperature REAL
);
'''.format(partition_table_name)

# Connect to the SQLite database
conn = sqlite3.connect(db_file)
cursor = conn.cursor()

# Execute the SQL statement to create the partition table
cursor.execute(create_partition_table_sql)

# Commit the changes and close the database connection
conn.commit()
conn.close()
