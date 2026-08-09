<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CoreSeeder extends Seeder
{
    public function run()
    {
        $this->call('App\Database\Seeds\TenantSeeder');
        $this->call('App\Database\Seeds\RolePermissionSeeder');
        $this->call('App\Database\Seeds\UserSeeder');
        $this->call('App\Database\Seeds\SettingSeeder');
        $this->call('App\Database\Seeds\CourtSeeder');
    }
}
