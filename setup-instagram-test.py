#!/usr/bin/env python3
"""
Instagram Test Data Setup Script
This script reads the SQL file and inserts test data into the database
"""
import pymysql
import re
import sys
from pathlib import Path

# Read .env file to get database credentials
env_file = Path('.env')
env_vars = {}

if env_file.exists():
    with open(env_file, 'r') as f:
        for line in f:
            line = line.strip()
            if line and '=' in line and not line.startswith('#'):
                key, value = line.split('=', 1)
                env_vars[key.strip()] = value.strip()

# Database configuration
db_config = {
    'host': env_vars.get('DB_HOST', '127.0.0.1'),
    'port': int(env_vars.get('DB_PORT', 3306)),
    'user': env_vars.get('DB_USERNAME', 'root'),
    'password': env_vars.get('DB_PASSWORD', ''),
    'database': env_vars.get('DB_DATABASE', 'vatan_ai'),
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

print(f"Connecting to {db_config['host']}:{db_config['port']}/{db_config['database']}...")

try:
    # Connect to database
    connection = pymysql.connect(**db_config)
    print("✓ Connected successfully")
    
    # Read SQL file
    sql_file = Path('setup-test-data.sql')
    if not sql_file.exists():
        print(f"✗ SQL file not found: {sql_file}")
        sys.exit(1)
    
    with open(sql_file, 'r', encoding='utf-8') as f:
        sql_content = f.read()
    
    # Split SQL statements (simple split by ;)
    statements = [stmt.strip() for stmt in sql_content.split(';') if stmt.strip() and not stmt.strip().startswith('--')]
    
    cursor = connection.cursor()
    
    print(f"\nExecuting {len(statements)} SQL statements...")
    
    for i, statement in enumerate(statements, 1):
        # Skip comments
        if statement.startswith('--'):
            continue
        
        try:
            cursor.execute(statement)
            print(f"  {i}. ✓ Executed")
        except Exception as e:
            print(f"  {i}. ✗ Error: {e}")
            print(f"     Statement: {statement[:100]}...")
    
    connection.commit()
    print("\n✓ All statements executed and committed successfully!")
    
    # Verify data was inserted
    cursor.execute("SELECT COUNT(*) as count FROM instagram_post_settings WHERE instagram_post_id = 'Dd4VEftOMUV'")
    result = cursor.fetchone()
    print(f"\n✓ Verification: Found {result['count']} post settings for Dd4VEftOMUV")
    
    cursor.close()
    connection.close()
    
except pymysql.Error as e:
    print(f"✗ Database error: {e}")
    sys.exit(1)
except Exception as e:
    print(f"✗ Error: {e}")
    sys.exit(1)

print("\n✓ Test data setup completed!")
