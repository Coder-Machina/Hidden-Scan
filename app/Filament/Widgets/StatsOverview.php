<?php

namespace App\Filament\Widgets;

use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Manga;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalMangas = Manga::count();
        $totalChapters = Chapter::where('status', \App\Enums\ChapterStatus::PUBLIE)->count();
        $pendingChapters = Chapter::where('status', '!=', \App\Enums\ChapterStatus::PUBLIE)->count();
        $totalMangaViews = Manga::sum('views_count');
        $totalChapterViews = Chapter::sum('views_count');
        $totalComments = Comment::count();

        // Weekly trends (last 7 days)
        $recentChapters = Chapter::where('created_at', '>=', now()->subDays(7))->count();
        $recentComments = Comment::where('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make('Total Séries', $totalMangas)
                ->description('Dans le catalogue')
                ->descriptionIcon('heroicon-m-book-open')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Chapitres Publiés', $totalChapters)
                ->description($pendingChapters . ' en attente')
                ->descriptionIcon('heroicon-m-document-text')
                ->chart([3, 10, 4, 15, 6, 20, 8])
                ->color('info'),

            Stat::make('Vues Mangas', number_format($totalMangaViews))
                ->description('Toutes séries confondues')
                ->descriptionIcon('heroicon-m-eye')
                ->chart([10, 30, 20, 45, 35, 55, 60])
                ->color('primary'),

            Stat::make('Vues Chapitres', number_format($totalChapterViews))
                ->description('Lectures totales')
                ->descriptionIcon('heroicon-m-book-open')
                ->chart([5, 15, 10, 25, 20, 35, 40])
                ->color('warning'),

            Stat::make('Commentaires', $totalComments)
                ->description($recentComments . ' cette semaine')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->chart([1, 4, 2, 8, 5, 12, 15])
                ->color('danger'),

            Stat::make('Activité récente', $recentChapters . ' chapitres')
                ->description('Créés ces 7 derniers jours')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([2, 4, 6, 8, 10, 12, $recentChapters])
                ->color('gray'),
        ];
    }
}
