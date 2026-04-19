<!DOCTYPE html>
<html>

<head>
    <title>Forgot Password</title>
</head>

<body>
    @include('partials.toast')
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <input type="email" name="email" />
        @error('email')<span>{{ $message }}</span>@enderror
        <button type="submit">Send Reset Link</button>
    </form>
</body>

</html>