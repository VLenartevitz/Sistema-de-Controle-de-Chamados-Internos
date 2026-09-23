<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao.silva@example.com',
        ]);

        User::factory()->create([
            'name' => 'Maria Souza',
            'email' => 'maria.souza@example.com',
        ]);

        User::factory()->create([
            'name' => 'Carlos Oliveira',
            'email' => 'carlos.oliveira@example.com',
        ]);
    }
}
