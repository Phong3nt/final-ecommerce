<!DOCTYPE html>
<html>
<head><title>Reset Password</title></head>
<body>
<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}" />
    <input type="email" name="email" value="{{ $email ?? '' }}" />
    <input type="password" name="password" />
    <input type="password" name="password_confirmation" />
    @error('email')<span>{{ $message }}</span>@enderror
    @error('password')<span>{{ $message }}</span>@enderror
    <button type="submit">Reset Password</button>
</form>
</body>
</html>
