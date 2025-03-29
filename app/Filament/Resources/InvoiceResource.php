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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\ViewField;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Invoices';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?string $slug = 'invoices';
    protected static ?string $modelLabel = 'Invoice';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Header Section with Invoice Number and Dates
                Section::make('Informasi Invoice')
                    ->description('Nomor invoice dan informasi tanggal')
                    ->icon('heroicon-o-document')
                    ->schema([
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                TextInput::make('invoice_number')
                                    ->label('Nomor Invoice')
                                    ->default(fn() => 'INV-' . now()->format('Ymd') . '-' . rand(1000, 9999))
                                    ->disabled()
                                    ->required(),
                                
                                DatePicker::make('tgl_invoice')
                                    ->label('Tanggal Invoice')
                                    ->default(now())
                                    ->required()
                                    ->displayFormat('d M Y')
                                    ->closeOnDateSelection(),
                                
                                    DatePicker::make('tgl_jatuh_tempo')
                                    ->label('Tanggal Jatuh Tempo')
                                    ->required()
                                    ->default(function (callable $get) {
                                        // Coba ambil dari pelanggan_id
                                        $pelangganId = $get('pelanggan_id');
                                        if ($pelangganId) {
                                            $langganan = Langganan::where('pelanggan_id', $pelangganId)->first();
                                            if ($langganan && $langganan->tgl_jatuh_tempo) {
                                                return Carbon::parse($langganan->tgl_jatuh_tempo);
                                            }
                                        }
                                        
                                        // Jika tidak ada, gunakan tanggal invoice
                                        $invoiceDate = $get('tgl_invoice') ?? now();
                                        return Carbon::parse($invoiceDate);
                                    })
                                    ->displayFormat('d M Y')
                                    ->closeOnDateSelection()
                                    ->helperText('Default menggunakan tanggal jatuh tempo dari langganan'),
                            ]),
                    ]),
                
                // Customer Information Section
                Section::make('Informasi Pelanggan')
                    ->description('Pilih pelanggan untuk mengisi data secara otomatis')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Select::make('pelanggan_id')
                            ->label('Nama Pelanggan')
                            ->options(Pelanggan::pluck('nama', 'id'))
                            ->searchable()
                            ->preload()
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
                        
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                TextInput::make('id_pelanggan')
                                    ->label('ID Pelanggan')
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
                            ]),
                    ]),
                
                // Billing Information Section
                Section::make('Informasi Pembayaran')
                    ->description('Detail layanan dan pembayaran')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('brand')
                                    ->label('Brand')
                                    ->disabled()
                                    ->required(),
                                
                                TextInput::make('total_harga')
                                    ->label('Total Harga')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->required(),
                            ]),

                        // Visual separator
                        Placeholder::make('payment_details_separator')
                            ->label('Informasi Xendit')
                            ->content('Data ini akan diisi otomatis ketika integrasi dengan Xendit dijalankan.')
                            ->columnSpan(2),
                        
                        Grid::make()
                            ->columns(3)
                            ->schema([
                                TextInput::make('xendit_external_id')
                                    ->label('Xendit External ID')
                                    ->disabled()
                                    ->helperText('Diisi otomatis saat invoice dibuat di Xendit'),
                                
                                TextInput::make('xendit_id')
                                    ->label('Xendit ID')
                                    ->disabled()
                                    ->helperText('Diisi otomatis saat pembayaran'),
                                
                                TextInput::make('payment_link')
                                    ->label('Link Pembayaran')
                                    ->url()
                                    ->disabled()
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('open_link')
                                            ->icon('heroicon-m-arrow-top-right-on-square')
                                            ->url(fn ($state) => $state ?? '#')
                                            ->openUrlInNewTab()
                                            ->visible(fn ($state) => filled($state))
                                    )
                                    ->helperText('Link pembayaran dari Xendit'),
                            ]),
                    ]),

                // Status Section (untuk Edit Form)
                Section::make('Status Invoice')
                    ->description('Status pembayaran invoice')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Select::make('status_invoice')
                            ->label('Status Pembayaran')
                            ->options([
                                'Menunggu Pembayaran' => 'Menunggu Pembayaran',
                                'Lunas' => 'Lunas',
                                'Kadaluarsa' => 'Kadaluarsa',
                                'Selesai' => 'Selesai',
                            ])
                            ->default('Menunggu Pembayaran')
                            ->required(),

                        Placeholder::make('status_note')
                            ->content('Catatan: Status akan otomatis diperbarui ketika terintegrasi dengan Xendit.')
                            ->columnSpan(2),
                    ])
                    ->hidden(fn ($livewire) => $livewire instanceof CreateInvoice),
            ]);
    }

    // Method untuk mengupdate data invoice berdasarkan pelanggan
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

            // Set tanggal jatuh tempo dari langganan jika ada, jika tidak gunakan tanggal invoice
            if ($langganan->tgl_jatuh_tempo) {
                $set('tgl_jatuh_tempo', Carbon::parse($langganan->tgl_jatuh_tempo));
            } else {
                $set('tgl_jatuh_tempo', $invoiceDate);
            }
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
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

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
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('tgl_jatuh_tempo')
                    ->label('Tanggal Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => 
                        Carbon::parse($record->tgl_jatuh_tempo)->isPast() && 
                        $record->status_invoice !== 'Lunas' && 
                        $record->status_invoice !== 'Selesai' 
                            ? 'danger' 
                            : null
                    ),

                TextColumn::make('status_invoice')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Menunggu Pembayaran' => 'warning',
                        'Lunas' => 'success',
                        'Kadaluarsa' => 'danger',
                        'Selesai' => 'primary',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'Menunggu Pembayaran' => 'heroicon-o-clock',
                        'Lunas' => 'heroicon-o-check-circle',
                        'Kadaluarsa' => 'heroicon-o-x-circle',
                        'Selesai' => 'heroicon-o-check-badge',
                        default => null,
                    }),

                TextColumn::make('xendit_external_id')
                    ->label('Xendit External ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('xendit_id')
                    ->label('Xendit ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('payment_link')
                    ->label('Link Pembayaran')
                    ->url(fn ($record) => $record->payment_link ?? '')
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->visible(fn ($record) => !empty($record->payment_link)),
                    
                // Menambahkan kolom created_at sebagai kolom tersembunyi untuk pengurutan
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_invoice')
                    ->label('Status Pembayaran')
                    ->options([
                        'Menunggu Pembayaran' => 'Menunggu Pembayaran',
                        'Lunas' => 'Lunas',
                        'Kadaluarsa' => 'Kadaluarsa',
                        'Selesai' => 'Selesai',
                    ]),
                SelectFilter::make('pelanggan')
                    ->label('Pelanggan')
                    ->relationship('pelanggan', 'nama')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('overdue')
                    ->label('Jatuh Tempo')
                    ->options([
                        'overdue' => 'Lewat Jatuh Tempo',
                        'upcoming' => 'Jatuh Tempo Minggu Ini',
                    ])
                    ->query(function ($query, $data) {
                        if ($data['value'] === 'overdue') {
                            return $query->where('tgl_jatuh_tempo', '<', now())
                                ->whereNotIn('status_invoice', ['Lunas', 'Selesai']);
                        }
                        
                        if ($data['value'] === 'upcoming') {
                            $startOfWeek = now()->startOfWeek();
                            $endOfWeek = now()->endOfWeek();
                            
                            return $query->whereBetween('tgl_jatuh_tempo', [$startOfWeek, $endOfWeek])
                                ->whereNotIn('status_invoice', ['Lunas', 'Selesai']);
                        }
                        
                        return $query;
                    })
            ])
            ->actions([
                EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                    
                Action::make('open_payment_link')
                    ->label('Buka Link Pembayaran')
                    ->icon('heroicon-o-link')
                    ->url(fn ($record) => $record->payment_link ?? '#')
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->payment_link)),

                Action::make('view')
                    ->label('Lihat Invoice')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Invoice $record) => route('invoice.view', $record->id))
                    ->openUrlInNewTab(),
                    
                Action::make('copy_payment_link')
                    ->label('Salin Link')
                    ->icon('heroicon-o-clipboard')
                    ->action(fn ($record) => null) // Handled by JavaScript
                    ->extraAttributes([
                        'x-data' => '',
                        'x-on:click' => "
                            navigator.clipboard.writeText('{record.payment_link}');
                            window.dispatchEvent(new CustomEvent('notification', {
                                title: 'Link disalin!',
                                body: 'Link pembayaran telah disalin ke clipboard.',
                                icon: 'heroicon-o-clipboard',
                                iconColor: 'success',
                                timeout: 3000,
                            })
                        "
                    ])
                    ->visible(fn ($record) => !empty($record->payment_link)),
                    
                Action::make('mark_as_paid')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($record) {
                        $record->status_invoice = 'Lunas';
                        $record->save();
                        Notification::make()
                            ->success()
                            ->title('💰 Invoice Ditandai Lunas!')
                            ->body("Invoice {$record->invoice_number} telah ditandai sebagai lunas.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status_invoice === 'Menunggu Pembayaran'),
                    
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Hapus Invoice')
                    ->modalDescription('Apakah Anda yakin ingin menghapus invoice ini? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->modalCancelActionLabel('Batal')
                    ->successNotificationTitle('🗑️ Invoice Berhasil Dihapus!')
                    ->after(function () {
                        Notification::make()
                            ->success()
                            ->title('🗑️ Invoice Telah Dihapus!')
                            ->body('Invoice ini telah dihapus secara permanen.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_bulk_as_paid')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->status_invoice === 'Menunggu Pembayaran') {
                                $record->status_invoice = 'Lunas';
                                $record->save();
                                $count++;
                            }
                        }
                        
                        Notification::make()
                            ->success()
                            ->title("💰 {$count} Invoice Ditandai Lunas!")
                            ->body("Berhasil memperbarui status {$count} invoice menjadi lunas.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Beberapa Invoice Sebagai Lunas')
                    ->modalDescription('Apakah Anda yakin ingin menandai semua invoice yang dipilih sebagai lunas?')
                    ->modalSubmitActionLabel('Ya, Tandai Lunas')
                    ->deselectRecordsAfterCompletion(),
                    
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Hapus Beberapa Invoice')
                    ->modalDescription('Apakah Anda yakin ingin menghapus semua invoice yang dipilih? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Ya, Hapus Semua')
                    ->modalCancelActionLabel('Batal')
                    ->successNotificationTitle('🗑️ Invoice Berhasil Dihapus!')
            ])
            // Mengubah defaultSort untuk menggunakan created_at dengan urutan descending (terbaru di atas)
            ->defaultSort('created_at', 'desc')
            // Membuat pengurutan tetap konsisten saat polling
            ->persistSortInSession()
            ->poll('60s'); // Refresh data setiap 60 detik
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