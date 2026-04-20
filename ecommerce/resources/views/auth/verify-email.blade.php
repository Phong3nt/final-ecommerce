<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify Email</title>
</head>

<body>
    @include('partials.toast')
    <h1>Verify Your Email Address</h1>

    <p>Thanks for signing up! Before getting started, please verify your email address by clicking the link we just sent
        to you. If you did not receive the email, we will gladly send you another.</p>

    @if (session('status') === 'verification-link-sent')
        <p>A new verification link has been sent to the email address you provided during registration.</p>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit">Resend Verification Email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Log Out</button>
    </form>
</body>

</html>