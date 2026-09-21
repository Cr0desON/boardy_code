import aiomysql
import os

DB_CONFIG = {
    'host': os.environ.get('DB_HOST', '127.0.0.1'),
    'port': int(os.environ.get('DB_PORT', 3306)),
    'user': os.environ.get('DB_USER', 'boardy'),
    'password': os.environ.get('DB_PASSWORD', ''),
    'db': os.environ.get('DB_NAME', 'boardy_api'),
    'charset': 'utf8mb4',
}

async def get_db():
    return await aiomysql.connect(**DB_CONFIG)

async def db_query(query: str, *args):
    conn = await get_db()
    async with conn.cursor(aiomysql.DictCursor) as cur:
        await cur.execute(query, args)
        rows = await cur.fetchall()
    conn.close()
    for row in rows:
        if 'created_at' in row and row['created_at']:
            row['created_at'] = str(row['created_at'])
        if 'updated_at' in row and row['updated_at']:
            row['updated_at'] = str(row['updated_at'])
    return rows

async def db_query_one(query: str, *args):
    conn = await get_db()
    async with conn.cursor(aiomysql.DictCursor) as cur:
        await cur.execute(query, args)
        row = await cur.fetchone()
    conn.close()
    if row:
        if 'created_at' in row and row['created_at']:
            row['created_at'] = str(row['created_at'])
        if 'updated_at' in row and row['updated_at']:
            row['updated_at'] = str(row['updated_at'])
    return row

async def db_execute(query: str, *args):
    conn = await get_db()
    async with conn.cursor() as cur:
        await cur.execute(query, args)
        await conn.commit()
    conn.close()

async def db_insert(query: str, *args):
    conn = await get_db()
    async with conn.cursor() as cur:
        await cur.execute(query, args)
        await conn.commit()
        last_id = cur.lastrowid
    conn.close()
    return last_id