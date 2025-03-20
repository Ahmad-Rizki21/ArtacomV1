<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Exports\InvoicesExport;
use Maatwebsite\Excel\Facades\Excel;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export')
            ->label('Export Excel')
            ->icon('heroicon-o-document-arrow-down')
            ->action(function () {
                return Excel::download(new InvoicesExport, 'invoices-' . date('Y-m-d') . '.xlsx');
            })
            ->color('success')
        ];
    }
}
