// resources/views/filament/resources/isolir/detail.blade.php
<div class="p-4">
    <h3 class="text-lg font-semibold mb-4">Detail Isolir</h3>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <strong>Nama Pelanggan:</strong> {{ $record->pelanggan->nama }}
        </div>
        <div>
            <strong>Brand:</strong> {{ $record->brand }}
        </div>
        <!-- Tambahkan field lainnya -->
    </div>
</div>