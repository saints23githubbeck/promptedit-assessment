<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@promptedit.test'],
            [
                'name' => 'Ada Operator',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'demo@promptedit.test'],
            [
                'name' => 'Dana Demo',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'sam@promptedit.test'],
            [
                'name' => 'Sam Second',
                'password' => Hash::make('password'),
            ]
        );

        $this->command?->info('Seeded 3 demo users successfully.');
    }
}
