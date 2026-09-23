<?php

namespace App\Filament\Widgets;

use App\Models\Chapter;
use App\Models\Manga;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalMangas = Manga::count();
        $totalChapters = Chapter::where('status', \App\Enums\ChapterStatus::PUBLIE)->count();
        $totalViews = Manga::sum('views_count');
        $totalComments = \App\Models\Comment::count();

        // Simulate some recent trends for the charts
        $viewsTrend = DB::table('manga_views')
            ->selectRaw('DATE(viewed_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(7)
            ->pluck('count')
            ->toArray();

        return [
            Stat::make('Total Séries', $totalMangas)
                ->description('Dans le catalogue')
                ->descriptionIcon('heroicon-m-book-open')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            
            Stat::make('Chapitres Publiés', $totalChapters)
                ->description('Chapitres accessibles au public')
                ->descriptionIcon('heroicon-m-document-text')
                ->chart([3, 10, 4, 15, 6, 20, 8])
                ->color('info'),
                
            Stat::make('Vues Globales', number_format($totalViews))
                ->description('Toutes séries confondues')
                ->descriptionIcon('heroicon-m-eye')
                ->chart(empty($viewsTrend) ? [0,0,0,0,0,0,0] : array_reverse($viewsTrend))
                ->color('primary'),

            Stat::make('Commentaires', $totalComments)
                ->description('Sur toute la plateforme')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->chart([1, 4, 2, 8, 5, 12, 15])
                ->color('warning'),
        ];
    }
}
