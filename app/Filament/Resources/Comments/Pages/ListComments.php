<?php

namespace App\Filament\Resources\Comments\Pages;

use App\Filament\Resources\Comments\CommentResource;
use App\Models\BannedIp;
use App\Models\Comment;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

use Filament\Notifications\Notification;

class ListComments extends ListRecords
{
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        $bannedUsersCount = User::where('is_banned', true)->orWhere('is_comment_banned', true)->count();
        $bannedIpsCount = BannedIp::count();
        $totalBanned = $bannedUsersCount + $bannedIpsCount;

        return [
            Action::make('viewBannedAccounts')
                ->label('Comptes & IPs bannis' . ($totalBanned > 0 ? " ({$totalBanned})" : ''))
                ->icon('heroicon-o-shield-exclamation')
                ->color($totalBanned > 0 ? 'danger' : 'gray')
                ->modalHeading('Comptes et Adresses IP Sanctionnés')
                ->modalDescription('Consultez et gérez les comptes utilisateurs et adresses IP actuellement sous restriction.')
                ->modalWidth('4xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Fermer')
                ->modalContent(fn () => view('filament.comments.banned-accounts-modal', [
                    'bannedUsers' => User::where('is_banned', true)->orWhere('is_comment_banned', true)->get(),
                    'bannedIps' => BannedIp::latest()->get(),
                ])),
        ];
    }

    public function unbanUserById(int $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->update([
                'is_banned' => false,
                'banned_at' => null,
                'ban_reason' => null,
                'is_comment_banned' => false,
                'comment_banned_at' => null,
                'comment_ban_reason' => null,
            ]);
            Notification::make()->title("L'utilisateur {$user->name} a été débanni avec succès.")->success()->send();
        }
    }

    public function unbanIpById(int $ipId): void
    {
        $ip = BannedIp::find($ipId);
        if ($ip) {
            $ip->delete();
            Notification::make()->title("L'adresse IP a été débloquée.")->success()->send();
        }
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous')
                ->badge(Comment::withoutTrashed()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()),
            'hidden' => Tab::make('Masqués')
                ->badge(Comment::withoutTrashed()->where('is_hidden', true)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()->where('is_hidden', true)),
            'trashed' => Tab::make('Supprimés')
                ->badge(Comment::onlyTrashed()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
            'banned' => Tab::make('Auteurs bannis')
                ->badge(Comment::withoutTrashed()->where(function ($q) {
                    $q->whereHas('user', fn ($u) => $u->where('is_banned', true)->orWhere('is_comment_banned', true))
                      ->orWhereIn('ip_hash', BannedIp::pluck('ip_hash'));
                })->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()->where(function ($q) {
                    $q->whereHas('user', fn ($u) => $u->where('is_banned', true)->orWhere('is_comment_banned', true))
                      ->orWhereIn('ip_hash', BannedIp::pluck('ip_hash'));
                })),
        ];
    }
}
