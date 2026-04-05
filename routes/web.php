<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostTagController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PostController::class, 'index'])->name('home');
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');
Route::get('/tags/autocomplete', [TagController::class, 'autocomplete'])->name('tags.autocomplete');

Route::get('/user/{user}', [UserController::class, 'show'])->name('users.show');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::patch('/posts/{post}/visibility', [PostController::class, 'toggleVisibility'])->name('posts.toggleVisibility');

    Route::post('/posts/{post}/vote', [VoteController::class, 'vote'])->name('posts.vote');
    Route::post('/posts/{post}/favorites', [FavoriteController::class, 'toggle'])->name('posts.favorites.toggle');

    Route::post('/posts/{post}/tags', [PostTagController::class, 'attach'])->name('posts.tags.attach');
    Route::delete('/posts/{post}/tags/{tag}', [PostTagController::class, 'detach'])->name('posts.tags.detach');

    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('posts.comments.store')->middleware('throttle:10,1');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::get('/upload', [PostController::class, 'create'])->name('posts.create');
    Route::post('/upload', [PostController::class, 'store'])->name('posts.store');

    Route::get('/account', [AccountController::class, 'show'])->name('account.show');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::post('/account/invites', [AccountController::class, 'createInvite'])->name('account.invites.create');
    Route::delete('/account/invites/{invite}', [AccountController::class, 'deleteInvite'])->name('account.invites.delete');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// dev components testing route
Route::get('/dev/components', function () {
    abort_unless(app()->isLocal(), 404);

    return view('dev.components');
})->name('dev.components');

// sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-static.xml', [SitemapController::class, 'static'])->name('sitemap.static');
Route::get('/sitemap-posts.xml', [SitemapController::class, 'posts'])->name('sitemap.posts');

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
