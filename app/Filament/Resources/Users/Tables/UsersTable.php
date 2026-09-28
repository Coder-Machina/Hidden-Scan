<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Comment;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom / Pseudo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->label('Adresse email')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->getStateUsing(function (User $record) {
                        if ($record->is_banned) return 'Compte banni';
                        if ($record->is_comment_banned) return 'Banni des comm.';
                        return 'Actif';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Compte banni' => 'danger',
                        'Banni des comm.' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'owner' => 'danger',
                        'admin' => 'warning',
                        default => 'info',
                    })
                    ->placeholder('Membre'),
                TextColumn::make('comments_count')
                    ->label('Commentaires')
                    ->counts('comments')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('ban')
                    ->label('Bannir')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (User $record) => !$record->is_banned)
                    ->modalHeading(fn (User $record) => 'Bannir ' . $record->name)
                    ->schema([
                        Radio::make('ban_type')
                            ->label('Type de bannissement')
                            ->options([
                                'comment_only' => 'Bannir des commentaires uniquement (le compte reste actif mais ne peut plus commenter)',
                                'full_account' => 'Bannir le compte définitivement (accès et connexion bloqués)',
                            ])
                            ->default('comment_only')
                            ->required(),
                        TextInput::make('reason')
                            ->label('Motif du bannissement')
                            ->placeholder('ex: Spam, propos inappropriés'),
                        Toggle::make('hide_all_comments')
                            ->label('Masquer tous les commentaires déjà postés')
                            ->default(true),
                    ])
                    ->action(function (User $record, array $data) {
                        $reason = $data['reason'] ?? null;
                        $hideComments = $data['hide_all_comments'] ?? false;
                        $banType = $data['ban_type'] ?? 'comment_only';

                        if ($banType === 'comment_only') {
                            $record->update([
                                'is_comment_banned' => true,
                                'comment_banned_at' => now(),
                                'comment_ban_reason' => $reason,
                            ]);
                            Notification::make()->title('Utilisateur banni des commentaires')->warning()->send();
                        } else {
                            $record->update([
                                'is_banned' => true,
                                'banned_at' => now(),
                                'ban_reason' => $reason,
                            ]);
                            Notification::make()->title('Compte utilisateur banni')->danger()->send();
                        }

                        if ($hideComments) {
                            Comment::where('user_id', $record->id)->update(['is_hidden' => true]);
                        }

                        // Audit log
                        \App\Models\AuditLog::create([
                            'user_id' => auth()->id(),
                            'action' => $banType === 'comment_only' ? 'ban_comments' : 'ban_account',
                            'model_type' => User::class,
                            'model_id' => $record->id,
                            'new_values' => [
                                'target_user' => $record->name,
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
                    ->visible(fn (User $record) => $record->is_banned || $record->is_comment_banned)
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record) => 'Débannir ' . $record->name)
                    ->action(function (User $record) {
                        $record->update([
                            'is_banned' => false,
                            'banned_at' => null,
                            'ban_reason' => null,
                            'is_comment_banned' => false,
                            'comment_banned_at' => null,
                            'comment_ban_reason' => null,
                        ]);

                        // Audit log
                        \App\Models\AuditLog::create([
                            'user_id' => auth()->id(),
                            'action' => 'unban',
                            'model_type' => User::class,
                            'model_id' => $record->id,
                            'new_values' => [
                                'target_user' => $record->name,
                            ],
                            'ip_address' => request()->ip(),
                        ]);

                        Notification::make()->title('Utilisateur débanni avec succès')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
