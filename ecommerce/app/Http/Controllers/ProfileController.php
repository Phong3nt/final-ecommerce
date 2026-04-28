<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $user->load('savedPaymentMethods');

        return view('profile.show', ['user' => $user]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->only(['name', 'email']);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk(config('filesystems.image_disk', 's3'))->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', config('filesystems.image_disk', 's3'));
        }

        $user->update($data);

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }
}
