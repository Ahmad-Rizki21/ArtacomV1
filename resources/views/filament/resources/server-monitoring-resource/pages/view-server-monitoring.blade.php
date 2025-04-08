<x-filament-panels::page>
   
@php
    function formatUptime($uptime) {
        if (empty($uptime)) return 'N/A';
        
        $translations = [
            'w' => 'minggu',
            'd' => 'hari',
            'h' => 'jam',
            'm' => 'menit',
            's' => 'detik'
        ];
        
        $parts = [];
        preg_match_all('/(\d+)([wdhms])/', $uptime, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $value = $match[1];
            $unit = $match[2];
            $parts[] = "{$value} " . $translations[$unit];
        }
        
        return implode(', ', $parts) ?: 'N/A';
    }
    @endphp

    <!-- Debug Panel -->
    <div class="mb-4 p-4 bg-blue-50 text-blue-700 rounded-md">
        <h3 class="font-bold">Debug Info</h3>
        <ul>
            <li>Server ID: {{ $record->id }}</li>
            <li>Server Name: {{ $record->name }}</li>
            <li>Metrics Available: {{ isset($latestMetric) ? 'Yes' : 'No' }}</li>
            @if(isset($latestMetric))
                <li>CPU Load: {{ $latestMetric->cpu_load }}</li>
                <li>Memory: {{ $latestMetric->memory_usage }}</li>
            @endif
        </ul>
    </div>

    <!-- System Alerts -->
    <div class="mb-6">
        @php
            $alerts = [];
            
            // CPU Alert
            if (isset($latestMetric->cpu_load) && $latestMetric->cpu_load > 80) {
                $alerts[] = [
                    'type' => 'error',
                    'title' => 'CPU Usage High',
                    'message' => "CPU load currently at {$latestMetric->cpu_load}%"
                ];
            } elseif (isset($latestMetric->cpu_load) && $latestMetric->cpu_load > 60) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'CPU Usage Elevated',
                    'message' => "CPU load currently at {$latestMetric->cpu_load}%"
                ];
            }
            
            // Memory Alert
            if (isset($latestMetric->memory_usage) && (float)$latestMetric->memory_usage > 80) {
                $alerts[] = [
                    'type' => 'error',
                    'title' => 'Memory Usage High',
                    'message' => "Memory usage currently at {$latestMetric->memory_usage}"
                ];
            } elseif (isset($latestMetric->memory_usage) && (float)$latestMetric->memory_usage > 60) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Memory Usage Elevated',
                    'message' => "Memory usage currently at {$latestMetric->memory_usage}"
                ];
            }
            
            // Disk Alert
            if (isset($latestMetric->disk_usage) && (float)$latestMetric->disk_usage > 80) {
                $alerts[] = [
                    'type' => 'error',
                    'title' => 'Disk Usage High',
                    'message' => "Disk usage currently at {$latestMetric->disk_usage}"
                ];
            } elseif (isset($latestMetric->disk_usage) && (float)$latestMetric->disk_usage > 60) {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => 'Disk Usage Elevated',
                    'message' => "Disk usage currently at {$latestMetric->disk_usage}"
                ];
            }
        @endphp
        
        @foreach($alerts as $alert)
            <div class="p-4 mb-2 rounded-lg {{ $alert['type'] == 'error' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if($alert['type'] == 'error')
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">
                            <span class="font-bold">{{ $alert['title'] }}:</span> {{ $alert['message'] }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
        
        @if(empty($alerts))
            <div class="p-4 mb-2 rounded-lg bg-green-100 text-green-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">Semua sistem berjalan normal</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Main Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <!-- CPU Load Card -->
        <div class="p-6 bg-white rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-700">CPU Load</h3>
            <div class="mt-2 flex items-center">
                <span class="text-3xl font-bold {{ isset($latestMetric->cpu_load) && $latestMetric->cpu_load > 80 ? 'text-red-500' : (isset($latestMetric->cpu_load) && $latestMetric->cpu_load > 60 ? 'text-yellow-500' : 'text-primary-500') }}">
                    {{ $latestMetric->cpu_load ?? 'N/A' }}
                </span>
                <span class="ml-1 text-gray-500">%</span>
            </div>
            @if(isset($latestMetric->cpu_load))
            <div class="mt-3 w-full bg-gray-200 rounded-full h-2.5">
                <div class="h-2.5 rounded-full {{ $latestMetric->cpu_load > 80 ? 'bg-red-500' : ($latestMetric->cpu_load > 60 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min($latestMetric->cpu_load, 100) }}%"></div>
            </div>
            @endif
        </div>

        <!-- Memory Usage Card -->
        <div class="p-6 bg-white rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-700">Memory Usage</h3>
            <div class="mt-2 flex items-center">
                <span class="text-3xl font-bold {{ isset($latestMetric->memory_usage) && (float)$latestMetric->memory_usage > 80 ? 'text-red-500' : (isset($latestMetric->memory_usage) && (float)$latestMetric->memory_usage > 60 ? 'text-yellow-500' : 'text-primary-500') }}">
                    {{ $latestMetric->memory_usage ?? 'N/A' }}
                </span>
            </div>
            @if(isset($latestMetric->memory_usage))
            <div class="mt-3 w-full bg-gray-200 rounded-full h-2.5">
                <div class="h-2.5 rounded-full {{ (float)$latestMetric->memory_usage > 80 ? 'bg-red-500' : ((float)$latestMetric->memory_usage > 60 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min((float)$latestMetric->memory_usage, 100) }}%"></div>
            </div>
            @endif
        </div>

        <!-- Uptime Card -->
        <div class="p-6 bg-white rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-700">Uptime</h3>
            <div class="mt-2">
                <span class="text-xl font-medium text-gray-800">
                    {{ formatUptime($latestMetric->uptime ?? '') }}
                </span>
            </div>
            @if(isset($latestMetric->uptime))
            <div class="mt-2 text-sm text-gray-500">
                Format asli: {{ $latestMetric->uptime }}
            </div>
            @endif
        </div>
    </div>

    <!-- Server Information -->
    <div class="mb-8 p-6 bg-white rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-700 mb-4">Server Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="mb-2"><span class="font-medium">Name:</span> {{ $record->name }}</p>
                <p class="mb-2"><span class="font-medium">IP Address:</span> {{ $record->host_ip }}</p>
                <p class="mb-2"><span class="font-medium">RouterOS Version:</span> {{ $record->ros_version ?? 'Unknown' }}</p>
            </div>
            <div>
                <p class="mb-2">
                    <span class="font-medium">Status:</span> 
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $record->last_connection_status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $record->last_connection_status === 'success' ? 'Online' : 'Offline' }}
                    </span>
                </p>
                <p class="mb-2"><span class="font-medium">Last Check:</span> {{ $record->last_connected_at ? (is_string($record->last_connected_at) ? $record->last_connected_at : $record->last_connected_at->diffForHumans()) : 'Never' }}</p>
                <p class="mb-2"><span class="font-medium">Port:</span> {{ $record->port ?? '8728' }}</p>
            </div>
        </div>
    </div>
    
    @php
        $interfaces = isset($latestMetric) && $latestMetric->interfaces_traffic 
            ? json_decode($latestMetric->interfaces_traffic, true) 
            : null;
        
        $connections = isset($latestMetric) && $latestMetric->active_connections 
            ? json_decode($latestMetric->active_connections, true) 
            : null;
    @endphp
    
    <!-- Interfaces Panel -->
    @if(isset($interfaces) && count($interfaces) > 0)
    <div class="mb-8 overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <h3 class="p-4 bg-gray-50 text-lg font-medium text-gray-700">Interfaces ({{ count($interfaces) }})</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MAC Address</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Traffic (RX/TX)</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($interfaces as $interface)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $interface['name'] ?? 'N/A' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ $interface['type'] ?? 'N/A' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ $interface['mac-address'] ?? 'N/A' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">
                            @if(isset($interface['rx-byte']) && isset($interface['tx-byte']))
                                <span class="text-blue-600">↓ {{ number_format($interface['rx-byte'] / 1024 / 1024, 2) }} MB</span> / 
                                <span class="text-green-600">↑ {{ number_format($interface['tx-byte'] / 1024 / 1024, 2) }} MB</span>
                            @else
                                N/A
                            @endif
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">
                            @if(isset($interface['running']))
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $interface['running'] === 'true' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $interface['running'] === 'true' ? 'Running' : 'Stopped' }}
                                </span>
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="mb-8 p-6 bg-white rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-700">Network Interfaces</h3>
        <p class="mt-2 text-gray-500">No interface data available.</p>
    </div>
    @endif
    
    <!-- Connections Panel -->
    @if(isset($connections) && count($connections) > 0)
    <div class="mb-8 overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <h3 class="p-4 bg-gray-50 text-lg font-medium text-gray-700">Active Connections</h3>
        
        <div class="p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            @php
                $totalConnections = count($connections);
                $protocolCounts = [];
                
                foreach ($connections as $conn) {
                    $protocol = $conn['protocol'] ?? 'unknown';
                    if (!isset($protocolCounts[$protocol])) {
                        $protocolCounts[$protocol] = 0;
                    }
                    $protocolCounts[$protocol]++;
                }
            @endphp
            
            <div class="p-4 bg-gray-50 rounded-lg text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $totalConnections }}</div>
                <div class="text-gray-500">Total Connections</div>
            </div>
            
            @foreach($protocolCounts as $protocol => $count)
                <div class="p-4 bg-gray-50 rounded-lg text-center">
                    <div class="text-2xl font-bold 
                        {{ $protocol == 'tcp' ? 'text-green-600' : ($protocol == 'udp' ? 'text-orange-600' : 'text-purple-600') }}">
                        {{ $count }}
                    </div>
                    <div class="text-gray-500">{{ strtoupper($protocol) }}</div>
                </div>
            @endforeach
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocol</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">State</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Packets/Bytes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach(array_slice($connections, 0, 10) as $connection)
                    <tr>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $connection['protocol'] == 'tcp' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800' }}">
                                {{ strtoupper($connection['protocol'] ?? 'N/A') }}
                            </span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ $connection['src-address'] ?? 'N/A' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ $connection['dst-address'] ?? 'N/A' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">{{ $connection['tcp-state'] ?? 'N/A' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">
                            {{ $connection['orig-packets'] ?? '0' }} pkts / 
                            {{ isset($connection['orig-bytes']) ? number_format($connection['orig-bytes']) : '0' }} B
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        @if($totalConnections > 10)
            <div class="mt-2 p-2 text-right text-sm text-gray-500">
                Showing 10 of {{ $totalConnections }} connections
            </div>
        @endif
    </div>
    @endif

    <!-- Grafik CPU dan Memory -->
    @php
        // Ambil 10 entri metrik terakhir
        $historicalMetrics = \App\Models\ServerMetric::where('mikrotik_server_id', $record->id)
            ->latest()
            ->take(10)
            ->get()
            ->reverse();
            
        $cpuData = [];
        $memoryData = [];
        $labels = [];
        
        foreach ($historicalMetrics as $metric) {
            $cpuData[] = (int)$metric->cpu_load;
            $memoryData[] = (float)$metric->memory_usage;
            $labels[] = $metric->created_at->format('H:i');
        }
    @endphp
    
    @if(count($historicalMetrics) > 1)
    <div class="p-6 bg-white rounded-lg shadow mb-6">
        <h3 class="text-lg font-bold mb-4">Resource Usage History</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- CPU Chart -->
            <div class="p-4 bg-gray-50 rounded-lg">
                <h4 class="font-medium mb-2">CPU Usage History</h4>
                <canvas id="cpuChart" width="400" height="200"></canvas>
            </div>
            
            <!-- Memory Chart -->
            <div class="p-4 bg-gray-50 rounded-lg">
                <h4 class="font-medium mb-2">Memory Usage History</h4>
                <canvas id="memoryChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // CPU Chart
                const cpuCtx = document.getElementById('cpuChart').getContext('2d');
                const cpuChart = new Chart(cpuCtx, {
                    type: 'line',
                    data: {
                        labels: @json($labels),
                        datasets: [{
                            label: 'CPU Load (%)',
                            data: @json($cpuData),
                            backgroundColor: 'rgba(59, 130, 246, 0.2)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 2,
                            tension: 0.2
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        }
                    }
                });
                
                // Memory Chart
                const memCtx = document.getElementById('memoryChart').getContext('2d');
                const memChart = new Chart(memCtx, {
                    type: 'line',
                    data: {
                        labels: @json($labels),
                        datasets: [{
                            label: 'Memory Usage (%)',
                            data: @json($memoryData),
                            backgroundColor: 'rgba(16, 185, 129, 0.2)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 2,
                            tension: 0.2
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        }
                    }
                });
            });
        </script>
    </div>
    @else
    <div class="p-6 bg-white rounded-lg shadow mb-6">
        <h3 class="text-lg font-bold mb-4">Resource Usage History</h3>
        <p class="text-gray-500">Need at least 2 metrics data points to display charts. Please refresh data a few times.</p>
    </div>
    @endif

    <div class="mb-6 text-center text-xs text-gray-500">
        Last updated: {{ now()->format('Y-m-d H:i:s') }} · 
        <a href="#" class="text-primary-500 hover:text-primary-600" onclick="document.getElementById('refreshButton').click(); return false;">
            Refresh data
        </a>
    </div>
</x-filament-panels::page>