@extends('layouts.app')

@section('title', 'Редактировать пост')

@section('content')
    <div class="auth-card">
        <h1 class="page-title">Редактирование поста</h1>

        <form action="{{ route('posts.update', $post) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="title">Заголовок</label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $post->title) }}" required>
                @error('title')
                <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="body">Содержание</label>
                <textarea name="body" id="body" class="form-control" required>{{ old('body', $post->body) }}</textarea>
                @error('body')
                <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
            <a href="{{ route('posts.show', $post) }}" class="btn btn-secondary" style="margin-left: 10px;">Отмена</a>
        </form>
    </div>
@endsection
