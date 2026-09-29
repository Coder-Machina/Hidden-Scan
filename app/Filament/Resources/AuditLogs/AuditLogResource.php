<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $modelLabel = "journal d'audit";

    protected static ?string $pluralModelLabel = "journaux d'audit";

    protected static ?string $navigationLabel = "Journaux d'audit";

    protected static ?int $navigationSort = 999;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'Admin', 'owner', 'Owner']) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Détails de l\'action')
                ->icon('heroicon-o-information-circle')
                ->columns(3)
                ->schema([
                    TextEntry::make('action_label')
                        ->label('Action')
                        ->badge()
                        ->color(fn (AuditLog $record): string => $record->action_color),
                    TextEntry::make('user.name')
                        ->label('Exécuté par')
                        ->placeholder('Système')
                        ->icon('heroicon-o-user')
                        ->weight('bold'),
                    TextEntry::make('created_at')
                        ->label('Date & heure')
                        ->dateTime('d/m/Y à H:i:s')
                        ->icon('heroicon-o-clock'),
                    TextEntry::make('model_name')
                        ->label('Type de ressource')
                        ->badge()
                        ->color('gray'),
                    TextEntry::make('manga_name')
                        ->label('Œuvre concernée')
                        ->placeholder('—')
                        ->icon('heroicon-o-book-open')
                        ->weight('bold')
                        ->color('danger'),
                    TextEntry::make('target_title')
                        ->label('Élément concerné')
                        ->placeholder('—')
                        ->icon('heroicon-o-document-text'),
                    TextEntry::make('model_id')
                        ->label('ID de la ressource')
                        ->placeholder('—'),
                    TextEntry::make('ip_address')
                        ->label('Adresse IP')
                        ->placeholder('—')
                        ->icon('heroicon-o-globe-alt'),
                ]),

            Section::make('Anciennes valeurs (avant modification)')
                ->icon('heroicon-o-arrow-uturn-left')
                ->collapsible()
                ->collapsed()
                ->visible(fn (AuditLog $record): bool => !empty($record->old_values))
                ->schema([
                    KeyValueEntry::make('formatted_old_values')
                        ->label('Valeurs précédentes')
                        ->keyLabel('Propriété')
                        ->valueLabel('Ancienne valeur')
                        ->columnSpanFull(),
                ]),

            Section::make('Nouvelles valeurs / Détails enregistrés')
                ->icon('heroicon-o-arrow-right')
                ->collapsible()
                ->visible(fn (AuditLog $record): bool => !empty($record->new_values))
                ->schema([
                    KeyValueEntry::make('formatted_new_values')
                        ->label('Nouvelles valeurs')
                        ->keyLabel('Propriété')
                        ->valueLabel('Nouvelle valeur')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn (AuditLog $record): string => $record->created_at->diffForHumans()),
                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->placeholder('Système')
                    ->icon('heroicon-o-user')
                    ->weight('bold'),
                TextColumn::make('action_label')
                    ->label('Action')
                    ->badge()
                    ->color(fn (AuditLog $record): string => $record->action_color)
                    ->searchable(query: function ($query, string $search) {
                        $query->where('action', 'like', "%{$search}%");
                    }),
                TextColumn::make('model_name')
                    ->label('Type')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('manga_name')
                    ->label('Œuvre')
                    ->placeholder('—')
                    ->weight('medium')
                    ->limit(25),
                TextColumn::make('target_title')
                    ->label('Élément / Contexte')
                    ->placeholder('—')
                    ->limit(65)
                    ->tooltip(fn (AuditLog $record): ?string => $record->target_title)
                    ->wrap(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading('Aucun journal d\'audit')
            ->emptyStateDescription('Toutes les actions d\'administration et de modération apparaîtront ici.')
            ->filters([
                SelectFilter::make('action')
                    ->label('Type d\'action')
                    ->multiple()
                    ->options([
                        'Contenu' => [
                            'created' => 'Création',
                            'updated' => 'Modification',
                            'deleted' => 'Suppression',
                            'published_chapter' => 'Publication chapitre',
                            'controlled_chapter' => 'Contrôle chapitre',
                            'bulk_published' => 'Publication en masse',
                            'manga_created' => 'Œuvre ajoutée',
                            'manga_updated' => 'Œuvre modifiée',
                            'manga_deleted' => 'Œuvre supprimée',
                        ],
                        'Modération' => [
                            'ban_account' => 'Bannissement compte',
                            'ban_comments' => 'Bannissement commentaires',
                            'unban' => 'Débannissement',
                            'comment_hidden' => 'Commentaire masqué',
                            'comment_restored' => 'Commentaire restauré',
                            'comment_deleted' => 'Commentaire supprimé',
                        ],
                    ]),
                SelectFilter::make('model_type')
                    ->label('Type de ressource')
                    ->options([
                        'App\Models\Chapter' => 'Chapitre',
                        'App\Models\Manga' => 'Œuvre',
                        'App\Models\Comment' => 'Commentaire',
                        'App\Models\User' => 'Utilisateur',
                        'App\Models\Artist' => 'Artiste',
                        'App\Models\Author' => 'Auteur',
                        'App\Models\Genre' => 'Genre',
                        'App\Models\Tag' => 'Tag',
                        'App\Models\CommentReport' => 'Signalement',
                        'App\Models\BannedIp' => 'IP Bannie',
                    ]),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()->label('Afficher'),
            ])
            ->toolbarActions([
                // Read-only, no bulk actions
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
