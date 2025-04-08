<?php

namespace Database\Seeders;

use App\Models\MikrotikServer;
use Illuminate\Database\Seeder;

class MikrotikServerSeeder extends Seeder
{
    public function run()
    {
        $servers = [
            [
                'name' => 'Nagrak',
                'host_ip' => '12.1.1.5',
                'username' => 'monarta', // Sesuaikan dengan username yang benar
                'password' => 'Spv@4rta.!!', // Sesuaikan dengan password yang benar
                'port' => 8728, // Port default RouterOS
                'api_port' => '9729',
                'is_active' => true,
            ],
            [
                'name' => 'Parama',
                'host_ip' => '12.1.1.7',
                'username' => 'monarta', // Sesuaikan dengan username yang benar
                'password' => 'Spv@4rta.!!', // Sesuaikan dengan password yang benar
                'port' => 8728,
                'api_port' => '9729',
                'is_active' => true,
            ],
            [
                'name' => 'Pinus',
                'host_ip' => '12.1.1.4',
                'username' => 'artacom',
                'password' => 'password',
                'port' => 8728,
                'api_port' => '9729',
                'is_active' => true,
            ],
            [
                'name' => 'Pulogebang',
                'host_ip' => '12.1.1.2',
                'username' => 'admin',
                'password' => 'password',
                'port' => 8728,
                'api_port' => '9729',
                'is_active' => true,
            ],
            [
                'name' => 'Tambun',
                'host_ip' => '12.1.1.6',
                'username' => 'admin',
                'password' => 'password',
                'port' => 8728,
                'api_port' => '9729',
                'is_active' => true,
            ],
            [
                'name' => 'Tipar',
                'host_ip' => '12.1.1.3',
                'username' => 'admin',
                'password' => 'password',
                'port' => 8728,
                'api_port' => '9729',
                'is_active' => true,
            ],
            [
                'name' => 'Waringin',
                'host_ip' => '12.1.1.8',
                'username' => 'admin',
                'password' => 'password',
                'port' => 8728,
                'api_port' => '9729',
                'is_active' => true,
            ],
        ];

        foreach ($servers as $server) {
            MikrotikServer::create($server);
        }
    }
}
