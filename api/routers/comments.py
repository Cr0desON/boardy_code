from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel, Field
from auth import get_current_user
from routers.ws import manager
from database import db_query, db_query_one, db_execute, db_insert
import aiomysql

router = APIRouter(prefix='/api')

class CommentIn(BaseModel):
    body: str = Field(..., min_length=1, max_length=2000)
    author_name: str = Field(..., min_length=1, max_length=255)

class CommentUpdate(BaseModel):
    body: str = Field(..., min_length=1, max_length=2000)

@router.get('/posts/{post_id}/comments')
async def list_comments(post_id: int):
    rows = await db_query(
        'SELECT * FROM comments WHERE post_id=%s ORDER BY created_at',
        post_id)
    return rows

@router.post('/posts/{post_id}/comments')
async def create_comment(post_id: int, data: CommentIn,
                          user = Depends(get_current_user)):
    comment_id = await db_insert(
        '''INSERT INTO comments (post_id, author_id, author_name, body)
           VALUES (%s, %s, %s, %s)''',
        post_id, user['sub'], data.author_name, data.body)

    comment = {
        'id': comment_id,
        'post_id': post_id,
        'author_id': user['sub'],
        'author_name': data.author_name,
        'body': data.body,
    }
    await manager.broadcast({'type': 'new_comment', 'comment': comment})
    return comment

@router.put('/comments/{comment_id}')
async def update_comment(comment_id: int, data: CommentUpdate,
                          user = Depends(get_current_user)):
    existing = await db_query_one(
        'SELECT * FROM comments WHERE id=%s', comment_id)
    if not existing:
        raise HTTPException(404, 'Not found')
    if str(existing['author_id']) != str(user['sub']):
        raise HTTPException(403, 'Not your comment')

    await db_execute('UPDATE comments SET body=%s WHERE id=%s',
                      data.body, comment_id)
    await manager.broadcast({
        'type': 'update_comment',
        'comment': {'id': comment_id, 'body': data.body}
    })
    return {'id': comment_id, 'body': data.body}

@router.delete('/comments/{comment_id}')
async def delete_comment(comment_id: int,
                          user = Depends(get_current_user)):
    existing = await db_query_one(
        'SELECT * FROM comments WHERE id=%s', comment_id)
    if not existing:
        raise HTTPException(404)
    if str(existing['author_id']) != str(user['sub']):
        raise HTTPException(403, 'Not your comment')

    await db_execute('DELETE FROM comments WHERE id=%s', comment_id)
    await manager.broadcast({
        'type': 'delete_comment',
        'comment_id': comment_id
    })
    return {'ok': True}
