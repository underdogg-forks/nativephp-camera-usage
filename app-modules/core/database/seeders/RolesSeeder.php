<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Enums\UserRole;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            Role::firstOrCreate(
                ['name' => $role->value, 'guard_name' => 'web'],
            );
        }
    }
}
