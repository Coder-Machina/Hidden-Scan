<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Comment;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

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
                    ->weight('bold')
                    ->description(fn (User $record): ?string => $record->username ? '@' . $record->username : null)
                    ->url(fn (User $record): string => route('profile.public', $record->username ?: $record->id))
                    ->openUrlInNewTab(),
                TextColumn::make('roles.name')
                    ->label('Rôles')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'owner', 'admin', 'administrateur' => 'danger', // Rouge
                        'modo', 'modérateur', 'moderateur' => 'purple', // Violet
                        'uploader' => 'info', // Bleu ciel
                        default => 'gray',
                    })
                    ->placeholder('LECTEUR'),
                TextColumn::make('email')
                    ->label('Adresse email')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->getStateUsing(function (User $record) {
                        if ($record->isBanned()) {
                            return $record->banned_until
                                ? 'Banni temp. (' . $record->banned_until->translatedFormat('d/m H:i') . ')'
                                : 'Compte banni';
                        }
                        if ($record->isCommentBanned()) {
                            return $record->comment_banned_until
                                ? 'Banni comm. (' . $record->comment_banned_until->translatedFormat('d/m H:i') . ')'
                                : 'Banni des comm.';
                        }
                        return 'Actif';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_starts_with($state, 'Banni temp.'), $state === 'Compte banni' => 'danger',
                        str_starts_with($state, 'Banni comm.'), $state === 'Banni des comm.' => 'warning',
                        default => 'success',
                    }),
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
                TextColumn::make('pass_code')
                    ->label('Pass Secret')
                    ->searchable()
                    ->copyable()
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->color('danger')
                    ->placeholder('Compte classique')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_ip_address')
                    ->label('IP')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading('Aucun membre trouvé')
            ->emptyStateDescription('La liste des utilisateurs enregistrés s\'affichera ici.')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('public_profile')
                    ->label('Profil public')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->tooltip('Voir le profil sur le site public')
                    ->url(fn (User $record): string => route('profile.public', $record->username ?: $record->id))
                    ->openUrlInNewTab(),
                ViewAction::make(),
                EditAction::make(),
                Action::make('ban')
                    ->label('Bannir')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (User $record) => !$record->isBanned() && !$record->isStaffOrAdmin())
                    ->modalHeading(fn (User $record) => 'Sanctionner ' . $record->name)
                    ->schema([
                        Radio::make('ban_type')
                            ->label('Type de sanction')
                            ->options([
                                'full_account' => 'Bannissement Total (Accès au site et compte totalement bloqués - Radical)',
                                'comment_only' => 'Bannissement des commentaires uniquement (le membre peut lire mais ne peut plus commenter)',
                            ])
                            ->default('full_account')
                            ->required(),
                        Select::make('duration')
                            ->label('Durée du bannissement')
                            ->options([
                                '1_hour' => '1 heure',
                                '3_hours' => '3 heures',
                                '6_hours' => '6 heures',
                                '12_hours' => '12 heures',
                                '24_hours' => '24 heures (1 jour)',
                                '3_days' => '3 jours',
                                '7_days' => '7 jours (1 semaine)',
                                '14_days' => '14 jours (2 semaines)',
                                '30_days' => '30 jours (1 mois)',
                                'custom' => 'Personnalisé (sélectionner date et heure)',
                                'permanent' => 'Définitif (Permanent)',
                            ])
                            ->default('permanent')
                            ->live()
                            ->required(),
                        DateTimePicker::make('custom_banned_until')
                            ->label('Fin du bannissement personnalisé')
                            ->visible(fn ($get) => $get('duration') === 'custom')
                            ->required(fn ($get) => $get('duration') === 'custom')
                            ->minDate(now()),
                        Toggle::make('ban_ip')
                            ->label('Bloquer également l\'adresse IP associée (bloque l\'accès au site)')
                            ->helperText(fn (User $record) => $record->last_ip_address ? 'Dernière adresse IP enregistrée : ' . $record->last_ip_address : 'Aucune IP enregistrée pour ce compte.')
                            ->default(true),
                        TextInput::make('reason')
                            ->label('Motif du bannissement')
                            ->placeholder('ex: Propos inappropriés, spam, harcèlement'),
                        Toggle::make('hide_all_comments')
                            ->label('Masquer tous les commentaires déjà postés par ce membre')
                            ->default(true),
                    ])
                    ->action(function (User $record, array $data) {
                        $reason = $data['reason'] ?? null;
                        $hideComments = $data['hide_all_comments'] ?? false;
                        $banType = $data['ban_type'] ?? 'full_account';
                        $duration = $data['duration'] ?? 'permanent';
                        $banIp = $data['ban_ip'] ?? false;

                        $bannedUntil = match ($duration) {
                            '1_hour' => now()->addHour(),
                            '3_hours' => now()->addHours(3),
                            '6_hours' => now()->addHours(6),
                            '12_hours' => now()->addHours(12),
                            '24_hours' => now()->addDay(),
                            '3_days' => now()->addDays(3),
                            '7_days' => now()->addDays(7),
                            '14_days' => now()->addDays(14),
                            '30_days' => now()->addDays(30),
                            'custom' => !empty($data['custom_banned_until']) ? \Carbon\Carbon::parse($data['custom_banned_until']) : null,
                            default => null,
                        };

                        if ($banType === 'comment_only') {
                            $record->update([
                                'is_comment_banned' => true,
                                'comment_banned_at' => now(),
                                'comment_banned_until' => $bannedUntil,
                                'comment_ban_reason' => $reason,
                            ]);

                            if ($banIp && $record->last_ip_address && !in_array($record->last_ip_address, ['127.0.0.1', '::1', 'localhost'])) {
                                DB::table('banned_ips')->updateOrInsert(
                                    ['ip_address' => $record->last_ip_address],
                                    [
                                        'ip_hash' => sha1($record->last_ip_address),
                                        'ban_type' => 'comments',
                                        'reason' => $reason ?: 'Commentaires bloqués',
                                        'banned_by' => auth()->id(),
                                        'banned_until' => $bannedUntil,
                                        'updated_at' => now(),
                                        'created_at' => now(),
                                    ]
                                );
                            }

                            Notification::make()->title('Utilisateur banni des commentaires')->warning()->send();
                        } else {
                            $record->update([
                                'is_banned' => true,
                                'banned_at' => now(),
                                'banned_until' => $bannedUntil,
                                'ban_reason' => $reason,
                            ]);

                            if ($banIp && $record->last_ip_address && !in_array($record->last_ip_address, ['127.0.0.1', '::1', 'localhost'])) {
                                DB::table('banned_ips')->updateOrInsert(
                                    ['ip_address' => $record->last_ip_address],
                                    [
                                        'ip_hash' => sha1($record->last_ip_address),
                                        'ban_type' => 'all',
                                        'reason' => $reason ?: 'Compte banni radicalement',
                                        'banned_by' => auth()->id(),
                                        'banned_until' => $bannedUntil,
                                        'updated_at' => now(),
                                        'created_at' => now(),
                                    ]
                                );
                            }

                            Notification::make()->title('Compte banni avec succès (accès bloqué)')->danger()->send();
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
                                'duration' => $duration,
                                'banned_until' => $bannedUntil?->toIso8601String(),
                                'reason' => $reason,
                                'comments_hidden' => $hideComments,
                                'ip_banned' => $banIp ? $record->last_ip_address : null,
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
                            'banned_until' => null,
                            'ban_reason' => null,
                            'is_comment_banned' => false,
                            'comment_banned_at' => null,
                            'comment_banned_until' => null,
                            'comment_ban_reason' => null,
                        ]);

                        if ($record->last_ip_address) {
                            DB::table('banned_ips')
                                ->where('ip_address', $record->last_ip_address)
                                ->orWhere('ip_hash', sha1($record->last_ip_address))
                                ->delete();
                        }

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
