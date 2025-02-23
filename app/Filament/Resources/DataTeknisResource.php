<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataTeknisResource\Pages;
use App\Models\DataTeknis;
use App\Models\Pelanggan;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;

class DataTeknisResource extends Resource
{
    protected static ?string $model = DataTeknis::class;

    protected static ?string $navigationLabel = 'Data Teknis';
    protected static ?string $navigationIcon = 'heroicon-o-server'; // Ikon yang lebih sesuai
    protected static ?string $navigationGroup = 'FTTH'; // Mengelompokkan dalam grup "FTTH"

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Select::make('pelanggan_id')
                ->label('Pelanggan')
                ->options(Pelanggan::pluck('nama', 'id')) // 🔥 Gunakan 'id' karena 'id_pelanggan' tidak ada!
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(fn ($state, callable $set) => 
                    $set('id_pelanggan', Pelanggan::where('id', $state)->value('id')) // 🔥 Gunakan 'id' yang ada di 'pelanggan'
                        ),

                        TextInput::make('id_vlan')
                        ->label('ID VLAN')
                        ->required(), // ✅ Pastikan ini wajib diisi

                        TextInput::make('id_pelanggan')
                        ->label('ID Pelanggan')
                        ->required(),

            

                TextInput::make('password_pppoe')
                    ->label('Password PPPoE')
                    ->password()
                    ->required(),

                TextInput::make('ip_pelanggan')
                    ->label('IP Pelanggan')
                    ->required(),

                TextInput::make('profile_pppoe')
                    ->label('Profile PPPoE')
                    ->required(),

                TextInput::make('olt')
                    ->label('OLT')
                    ->required(),

                TextInput::make('pon')
                    ->label('PON')
                    ->required(),

                TextInput::make('otb')
                    ->label('OTB')
                    ->required(),

                TextInput::make('odc')
                    ->label('ODC')
                    ->required(),

                TextInput::make('odp')
                    ->label('ODP')
                    ->required(),

                TextInput::make('onu_power')
                    ->label('ONU Power')
                    ->required(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id_vlan')->label('ID VLAN')->sortable()->searchable(),
                TextColumn::make('id_pelanggan')->label('ID Pelanggan')->sortable()->searchable(),
                TextColumn::make('password_pppoe')->label('Password PPPoE')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_pelanggan')->label('IP Pelanggan')->sortable(),
                TextColumn::make('profile_pppoe')->label('Profile PPPoE'),
                TextColumn::make('olt')->label('OLT'),
                TextColumn::make('pon')->label('PON'),
                TextColumn::make('otb')->label('OTB'),
                TextColumn::make('odc')->label('ODC'),
                TextColumn::make('odp')->label('ODP'),
                TextColumn::make('onu_power')->label('ONU Power'),
            ])
            ->filters([]) // Tambahkan filter jika diperlukan
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }


    protected static function boot()
{
    parent::boot();
    static::creating(function ($dataTeknis) {
        if (!$dataTeknis->id_pelanggan) {
            $dataTeknis->id_pelanggan = 'UNKNOWN'; // 🔥 Default jika kosong
        }
    });
}


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataTeknis::route('/'),
            'create' => Pages\CreateDataTeknis::route('/create'),
            'edit' => Pages\EditDataTeknis::route('/{record}/edit'),
        ];
    }
}
