<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class XenditWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Log payload untuk debugging
        Log::info('Xendit Webhook Received', ['payload' => $request->all()]);

        // Validasi payload
        $data = $this->validateWebhookPayload($request);

        // Cari invoice berdasarkan external_id
        $invoice = $this->findInvoiceByExternalId($data['external_id']);

        // Perbarui status invoice
        return $this->updateInvoiceFromWebhook($invoice, $data);
    }

    private function validateWebhookPayload(Request $request): array
    {
        $data = $request->all();

        // Field yang wajib ada di payload webhook
        $requiredFields = ['id', 'external_id', 'status'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                Log::error('Invalid webhook payload: Missing required field', ['field' => $field]);
                throw new \Exception("Field webhook tidak lengkap: $field");
            }
        }

        return $data;
    }

    private function findInvoiceByExternalId(string $externalId): Invoice
    {
        $invoice = Invoice::where('xendit_external_id', $externalId)->first();

        if (!$invoice) {
            Log::error('Invoice tidak ditemukan', ['external_id' => $externalId]);
            throw new \Exception('Invoice tidak ditemukan');
        }

        return $invoice;
    }

    private function updateInvoiceFromWebhook(Invoice $invoice, array $data)
    {
        return DB::transaction(function () use ($invoice, $data) {
            // Mapping status Xendit ke status internal
            $newStatus = $this->mapXenditStatus($data['status']);

            // Update invoice
            $invoice->status_invoice = $newStatus;
            $invoice->xendit_id = $data['id']; // Simpan Xendit ID
            $invoice->xendit_external_id = $data['external_id']; // Simpan external ID

            // Jika ada informasi pembayaran, simpan
            if (isset($data['paid_amount'])) {
                $invoice->paid_amount = $data['paid_amount'];
            }

            if (isset($data['paid_at'])) {
                $invoice->paid_at = $data['paid_at'];
            }

            // Simpan perubahan
            $invoice->save();

            // Log perubahan status
            Log::info('Invoice status updated from webhook', [
                'invoice_number' => $invoice->invoice_number,
                'new_status' => $newStatus,
                'xendit_status' => $data['status']
            ]);

            return response()->json([
                'message' => 'Invoice berhasil diperbarui',
                'status' => $newStatus
            ]);
        });
    }

    private function mapXenditStatus(string $xenditStatus): string
    {
        $statusMap = [
            'PAID' => 'Lunas',
            'SETTLED' => 'Selesai',
            'EXPIRED' => 'Kadaluarsa',
            'PENDING' => 'Menunggu Pembayaran'
        ];

        return $statusMap[$xenditStatus] ?? 'Tidak Diketahui';
    }
}