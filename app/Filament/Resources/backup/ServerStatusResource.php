<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerStatusResource\Pages;
use App\Filament\Resources\ServerStatusResource\RelationManagers;
use App\Models\ServerStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServerStatusResource extends Resource
{
    protected static ?string $model = ServerStatus::class;
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Server Status';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('cpu_usage')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('memory_usage')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('load_1m')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('load_5m')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('load_15m')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('uptime')
                    ->maxLength(191)
                    ->default(null),
                Forms\Components\TextInput::make('process_count')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('running_processes')
                    ->numeric()
                    ->default(null),
                Forms\Components\Textarea::make('raw_data')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('snapshot_time'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cpu_usage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('memory_usage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('load_1m')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('load_5m')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('load_15m')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('uptime')
                    ->searchable(),
                Tables\Columns\TextColumn::make('process_count')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('running_processes')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('snapshot_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerStatuses::route('/'),
            // 'create' => Pages\CreateServerStatus::route('/create'),
            // 'edit' => Pages\EditServerStatus::route('/{record}/edit'),
        ];
    }
}
