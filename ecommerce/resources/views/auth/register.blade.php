<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — E-Commerce</title>
    <style>
        body { font-family: sans-serif; max-width: 420px; margin: 80px auto; padding: 0 1rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: .25rem; font-weight: 600; }
        input { width: 100%; padding: .5rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: .65rem; background: #4f46e5; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; }
        .error { color: #dc2626; font-size: .85rem; margin-top: .25rem; }
        .alert { background: #fef2f2; border: 1px solid #fca5a5; padding: .75rem; border-radius: 4px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h2>Create an account</h2>

    @if ($errors->any())
        <div class="alert">
            <ul style="margin:0;padding-left:1.2rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.store') }}">
        @csrf

        <div class="form-group">
            <label for="name">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            @error('name') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            <small style="color:#6b7280;">Min 8 chars, upper+lower case, number.</small>
            @error('password') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit">Register</button>
    </form>

    <p style="text-align:center;margin-top:1rem;">
        Already have an account? <a href="{{ route('login') }}">Log in</a>
    </p>
</body>
</html>
