<!-- resources/views/admin/status.blade.php -->
<x-filament::page>
    <x-filament::card>
        <h2 class="text-xl font-bold mb-4">Status Pelanggan</h2>
        
        <div class="space-y-4">
            <div>
                <p class="font-medium">Nama Pelanggan:</p>
                <p>{{ $pelanggan->nama }}</p>
            </div>
            
            <div>
                <p class="font-medium">Status:</p>
                @if($isSuspended)
                    <p class="text-red-500 font-bold">TERSUSPEND</p>
                    <p class="mt-2 text-red-500">
                        User ini sedang tersuspend, akses ke modem tidak tersedia.
                    </p>
                @else
                    <p class="text-green-500 font-bold">{{ ucfirst($status) }}</p>
                @endif
            </div>
            
            <div class="mt-6">
                <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                    Kembali
                </a>
            </div>
        </div>
    </x-filament::card>
</x-filament::page>