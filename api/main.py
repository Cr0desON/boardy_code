from fastapi import FastAPI, Request
from datetime import datetime
from fastapi.middleware.cors import CORSMiddleware
import aiomysql
from contextlib import asynccontextmanager
import asyncio, json
import redis.asyncio as aioredis
from routers import comments, ws

DB_CONFIG = {
    'host': 'mysql',
    'port': 3306,
    'user': 'boardy',
    'password': '123abcD!',
    'db': 'boardy_api',
    'charset': 'utf8mb4',
}

async def get_db():
    return await aiomysql.connect(**DB_CONFIG)

async def db_execute(query, *args):
    conn = await get_db()
    async with conn.cursor() as cur:
        await cur.execute(query, args)
        await conn.commit()
    conn.close()

async def redis_subscriber():
    redis = await aioredis.from_url('redis://redis:6379')
    pubsub = redis.pubsub()
    await pubsub.subscribe('new_post', 'user.renamed')

    async for message in pubsub.listen():
        if message['type'] != 'message': continue

        channel = message['channel'].decode()
        data = json.loads(message['data'])

        if channel == 'new_post':
            await ws.manager.broadcast({
                'type': 'new_post', 'post': data
            })

        elif channel == 'user.renamed':
            await db_execute(
                'UPDATE comments SET author_name=%s WHERE author_id=%s',
                data['new_name'], data['id']
            )
            await ws.manager.broadcast({
                'type': 'user_renamed',
                'user_id': data['id'],
                'new_name': data['new_name']
            })

@asynccontextmanager
async def lifespan(app: FastAPI):
    task = asyncio.create_task(redis_subscriber())
    yield
    task.cancel()

app = FastAPI(title='Boardy API', version='0.5.0', lifespan=lifespan)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://localhost",
        "http://127.0.0.1",
    ],
    allow_credentials=True,
    allow_methods=["*"],  
    allow_headers=["*"],
)

app.include_router(comments.router)
app.include_router(ws.router)
 
@app.get('/api/status')
async def status():
    return {'status': 'ok', 'time': str(datetime.now())}
 
@app.get('/api/messages')
async def get_messages():
    conn = await get_db()
    async with conn.cursor(aiomysql.DictCursor) as cur:
        await cur.execute(
            'SELECT posts.body AS message, users.name, '
            'posts.created_at FROM posts '
            'JOIN users ON posts.author_id = users.id '
            'ORDER BY posts.created_at DESC'
        )
        messages = await cur.fetchall()
    conn.close()
    for m in messages:
        m['created_at'] = str(m['created_at'])
    return {'messages': messages, 'count': len(messages)}
 
@app.get('/api/users')
async def get_users():
    conn = await get_db()
    async with conn.cursor(aiomysql.DictCursor) as cur:
        await cur.execute(
            'SELECT id, name, email, created_at FROM users'
        )
        users = await cur.fetchall()
    conn.close()
    for u in users:
        u['created_at'] = str(u['created_at'])
    return {'users': users, 'count': len(users)}

@app.get('/comments')
async def get_comments():
    conn = await get_db()
    async with conn.cursor(aiomysql.DictCursor) as cur:
        await cur.execute('SELECT * FROM comments')
        comments = await cur.fetchall()
    conn.close()
    for com in comments:
        com['created_at'] = str(com['created_at'])
    return {'comments': comments, 'count': len(comments)}
