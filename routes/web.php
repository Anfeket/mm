<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuthController;

Route::get('/', [PostController::class, 'index'])->name('home');
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

    Route::post('/posts/{post}/vote', [VoteController::class, 'vote'])->name('posts.vote');

    Route::get('/upload', [PostController::class, 'create'])->name('posts.create');
    Route::post('/upload', [PostController::class, 'store'])->name('posts.store');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/invites', [ProfileController::class, 'createInvite'])->name('profile.invites.create');
    Route::delete('/profile/invites/{invite}', [ProfileController::class, 'deleteInvite'])->name('profile.invites.delete');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
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
