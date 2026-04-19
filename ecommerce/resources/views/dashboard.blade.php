<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — E-Commerce</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 700px;
            margin: 80px auto;
            padding: 0 1rem;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #86efac;
            padding: .75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            color: #166534;
        }

        .alert-warning {
            background: #fefce8;
            border: 1px solid #fde047;
            padding: .75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            color: #854d0e;
        }
    </style>
</head>

<body>
    @include('partials.toast')
    <h2>Welcome, {{ auth()->user()->name }}!</h2>

    @if (!auth()->user()->hasVerifiedEmail())
        <div class="alert-warning">
            Your email is not verified yet.
            <form method="POST" action="{{ route('verification.send') }}" style="display:inline;">
                @csrf
                <button type="submit"
                    style="background:none;border:none;color:#854d0e;cursor:pointer;text-decoration:underline;">
                    Resend verification email
                </button>
            </form>
        </div>
    @endif

    <p>You are logged in.</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
            style="padding:.5rem 1rem;background:#dc2626;color:#fff;border:none;border-radius:4px;cursor:pointer;">
            Log out
        </button>
    </form>
</body>

</html>