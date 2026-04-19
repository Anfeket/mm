<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController extends Controller
{
    public function show(User $user)
    {
        $postStats = $user->posts()
            ->where('is_listed', true)
            ->selectRaw('
                COUNT(*) as post_count,
                COALESCE(SUM(like_count), 0) as total_score,
                COALESCE(SUM(view_count), 0) as total_views,
                COALESCE(SUM(favorites_count), 0) as total_favorites_received
            ')
            ->first();

        $commentCount = $user->comments()->count();
        $favoritesGivenCount = $user->favorites()->count();

        $posts = $user->posts()
            ->where('is_listed', true)
            ->latest()
            ->limit(10)
            ->get();

        return view('users.show', compact('user', 'posts', 'postStats', 'commentCount', 'favoritesGivenCount'));
    }
}
