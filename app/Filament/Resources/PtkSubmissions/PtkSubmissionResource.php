<?php

namespace App\Filament\Resources\PtkSubmissions;

use App\Filament\Resources\PtkSubmissions\Pages\ListPtkSubmissions;
use App\Filament\Resources\PtkSubmissions\Pages\ViewPtkSubmission;
use App\Filament\Resources\PtkSubmissions\RelationManagers\StatusHistoriesRelationManager;
use App\Filament\Resources\PtkSubmissions\Schemas\PtkSubmissionInfolist;
use App\Filament\Resources\PtkSubmissions\Tables\PtkSubmissionsTable;
use App\Models\PtkSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Pengajuan PTK di dashboard admin (SPEC.md §5.4). Admin hanya melihat, mengubah status,
 * dan mengekspor — tidak membuat, mengubah isi, atau menghapus pengajuan (SPEC §6).
 */
class PtkSubmissionResource extends Resource
{
    protected static ?string $model = PtkSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?string $modelLabel = 'pengajuan PTK';

    protected static ?string $pluralModelLabel = 'Pengajuan PTK';

    protected static ?string $navigationLabel = 'Pengajuan PTK';

    protected static ?string $slug = 'pengajuan';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return PtkSubmissionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PtkSubmissionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('school');
    }

    public static function getRelations(): array
    {
        return [
            StatusHistoriesRelationManager::class,
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPtkSubmissions::route('/'),
            'view' => ViewPtkSubmission::route('/{record}'),
        ];
    }
}
