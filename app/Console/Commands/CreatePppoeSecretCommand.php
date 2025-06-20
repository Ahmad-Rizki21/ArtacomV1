<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DataTeknis;
use App\Services\MikrotikSubscriptionManager;

class CreatePppoeSecretCommand extends Command
{
    protected $signature = 'mikrotik:create-secret {dataTeknisId?}';

    protected $description = 'Create PPPoE secret in Mikrotik for given DataTeknis ID or the latest record if no ID is provided';

    public function handle(MikrotikSubscriptionManager $mikrotikManager)
    {
        $id = $this->argument('dataTeknisId');

        if ($id) {
            $dataTeknis = DataTeknis::find($id);
            if (!$dataTeknis) {
                $this->error("DataTeknis with ID $id not found.");
                return 1;
            }
        } else {
            $dataTeknis = DataTeknis::latest()->first();
            if (!$dataTeknis) {
                $this->error("No DataTeknis records found.");
                return 1;
            }
            $this->info("Processing the latest DataTeknis record with ID: " . $dataTeknis->id);
        }

        $result = $mikrotikManager->createPppoeSecretOnMikrotik($dataTeknis);

        if ($result) {
            $this->info("PPPoE secret created successfully for DataTeknis ID " . $dataTeknis->id . ".");
            return 0;
        } else {
            $this->error("Failed to create PPPoE secret for DataTeknis ID " . $dataTeknis->id . ".");
            return 1;
        }
    }
}