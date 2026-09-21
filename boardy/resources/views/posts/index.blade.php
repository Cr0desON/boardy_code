@extends('layouts.app')

@section('title', 'Лента постов')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 class="page-title">Лента постов</h1>
        @auth
            <a href="{{ route('posts.create') }}" class="btn btn-primary">Создать пост</a>
        @endauth
    </div>

    <div id="posts-feed" class="posts-list">
        @foreach($posts as $post)
            <a href="{{ route('posts.show', $post) }}" class="post-card" style="display:block; color:inherit;">
                <div class="post-header">
                    <span class="post-author">{{ $post->author->name }}</span>
                    <span class="post-time">{{ $post->created_at->diffForHumans() }}</span>
                </div>
                <h3 class="post-title">{{ $post->title }}</h3>
                <p class="post-body">{{ \Illuminate\Support\Str::limit($post->body, 200) }}</p>
            </a>
        @endforeach
    </div>

    <div style="margin-top: 20px;">
        {{ $posts->links() }}
    </div>

    <script>
        @if(app()->environment('production'))
        const wsUrl  = 'wss://api.{{ config("app.fastapi_domain") }}/ws'
        @else
        const wsUrl  = (location.protocol === 'https:' ? 'wss://' : 'ws://') + location.host + '/ws'
        @endif

        function connect() {
            const ws = new WebSocket(wsUrl)
            ws.onopen    = () => console.log('WS connected')
            ws.onmessage = (e) => {
                const msg = JSON.parse(e.data)
                if (msg.type === 'new_post') prependPost(msg.post)
            }
            ws.onclose = () => setTimeout(connect, 3000)
        }

        function prependPost(post) {
            const feed = document.getElementById('posts-feed')
            if (!feed) return
            const el = document.createElement('a')
            el.href = `/posts/${post.id}`
            el.className = 'post-card'
            el.style.display = 'block'
            el.style.color = 'inherit'
            el.innerHTML = `
                <div class="post-header">
                    <span class="post-author">${escapeHtml(post.author)}</span>
                    <span class="post-time">только что</span>
                </div>
                <h3 class="post-title">${escapeHtml(post.title)}</h3>
                <p class="post-body">${escapeHtml(post.body)}</p>`
            feed.prepend(el)
        }

        function escapeHtml(str) {
            const d = document.createElement('div')
            d.textContent = str
            return d.innerHTML
        }

        connect()
    </script>
@endsection
