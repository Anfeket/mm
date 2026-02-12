<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

Route::get('/', [PostController::class, 'index'])->name('home');
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

    Route::get('/upload', [PostController::class, 'create'])->name('posts.create');
    Route::post('/upload', [PostController::class, 'store'])->name('posts.store');

    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/invites', [App\Http\Controllers\ProfileController::class, 'createInvite'])->name('profile.invites.create');
    Route::delete('/profile/invites/{invite}', [App\Http\Controllers\ProfileController::class, 'deleteInvite'])->name('profile.invites.delete');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
    Route::get('/register', [App\Http\Controllers\AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [App\Http\Controllers\AuthController::class, 'register']);
});

// placeholders
Route::get('/tags', function () {
    return view('tags.index');
})->name('tags');
Route::get('/tag/{tag}', function ($tag) {
    return view('tags.show', ['tag' => $tag]);
})->name('tags.show');
Route::get('/artists', function () {
    return view('artists.index');
})->name('artists');
Route::get('/pools', function () {
    return view('pools.index');
})->name('pools');
Route::get('/wiki', function () {
    return view('wiki.index');
})->name('wiki');
Route::get('/forum', function () {
    return view('forum.index');
})->name('forum');
Route::get('/user/{user}', function ($user) {
    return view('users.show', ['user' => $user]);
})->name('users.show');
