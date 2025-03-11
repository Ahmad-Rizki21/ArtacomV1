<?php

namespace App\Filament\Resources;

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
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\Column;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Invoices';
    protected static ?string $navigationGroup = 'Billing'; // Mengelompokkan dalam grup "FTTH"

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
                    ->afterStateUpdated(function ($state, callable $set) {
                        try {
                            self::updateInvoiceData($set, $state);
                        } catch (\Exception $e) {
                            Log::error('Error updating invoice data', [
                                'pelanggan_id' => $state,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }),

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
                    ->numeric()
                    ->disabled()
                    ->required(),

                TextInput::make('no_telp')
                    ->label('Nomor Telepon')
                    ->disabled()
                    ->required(),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->disabled()
                    ->required(),

                DatePicker::make('tgl_invoice')
                    ->label('Tanggal Invoice Dibuat')
                    ->default(now())
                    ->required(),

                    DatePicker::make('tgl_jatuh_tempo')
                    ->label('Tanggal Jatuh Tempo')
                    ->required()
                    ->default(function (callable $get) {
                        // Ambil tanggal invoice
                        $invoiceDate = $get('tgl_invoice') ?? now();
                        
                        // Kembalikan 1 bulan setelah tanggal invoice
                        return Carbon::parse($invoiceDate)->addMonth();
                    })
                    ->minDate(fn ($get) => $get('tgl_invoice') ?? now())
                    ->helperText('Tanggal jatuh tempo otomatis dihitung 1 bulan setelah tanggal invoice'),

                    

                // Tambahkan field Xendit External ID
                TextInput::make('xendit_external_id')
                    ->label('Xendit External ID')
                    ->disabled()
                    ->helperText('Akan diisi otomatis saat membuat invoice di Xendit'),

                // Tambahkan field Xendit ID
                TextInput::make('xendit_id')
                    ->label('Xendit ID')
                    ->disabled()
                    ->helperText('Akan diisi otomatis saat pembayaran'),

                // Optional: Payment Link
                TextInput::make('payment_link')
                    ->label('Link Pembayaran')
                    ->url()
                    ->disabled()
                    ->helperText('Link pembayaran yang dibuat oleh Xendit')
            ]);
    }

   
     // Method untuk mengupdate data invoice berdasarkan pelanggan yang dipilih
     public static function updateInvoiceData(callable $set, $pelangganId)
{
    try {
        $pelanggan = Pelanggan::findOrFail($pelangganId);
        $dataTeknis = DataTeknis::where('pelanggan_id', $pelangganId)->firstOrFail();
        $langganan = Langganan::where('pelanggan_id', $pelangganId)->firstOrFail();
        
        // Set data pelanggan
        $set('no_telp', $pelanggan->no_telp);
        $set('email', $pelanggan->email);
        $set('id_pelanggan', $dataTeknis->id_pelanggan);

        // Ambil harga layanan
        $hargaLayanan = HargaLayanan::where('id_brand', $langganan->id_brand)->firstOrFail();
        $set('brand', $hargaLayanan->brand);
        $set('total_harga', $langganan->total_harga_layanan_x_pajak);

        // Set tanggal invoice ke hari ini
        $invoiceDate = now();
        $set('tgl_invoice', $invoiceDate);

        // Set tanggal jatuh tempo 1 bulan dari tanggal invoice
        $tglJatuhTempo = Carbon::parse($invoiceDate)->addMonth();
        $set('tgl_jatuh_tempo', $tglJatuhTempo);
    } catch (\Exception $e) {
        Log::error('Error updating invoice data', [
            'pelanggan_id' => $pelangganId,
            'error' => $e->getMessage()
        ]);
    }
}


public static function afterCreate($record)
    {
        // Cek jika invoice berhasil disimpan
        if ($record instanceof Invoice) {
            Notification::make()
                ->success()
                ->title('📄 Invoice Berhasil Dibuat!')
                ->body('Invoice baru telah berhasil dibuat.')
                ->send();
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Nomor Invoice')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pelanggan.nama')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brand')
                    ->label('Brand')
                    ->getStateUsing(fn ($record) => 
                        HargaLayanan::where('id_brand', $record->brand)->value('brand') ?? $record->brand
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable(),

                    TextColumn::make('tgl_invoice')
                ->label('Tanggal Invoice')
                ->date()
                ->sortable(),

            TextColumn::make('tgl_jatuh_tempo')
                ->label('Tanggal Jatuh Tempo')
                ->date()
                ->sortable(),

                TextColumn::make('status_invoice')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Menunggu Pembayaran' => 'warning',
                        'Lunas' => 'success',
                        'Kadaluarsa' => 'danger',
                        'Selesai' => 'primary',
                        default => 'gray',
                    }),

                // Tambahkan kolom Xendit External ID
                TextColumn::make('xendit_external_id')
                    ->label('Xendit External ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // Bisa disembunyikan

                // Tambahkan kolom Xendit ID
                TextColumn::make('xendit_id')
                    ->label('Xendit ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true), // Bisa disembunyikan

                // Optional: Kolom Payment Link
                TextColumn::make('payment_link')
                    ->label('Link Pembayaran')
                    ->url(fn ($record) => $record->payment_link ?? '')
                    ->toggleable(isToggledHiddenByDefault: true)
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Hapus Invoice')
                    ->modalDescription('Apakah Anda yakin ingin menghapus invoice ini? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->modalCancelActionLabel('Batal')
                    ->successNotificationTitle('🗑️ Invoice Berhasil Dihapus!')
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('🗑️ Invoice Telah Dihapus!')
                            ->body('Invoice ini telah dihapus secara permanen.')
                            ->send();
                    }),
                // Optional: Tambahkan action untuk membuka payment link
                Tables\Actions\Action::make('open_payment_link')
                    ->label('Buka Link Pembayaran')
                    ->icon('heroicon-o-link')
                    ->url(fn ($record) => $record->payment_link ?? '')
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->payment_link)),
            ])
            ->defaultSort('created_at', 'desc'); // Urutkan berdasarkan invoice terbaru
    }

    public static function getPages(): array
    {
        return [
            'index' => InvoiceResource\Pages\ListInvoices::route('/'),
            'create' => InvoiceResource\Pages\CreateInvoice::route('/create'),
            'edit' => InvoiceResource\Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}