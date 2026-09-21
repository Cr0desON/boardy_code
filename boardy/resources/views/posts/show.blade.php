@extends('layouts.app')

@section('title', $post->title)

@section('content')
    <a href="{{ route('posts.index') }}">&larr; Назад к ленте</a>

    <div class="post-card" style="margin-top: 15px;">
        <div class="post-header">
            <span class="post-author">{{ $post->author->name }}</span>
            <span class="post-time">{{ $post->created_at->diffForHumans() }}</span>
        </div>
        <h1 class="post-title">{{ $post->title }}</h1>
        <p class="post-body">{{ $post->body }}</p>

        @can('update', $post)
            <div style="margin-top: 15px;">
                <a href="{{ route('posts.edit', $post) }}" class="btn btn-secondary">Редактировать</a>
                <form method="POST" action="{{ route('posts.destroy', $post) }}" style="display:inline;">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        @endcan
    </div>

    <div class="comments-section">
        <h2 class="page-title" style="font-size: 20px;">Комментарии</h2>

        @auth
            <div class="form-group">
                <textarea id="comment-body" class="form-control" placeholder="Ваш комментарий..."></textarea>
                <button id="comment-submit" class="btn btn-primary" style="margin-top: 10px;">Отправить</button>
            </div>
        @endauth

        <div id="comments-list"></div>
    </div>

    <script>
        const POST_ID = {{ $post->id }};
        const user = @json(auth()->user()->name ?? null);
        const CURRENT_USER_ID = @json(auth()->id() ?? null);
        const AUTHOR_NAME = @json(auth()->user()->name ?? null);

        @if(app()->environment('production'))
        const apiUrl = 'https://api.{{ config("app.fastapi_domain") }}'
        const wsUrl  = 'wss://api.{{ config("app.fastapi_domain") }}/ws'
        @else
        const apiUrl = ''
        const wsUrl  = (location.protocol === 'https:' ? 'wss://' : 'ws://') + location.host + '/ws'
        @endif

        function escapeHtml(str) {
            const d = document.createElement('div')
            d.textContent = str
            return d.innerHTML
        }

        function renderComment(c) {
            const isAuthor = CURRENT_USER_ID && String(c.author_id) === String(CURRENT_USER_ID);

            return `
        <div class="comment-card" data-id="${c.id}" style="margin-bottom: 10px; padding: 10px; border: 1px solid #e5e7eb; border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <strong>${escapeHtml(c.author_name)}</strong>
                ${isAuthor ? `
                    <div>
                        <button onclick="startEditComment(${c.id})" class="btn btn-sm btn-secondary" style="font-size: 11px; padding: 2px 6px;">Изменить</button>
                        <button onclick="deleteComment(${c.id})" class="btn btn-sm btn-danger" style="font-size: 11px; padding: 2px 6px; margin-left: 5px;">Удалить</button>
                    </div>
                ` : ''}
            </div>
            <p class="comment-body-text" style="margin-top:4px;">${escapeHtml(c.body)}</p>
        </div>`
        }

        async function loadComments() {
            const res = await fetch(`${apiUrl}/api/posts/${POST_ID}/comments`)
            const data = await res.json()
            const commentsArray = Array.isArray(data) ? data : []
            document.getElementById('comments-list').innerHTML = commentsArray.map(renderComment).join('')
        }

        document.getElementById('comment-submit')?.addEventListener('click', async () => {
            const body = document.getElementById('comment-body').value.trim()
            if (!body) return

            const token = localStorage.getItem('token')

            if (!token) {
                alert('Вы не авторизованы в API (нет JWT-токена)!');
                return;
            }

            try {
                const res = await fetch(`${apiUrl}/api/posts/${POST_ID}/comments`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({ body, author_name: AUTHOR_NAME })
                })

                if (!res.ok) {
                    const errText = await res.text()
                    console.error('Ошибка API:', res.status, errText)
                    return
                }

                document.getElementById('comment-body').value = ''
            } catch (e) {
                console.error('Ошибка сети:', e)
            }
        })

        function connectWs() {
            const ws = new WebSocket(wsUrl)
            ws.onmessage = (e) => {
                const msg = JSON.parse(e.data)
                if (msg.type === 'new_comment' && msg.comment.post_id === POST_ID) {
                    document.getElementById('comments-list').insertAdjacentHTML('beforeend', renderComment(msg.comment))
                }
                if (msg.type === 'update_comment') {
                    const el = document.querySelector(`.comment-card[data-id="${msg.comment.id}"] p`)
                    if (el) el.textContent = msg.comment.body
                }
                if (msg.type === 'delete_comment') {
                    document.querySelector(`.comment-card[data-id="${msg.comment_id}"]`)?.remove()
                }
            }
            ws.onclose = () => setTimeout(connectWs, 3000)
        }

        async function deleteComment(commentId) {
            if (!confirm('Вы уверены, что хотите удалить комментарий?')) return;

            const token = localStorage.getItem('token');
            if (!token) {
                alert('Нет JWT-токена авторизации!');
                return;
            }

            try {
                const res = await fetch(`${apiUrl}/api/comments/${commentId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                if (!res.ok) {
                    const err = await res.text();
                    console.error('Ошибка удаления:', err);
                    alert('Не удалось удалить комментарий');
                }
            } catch (e) {
                console.error('Ошибка сети:', e);
            }
        }

        function startEditComment(commentId) {
            const card = document.querySelector(`.comment-card[data-id="${commentId}"]`);
            const pTag = card.querySelector('.comment-body-text');
            const currentText = pTag.textContent;

            card.querySelector('div:last-child').innerHTML = `
        <textarea class="form-control edit-input" style="width: 100%; margin-top: 5px; font-size: 14px;">${escapeHtml(currentText)}</textarea>
        <div style="margin-top: 5px;">
            <button onclick="saveEditComment(${commentId})" class="btn btn-sm btn-primary" style="font-size: 11px; padding: 2px 6px;">Сохранить</button>
            <button onclick="loadComments()" class="btn btn-sm btn-secondary" style="font-size: 11px; padding: 2px 6px; margin-left: 5px;">Отмена</button>
        </div>
    `;
        }

        async function saveEditComment(commentId) {
            const card = document.querySelector(`.comment-card[data-id="${commentId}"]`);
            const newBody = card.querySelector('.edit-input').value.trim();

            if (!newBody) return;

            const token = localStorage.getItem('token');
            if (!token) {
                alert('Нет JWT-токена авторизации!');
                return;
            }

            try {
                const res = await fetch(`${apiUrl}/api/comments/${commentId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({ body: newBody })
                });

                if (!res.ok) {
                    const err = await res.text();
                    console.error('Ошибка обновления:', err);
                    alert('Не удалось обновить комментарий');
                }
            } catch (e) {
                console.error('Ошибка сети:', e);
            }
        }

        loadComments()
        connectWs()
    </script>
@endsection
