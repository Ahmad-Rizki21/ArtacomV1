<?php

   namespace App\Observers;

   use App\Models\DataTeknis;
   use Illuminate\Support\Facades\Artisan;
   use Illuminate\Support\Facades\Log;

   class DataTeknisObserver
   {
       public function created(DataTeknis $dataTeknis)
       {
           Log::info('DataTeknis created, triggering mikrotik:create-secret for ID: ' . $dataTeknis->id, [
               'data_teknis' => $dataTeknis->toArray()
           ]);

           try {
               Artisan::call('mikrotik:create-secret ' . $dataTeknis->id);
               Log::info('Command mikrotik:create-secret executed successfully for DataTeknis ID: ' . $dataTeknis->id);
           } catch (\Exception $e) {
               Log::error('Failed to execute mikrotik:create-secret for DataTeknis ID: ' . $dataTeknis->id, [
                   'error' => $e->getMessage(),
                   'trace' => $e->getTraceAsString()
               ]);
           }
       }
   }