<?php

namespace Tests\Feature;

use App\Models\Ticket;
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

    public function test_cria_chamados_de_exemplo_para_a_listagem_nao_nascer_vazia(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(14, Ticket::count());

        foreach (Ticket::all() as $ticket) {
            $this->assertNotNull($ticket->assigned_to, 'Todo chamado de exemplo tem responsável.');
            $this->assertInstanceOf(Carbon::class, $ticket->opened_at);
            $this->assertGreaterThanOrEqual(
                Ticket::MIN_DESCRIPTION_LENGTH,
                mb_strlen($ticket->description),
                'As descrições de exemplo respeitam a validação do FormRequest.'
            );
        }
    }

    public function test_a_carga_dos_chamados_de_exemplo_permite_avaliar_a_distribuicao(): void
    {
        $this->seed(DatabaseSeeder::class);

        // A distribuição automática só é demonstrável com carga desigual: o
        // requisito 4.1 do desafio pede atribuir ao de menos abertos.
        $abertosPorResponsavel = User::withCount([
            'tickets as abertos' => fn ($q) => $q->open(),
        ])->get()->pluck('abertos', 'name')->all();

        $this->assertCount(3, $abertosPorResponsavel);
        $this->assertGreaterThan(
            min($abertosPorResponsavel),
            max($abertosPorResponsavel),
            'A carga precisa ser desigual para a distribuição automática ter efeito.'
        );
    }
}
