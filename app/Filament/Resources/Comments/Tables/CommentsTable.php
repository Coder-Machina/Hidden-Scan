<?php

namespace App\Filament\Resources\Comments\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\BannedIp;
use App\Models\Comment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pseudo')
                    ->label('Auteur')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon(function (Comment $record) {
                        $isBanned = ($record->user && $record->user->is_banned);
                        $isCommentBanned = ($record->user && $record->user->is_comment_banned) || BannedIp::where('ip_hash', $record->ip_hash)->exists();
                        if ($isBanned || $isCommentBanned) {
                            return 'heroicon-m-no-symbol';
                        }
                        return $record->user_id ? 'heroicon-m-check-badge' : 'heroicon-m-user';
                    })
                    ->iconColor(function (Comment $record) {
                        $isBanned = ($record->user && $record->user->is_banned);
                        $isCommentBanned = ($record->user && $record->user->is_comment_banned) || BannedIp::where('ip_hash', $record->ip_hash)->exists();
                        if ($isBanned || $isCommentBanned) {
                            return 'danger';
                        }
                        return $record->user_id ? 'success' : 'gray';
                    })
                    ->description(function (Comment $record) {
                        $labels = [];
                        if ($record->user?->is_banned) {
                            $labels[] = '🚫 Compte banni';
                        } elseif ($record->user?->is_comment_banned || BannedIp::where('ip_hash', $record->ip_hash)->exists()) {
                            $labels[] = '⛔ Banni des commentaires';
                        }
                        $labels[] = $record->user ? $record->user->email : 'Visiteur anonyme';
                        return implode(' • ', $labels);
                    }),
                TextColumn::make('content')->label('Contenu')
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->getStateUsing(function (Comment $record) {
                        $target = $record->commentable;
                        if (!$target) return 'Inconnu';

                        if ($target instanceof \App\Models\Manga) {
                            $type = $target->type;
                            return $type instanceof \App\Enums\MangaType ? $type->getLabel() : ucfirst((string)($type ?? 'Manga'));
                        }

                        if ($target instanceof \App\Models\Chapter) {
                            $manga = $target->manga;
                            $mangaType = $manga?->type;
                            $typeName = $mangaType instanceof \App\Enums\MangaType 
                                ? $mangaType->getLabel() 
                                : ($mangaType ? ucfirst((string)$mangaType) : 'Œuvre');

                            return $typeName . ' (Ch. ' . ($target->number ?? '') . ')';
                        }

                        return class_basename($record->commentable_type);
                    })
                    ->description(function (Comment $record) {
                        $target = $record->commentable;
                        if ($target instanceof \App\Models\Manga) {
                            return $target->title;
                        }
                        if ($target instanceof \App\Models\Chapter) {
                            return $target->manga?->title;
                        }
                        return null;
                    })
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        str_contains(strtolower((string)$state), 'manhwa') => 'info',
                        str_contains(strtolower((string)$state), 'manhua') => 'warning',
                        str_contains(strtolower((string)$state), 'manga') => 'success',
                        default => 'gray',
                    }),
                IconColumn::make('is_hidden')
                    ->label('Masqué')
                    ->boolean(),
                IconColumn::make('is_pinned')
                    ->label('Épinglé')
                    ->boolean(),
                TextColumn::make('created_at')->label('Date de création')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_hidden')->label('Masqué'),
                TrashedFilter::make()->label('État de suppression'),
            ])
            ->recordActions([
                Action::make('viewUser')
                    ->label('Profil')
                    ->icon('heroicon-o-user-circle')
                    ->color('info')
                    ->modalHeading(fn (Comment $record) => $record->user ? 'Profil de ' . $record->user->name : 'Détails du commentateur')
                    ->modalDescription(fn (Comment $record) => $record->user ? 'Compte utilisateur enregistré' : 'Ce commentaire a été posté sans compte connecté')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->infolist(fn (Comment $record) => [
                        Section::make('Informations de l\'auteur')
                            ->schema([
                                TextEntry::make('pseudo')
                                    ->label('Pseudo affiché')
                                    ->default($record->pseudo),
                                TextEntry::make('user.name')
                                    ->label('Nom du compte')
                                    ->default($record->user?->name)
                                    ->placeholder('Non inscrit (invité)'),
                                TextEntry::make('user.email')
                                    ->label('Adresse email')
                                    ->default($record->user?->email)
                                    ->copyable()
                                    ->placeholder('Non renseigné'),
                                TextEntry::make('user.created_at')
                                    ->label('Date d\'inscription')
                                    ->dateTime('d/m/Y H:i')
                                    ->default($record->user?->created_at)
                                    ->placeholder('Non inscrit'),
                                TextEntry::make('user_comments_count')
                                    ->label('Total commentaires de l\'auteur')
                                    ->state(fn () => $record->user ? $record->user->comments()->count() : 1)
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('ip_hash')
                                    ->label('Hash IP')
                                    ->default($record->ip_hash)
                                    ->copyable()
                                    ->limit(20),
                            ])
                            ->columns(2),
                    ])
                    ->extraModalFooterActions(fn (Comment $record) => $record->user_id ? [
                        Action::make('openUserResource')
                            ->label('Voir la fiche utilisateur')
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->url(UserResource::getUrl('view', ['record' => $record->user_id]))
                            ->openUrlInNewTab(),
                    ] : []),

                Action::make('ban')
                    ->label('Bannir')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->modalHeading(fn (Comment $record) => 'Bannir ' . $record->pseudo)
                    ->modalDescription('Sélectionnez la portée du bannissement et les actions associées.')
                    ->modalSubmitActionLabel('Confirmer le bannissement')
                    ->schema(fn (Comment $record) => [
                        Radio::make('ban_type')
                            ->label('Type de bannissement')
                            ->options($record->user_id ? [
                                'comment_only' => 'Bannir des commentaires uniquement (le compte reste actif mais ne peut plus commenter)',
                                'full_account' => 'Bannir le compte définitivement (connexion et accès bloqués)',
                            ] : [
                                'ip_comments' => 'Bannir l\'adresse IP des commentaires',
                            ])
                            ->default($record->user_id ? 'comment_only' : 'ip_comments')
                            ->required(),
                        TextInput::make('reason')
                            ->label('Motif du bannissement')
                            ->placeholder('ex: Spam, insultes, contenu inapproprié'),
                        Toggle::make('hide_all_comments')
                            ->label('Masquer tous les commentaires déjà postés par cet auteur')
                            ->default(true),
                    ])
                    ->action(function (Comment $record, array $data) {
                        $reason = $data['reason'] ?? null;
                        $hideComments = $data['hide_all_comments'] ?? false;
                        $banType = $data['ban_type'] ?? 'comment_only';

                        if ($banType === 'comment_only' && $record->user) {
                            $record->user->update([
                                'is_comment_banned' => true,
                                'comment_banned_at' => now(),
                                'comment_ban_reason' => $reason,
                            ]);
                            BannedIp::firstOrCreate(
                                ['ip_hash' => $record->ip_hash],
                                ['reason' => $reason, 'ban_type' => 'comments', 'banned_by' => auth()->id()]
                            );
                            Notification::make()->title('Auteur banni des commentaires')->warning()->send();
                        } elseif ($banType === 'full_account' && $record->user) {
                            $record->user->update([
                                'is_banned' => true,
                                'banned_at' => now(),
                                'ban_reason' => $reason,
                            ]);
                            BannedIp::firstOrCreate(
                                ['ip_hash' => $record->ip_hash],
                                ['reason' => $reason, 'ban_type' => 'all', 'banned_by' => auth()->id()]
                            );
                            Notification::make()->title('Compte utilisateur banni définitivement')->danger()->send();
                        } else {
                            // IP ban for guest
                            BannedIp::firstOrCreate(
                                ['ip_hash' => $record->ip_hash],
                                ['reason' => $reason, 'ban_type' => 'comments', 'banned_by' => auth()->id()]
                            );
                            Notification::make()->title('Adresse IP bannie des commentaires')->warning()->send();
                        }

                        if ($hideComments) {
                            if ($record->user_id) {
                                Comment::where('user_id', $record->user_id)->update(['is_hidden' => true]);
                            } else {
                                Comment::where('ip_hash', $record->ip_hash)->update(['is_hidden' => true]);
                            }
                        }

                        // Audit log
                        \App\Models\AuditLog::create([
                            'user_id' => auth()->id(),
                            'action' => $banType === 'full_account' ? 'ban_account' : 'ban_comments',
                            'model_type' => Comment::class,
                            'model_id' => $record->id,
                            'new_values' => [
                                'target' => $record->pseudo,
                                'ban_type' => $banType,
                                'reason' => $reason,
                                'comments_hidden' => $hideComments,
                            ],
                            'ip_address' => request()->ip(),
                        ]);
                    }),

                Action::make('unban')
                    ->label('Débannir')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(function (Comment $record) {
                        $isUserBanned = $record->user && ($record->user->is_banned || $record->user->is_comment_banned);
                        $isIpBanned = BannedIp::where('ip_hash', $record->ip_hash)->exists();
                        return $isUserBanned || $isIpBanned;
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn (Comment $record) => 'Débannir ' . $record->pseudo)
                    ->modalDescription('L\'utilisateur et son adresse IP retrouveront l\'accès et pourront à nouveau commenter.')
                    ->action(function (Comment $record) {
                        if ($record->user) {
                            $record->user->update([
                                'is_banned' => false,
                                'banned_at' => null,
                                'ban_reason' => null,
                                'is_comment_banned' => false,
                                'comment_banned_at' => null,
                                'comment_ban_reason' => null,
                            ]);
                        }
                        BannedIp::where('ip_hash', $record->ip_hash)->delete();

                        // Audit log
                        \App\Models\AuditLog::create([
                            'user_id' => auth()->id(),
                            'action' => 'unban',
                            'model_type' => Comment::class,
                            'model_id' => $record->id,
                            'new_values' => [
                                'target' => $record->pseudo,
                            ],
                            'ip_address' => request()->ip(),
                        ]);

                        Notification::make()->title('Utilisateur débanni avec succès')->success()->send();
                    }),

                EditAction::make()->visible(fn (Comment $record) => !$record->trashed()),
                DeleteAction::make()->visible(fn (Comment $record) => !$record->trashed()),
                RestoreAction::make()->visible(fn (Comment $record) => $record->trashed()),
                ForceDeleteAction::make()->visible(fn (Comment $record) => $record->trashed()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}