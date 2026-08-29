<?php

use App\Models\User;

it('preserves an absolute profile photo URL', function () {
    $user = User::factory()->make(['profile_photo_path' => 'https://cdn.example.test/avatar.png']);

    expect($user->profile_photo_url)->toBe('https://cdn.example.test/avatar.png');
});
