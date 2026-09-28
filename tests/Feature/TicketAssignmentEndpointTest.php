<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `GET /tickets/next-assignee` — pré-visualização da sugestão de responsável.
 *
 * O endpoint é read-only por decisão de arquitetura: ele justifica a escolha
 * para a tela, mas a gravação continua sendo feita pelo POST do formulário.
 */
class TicketAssignmentEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_retorna_o_responsavel_sugerido_com_a_justificativa_de_carga(): void
    {
        $ocupado = User::factory()->create([
            'name' => 'Carlos Oliveira',
            'email' => 'carlos.oliveira@example.com',
        ]);
        $disponivel = User::factory()->create([
            'name' => 'Maria Souza',
            'email' => 'maria.souza@example.com',
        ]);

        Ticket::factory()->create([
            'assigned_to' => $ocupado->id,
            'status' => TicketStatus::OPEN->value,
            'priority' => TicketPriority::HIGH->value,
        ]);
        Ticket::factory()->create([
            'assigned_to' => $ocupado->id,
            'status' => TicketStatus::IN_PROGRESS->value,
            'priority' => TicketPriority::LOW->value,
        ]);

        $response = $this->get('/tickets/next-assignee');

        $response->assertOk();
        $response->assertJson([
            'id' => $disponivel->id,
            'name' => 'Maria Souza',
            'email' => 'maria.souza@example.com',
            'reason' => [
                'open' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ],
        ]);
    }

    public function test_retorna_422_quando_nao_ha_responsaveis_cadastrados(): void
    {
        $response = $this->get('/tickets/next-assignee');

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Cadastre um responsável antes de usar atribuição automática.',
            'error' => 'Nenhum responsável disponível para atribuição automática.',
        ]);
    }

    public function test_nao_altera_nenhum_dado(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'assigned_to' => $user->id,
            'status' => TicketStatus::OPEN->value,
        ]);

        $ticketsAntes = Ticket::get(['id', 'assigned_to', 'status'])->toArray();
        $usersAntes = User::get(['id'])->toArray();

        $this->get('/tickets/next-assignee')->assertOk();

        $this->assertSame($ticketsAntes, Ticket::get(['id', 'assigned_to', 'status'])->toArray());
        $this->assertSame($usersAntes, User::get(['id'])->toArray());
        $this->assertSame($user->id, $ticket->fresh()->assigned_to);
    }
}
