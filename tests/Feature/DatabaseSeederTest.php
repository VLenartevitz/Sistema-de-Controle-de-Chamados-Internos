<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * O README promete três responsáveis via seeder e o requisito 3.4 do desafio
 * exige "ao menos 3 responsáveis". O teste fixa esses dois fatos.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_os_tres_responsaveis_documentados(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(
            ['carlos.oliveira@example.com', 'joao.silva@example.com', 'maria.souza@example.com'],
            User::orderBy('email')->pluck('email')->all()
        );
    }

    public function test_todos_os_responsaveis_tem_senha_e_podem_atribuir_chamados(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::count());
        $this->assertSame(
            ['Carlos Oliveira', 'João Silva', 'Maria Souza'],
            User::orderBy('name')->pluck('name')->all()
        );

        foreach (User::all() as $user) {
            $this->assertNotEmpty($user->password);
            $this->assertInstanceOf(Carbon::class, $user->email_verified_at);
        }
    }
}
