<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\LeaderboardService;
use App\Services\MurimRankService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $leaderboard = LeaderboardService::getTopCultivators(50, $user);
        $allRanks = MurimRankService::getRanks();

        return view('public.leaderboard', [
            'leaderboard' => $leaderboard,
            'podium' => $leaderboard['podium'],
            'cultivators' => $leaderboard['list'],
            'currentUserRank' => $leaderboard['current_user_rank'],
            'allRanks' => $allRanks,
        ]);
    }
}
