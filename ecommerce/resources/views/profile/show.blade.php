<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
</head>

<body>
    @include('partials.toast')
    <h1>My Profile</h1>

    @if ($user->avatar)
        <img src="{{ Storage::url($user->avatar) }}" alt="Avatar" width="120">
    @endif

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div>
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            @error('name') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
            @error('email') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="avatar">Avatar (jpg/png, max 2MB)</label>
            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png">
            @error('avatar') <span>{{ $message }}</span> @enderror
        </div>

        <button type="submit">Save Changes</button>
    </form>
</body>

</html>