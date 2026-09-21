<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Boardy')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

<nav class="main-nav">
    <div class="nav-left">
        <a href="{{ route('posts.index') }}" class="nav-brand">Boardy</a>
        <a href="{{ route('posts.index') }}" class="nav-item">Лента постов</a>
        @auth
            <a href="{{ route('posts.create') }}" class="nav-item">+ Новый пост</a>
        @endauth
    </div>

    <div class="nav-right">
        @auth
            <span class="nav-greeting">Привет, {{ Auth::user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="nav-item" style="background: none; border: none; cursor: pointer; font: inherit; color: indianred; margin-right: 10px;">
                    Выйти
                </button>
            </form>
        @else
            @if (Route::has('login'))
                <a href="{{ route('login') }}" class="nav-item">Вход</a>
            @endif
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="nav-item nav-btn-dark">Регистрация</a>
            @endif
        @endauth
    </div>
</nav>

<main>
    {{-- Уведомление об успешном действии --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @yield('content')
</main>

<footer>
    <p>&copy; {{ date('Y') }} Boardy Project</p>
</footer>

</body>
</html>
