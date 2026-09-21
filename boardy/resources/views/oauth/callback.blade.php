<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Вход...</title>
</head>
<body>
<p>Выполняется вход, подождите...</p>

<script type="module">
    import { handleCallback } from '/js/auth.js'

    handleCallback().then(token => {
        if (token) {
            localStorage.setItem('token', token)
            const returnTo = sessionStorage.getItem('return_to') || '/posts'
            sessionStorage.removeItem('return_to')
            window.location = returnTo
        } else {
            document.body.innerHTML = '<p>Ошибка входа. <a href="/login">Попробовать снова</a></p>'
        }
    }).catch(err => {
        console.error(err)
        document.body.innerHTML = '<p>Ошибка: ' + err.message + '. <a href="/login">Попробовать снова</a></p>'
    })
</script>
</body>
</html>
