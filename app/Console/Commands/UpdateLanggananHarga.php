<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Langganan;
use Illuminate\Support\Facades\DB;

class UpdateLanggananHarga extends Command
{
    protected $signature = 'langganan:update-harga';
    protected $description = 'Memperbarui format harga langganan ke kelipatan 1000';

    public function handle()
    {
        DB::beginTransaction();
        try {
            $this->info('Memulai pembaruan harga langganan...');
            
            $langganan = Langganan::all();
            $bar = $this->output->createProgressBar(count($langganan));
            $bar->start();
            
            $updated = 0;
            
            foreach ($langganan as $item) {
                $oldPrice = $item->total_harga_layanan_x_pajak;
                $newPrice = ceil($oldPrice / 1000) * 1000;
                
                if ($oldPrice != $newPrice) {
                    $item->total_harga_layanan_x_pajak = $newPrice;
                    $item->save();
                    $updated++;
                }
                
                $bar->advance();
            }
            
            $bar->finish();
            DB::commit();
            
            $this->newLine();
            $this->info("Pembaruan selesai! {$updated} data telah diperbarui.");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Terjadi kesalahan: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}