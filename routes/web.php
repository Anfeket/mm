<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

Route::get('/', function () {
    return view('home');
});

Route::get('/posts', [PostController::class, 'index'])->name('posts.index');


// placeholders
Route::get('/tags', function () {
    return view('tags.index');
})->name('tags');
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
Route::get('/login', function () {
    return view('auth.login');
})->name('login');
Route::get('/register', function () {
    return view('auth.register');
})->name('register');
