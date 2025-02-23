<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Pelanggan;
use App\Models\DataTeknis;
use App\Models\Langganan;
use App\Models\HargaLayanan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

use Filament\Tables\Columns\BadgeColumn;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Invoices';
    protected static ?string $slug = 'invoices';
    protected static ?string $modelLabel = 'Invoice';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('invoice_number')
                    ->label('Nomor Invoice')
                    ->default(fn() => 'INV-' . now()->format('Ymd') . '-' . rand(1000, 9999))
                    ->disabled()
                    ->required(),

                Select::make('pelanggan_id')
                    ->label('Nama Pelanggan')
                    ->options(Pelanggan::pluck('nama', 'id'))
                    ->searchable()
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn ($state, callable $set) => self::updateInvoiceData($set, $state)),

                TextInput::make('id_pelanggan')
                    ->label('ID Pelanggan (Data Teknis)')
                    ->disabled()
                    ->required(),

                TextInput::make('brand')
                    ->label('Brand')
                    ->disabled()
                    ->required(),

                TextInput::make('total_harga')
                    ->label('Total Harga')
                    ->disabled()
                    ->required(),

                TextInput::make('no_telp')
                    ->label('Nomor Telepon')
                    ->disabled()
                    ->required(),

                TextInput::make('email')
                    ->label('Email')
                    ->disabled()
                    ->required(),

                DatePicker::make('tgl_invoice')
                    ->label('Tanggal Invoice Dibuat')
                    ->default(now())
                    ->required(),

                DatePicker::make('tgl_jatuh_tempo')
                    ->label('Tanggal Jatuh Tempo')
                    ->required(),
            ]);
    }

    public static function updateInvoiceData(callable $set, $pelangganId)
    {
        if ($pelangganId) {
            $pelanggan = \App\Models\Pelanggan::find($pelangganId);
            $dataTeknis = \App\Models\DataTeknis::where('pelanggan_id', $pelangganId)->first();
            $langganan = \App\Models\Langganan::where('pelanggan_id', $pelangganId)->first();
    
            if ($pelanggan) {
                $set('no_telp', $pelanggan->no_telp);
                $set('email', $pelanggan->email);
            }
    
            if ($dataTeknis) {
                $set('id_pelanggan', $dataTeknis->id_pelanggan);
            }
    
            if ($langganan) {
                $hargaLayanan = \App\Models\HargaLayanan::where('id_brand', $langganan->id_brand)->first();
                if ($hargaLayanan) {
                    $set('brand', $hargaLayanan->brand);
                    $set('total_harga', $langganan->total_harga_layanan_x_pajak);
                }
            }
        }
    }
    


    public static function table(Tables\Table $table): Tables\Table
{
    return $table
        ->columns([
            TextColumn::make('invoice_number')->label('Nomor Invoice')->sortable(),
            TextColumn::make('pelanggan.nama')->label('Pelanggan')->sortable(),

            // 🔥 Tampilkan Nama Brand Bukan ID
            TextColumn::make('brand')
                ->label('Brand')
                ->formatStateUsing(fn ($state) => HargaLayanan::where('id_brand', $state)->value('brand'))
                ->sortable()
                ->searchable(),

            TextColumn::make('total_harga')->label('Total Harga')->money('IDR')->sortable(),
            TextColumn::make('tgl_invoice')->label('Tanggal Invoice')->date(),
            TextColumn::make('tgl_jatuh_tempo')->label('Tanggal Jatuh Tempo')->date(),

            BadgeColumn::make('status_invoice')
                ->label('Status Pembayaran')
                ->formatStateUsing(fn ($state) => match ($state) {
                    'Belum Dibayar' => 'Belum Dibayar',
                    'Lunas' => 'Lunas',
                    'Kadaluarsa' => 'Kadaluarsa',
                    default => 'Tidak Diketahui',
                })
                ->color(fn ($state) => match ($state) {
                    'Belum Dibayar' => 'warning',
                    'Lunas' => 'success',
                    'Kadaluarsa' => 'danger',
                    default => 'gray',
                }),
        ])
        ->actions([
            EditAction::make(),
            DeleteAction::make(),
        ]);
}


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
