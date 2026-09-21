import { startLogin, handleCallback, refreshToken } from './auth.js'
import React, { useState, useEffect } from "react";

function Comments({ postId, userName, wsEvent }) {
    const [token, setToken] = useState(() => localStorage.getItem('token'));
    const [comments, setComments] = useState([]);

    useEffect(() => {
        handleCallback().then(t => {
            if (t) {
                localStorage.setItem('token', t);
                setToken(t);
            }
        }).catch(err => console.error('Ошибка OAuth callback:', err));

        async function fetchComments() {
            try {
                const res = await fetch(`/api/posts/${postId}/comments`);
                const data = await res.json();
                setComments(Array.isArray(data) ? data : []);
            } catch (e) {
                console.error('Ошибка загрузки', e);
            }
        }
        fetchComments();
    }, [postId]);

    useEffect(() => {
        if (!wsEvent) return;

        if (wsEvent.type === 'new_comment' && wsEvent.comment.post_id === postId) {
            setComments(prev => [...prev, wsEvent.comment]);
        }
        if (wsEvent.type === 'update_comment') {
            setComments(prev => prev.map(c =>
                c.id === wsEvent.comment.id ? { ...c, body: wsEvent.comment.body } : c
            ));
        }
        if (wsEvent.type === 'delete_comment') {
            setComments(prev => prev.filter(c => c.id !== wsEvent.comment_id));
        }
    }, [wsEvent, postId]);

    async function authedFetch(url, options = {}) {
        let response = await fetch(url, {
            ...options,
            headers: {
                ...options.headers,
                'Authorization': 'Bearer ' + token,
            }
        });
        if (response.status === 401) {
            const newToken = await refreshToken();
            if (!newToken) return null;
            setToken(newToken);
            return fetch(url, {
                ...options,
                headers: {
                    ...options.headers,
                    'Authorization': 'Bearer ' + newToken,
                }
            });
        }
        return response;
    }

    async function handleAddComment(e) {
        e.preventDefault();
        const body = e.target.elements.body.value.trim();
        if (!body) return;

        await authedFetch(`/api/posts/${postId}/comments`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ body, author_name: userName })
        });

        e.target.reset();
    }

    return (
        <div className="comments-section">
            <h3>Комментарии ({comments.length})</h3>

            {token ? (
                <form onSubmit={handleAddComment}>
                    <textarea name="body" placeholder="Напишите комментарий..." required></textarea>
                    <button type="submit">Отправить</button>
                </form>
            ) : (
                <p>Войдите, чтобы оставить комментарий</p>
            )}

            <div className="comments-list">
                {comments.map(c => (
                    <div key={c.id} className="comment-card">
                        <strong>{c.author_name}</strong>
                        <p>{c.body}</p>
                    </div>
                ))}
            </div>
        </div>
    );
}
