<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Authorize</title></head>
<body>
<h1>Authorize {{ $client->name }}?</h1>
<p>{{ $user->name }}, приложение "{{ $client->name }}" запрашивает доступ к вашему аккаунту.</p>

<form method="post" action="{{ route('passport.authorizations.approve') }}">
    @csrf
    <input type="hidden" name="state" value="{{ $request->state }}">
    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
    <input type="hidden" name="auth_token" value="{{ $authToken }}">
    <button type="submit">Approve</button>
</form>

<form method="post" action="{{ route('passport.authorizations.deny') }}">
    @csrf
    @method('DELETE')
    <input type="hidden" name="state" value="{{ $request->state }}">
    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
    <input type="hidden" name="auth_token" value="{{ $authToken }}">
    <button type="submit">Deny</button>
</form>
</body>
</html>
