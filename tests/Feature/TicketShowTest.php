<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tela de detalhe do chamado. O controller monta o array manualmente, então o
 * teste fixa o contrato (rótulos em PT-BR e formato de data) que o Vue consome.
 */
class TicketShowTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(array $attributes = []): Ticket
    {
        return Ticket::factory()->create($attributes);
    }

    public function test_exibe_o_chamado_com_rotulos_e_datas_formatadas(): void
    {
        $user = User::factory()->create([
            'id' => 7,
            'name' => 'Maria Souza',
            'email' => 'maria.souza@example.com',
        ]);

        $ticket = $this->ticket([
            'title' => 'Impressora sem toner',
            'description' => 'A impressora do segundo andar está sem toner.',
            'priority' => TicketPriority::HIGH->value,
            'status' => TicketStatus::IN_PROGRESS->value,
            'assigned_to' => $user->id,
            'opened_at' => '2026-03-04 08:30:00',
            'created_at' => '2026-03-05 09:15:00',
            'updated_at' => '2026-03-06 10:00:00',
        ]);

        $response = $this->get("/tickets/{$ticket->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Tickets/Show')
            ->where('ticket.id', $ticket->id)
            ->where('ticket.title', 'Impressora sem toner')
            ->where('ticket.description', 'A impressora do segundo andar está sem toner.')
            ->where('ticket.priority', 'high')
            ->where('ticket.priority_label', 'Alta')
            ->where('ticket.status', 'in_progress')
            ->where('ticket.status_label', 'Em andamento')
            ->where('ticket.assigned_to', $user->id)
            ->where('ticket.assigned_user.id', $user->id)
            ->where('ticket.assigned_user.name', 'Maria Souza')
            ->where('ticket.assigned_user.email', 'maria.souza@example.com')
            ->where('ticket.opened_at', '04/03/2026 08:30')
            ->where('ticket.created_at', '05/03/2026 09:15')
            ->where('ticket.updated_at', '06/03/2026 10:00')
        );
    }

    public function test_chamado_sem_responsavel_cai_no_padrao_sem_responsavel(): void
    {
        // assigned_to é nullable no banco; o model usa withDefault para que a
        // tela nunca receba null.
        $ticket = $this->ticket([
            'assigned_to' => null,
            'priority' => TicketPriority::LOW->value,
            'status' => TicketStatus::CLOSED->value,
        ]);

        $response = $this->get("/tickets/{$ticket->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Tickets/Show')
            ->where('ticket.assigned_to', null)
            ->where('ticket.priority_label', 'Baixa')
            ->where('ticket.status_label', 'Fechado')
        );

        $this->assertSame('Sem responsável', $ticket->fresh()->assignedUser->name);
        $this->assertSame('', $ticket->fresh()->assignedUser->email);
    }

    public function test_remove_o_responsavel_sem_perder_o_historico_do_chamado(): void
    {
        $user = User::factory()->create();
        $ticket = $this->ticket(['assigned_to' => $user->id]);

        $user->delete();

        $this->assertNull($ticket->fresh()->assigned_to);
        $this->assertSame('Sem responsável', $ticket->fresh()->assignedUser->name);
    }

    public function test_devolve_404_para_chamado_inexistente(): void
    {
        $this->get('/tickets/999999')->assertNotFound();
    }

    public function test_tela_de_edicao_carrega_o_chamado_e_as_opcoes(): void
    {
        $user = User::factory()->create();
        $ticket = $this->ticket([
            'assigned_to' => $user->id,
            'priority' => TicketPriority::MEDIUM->value,
            'status' => TicketStatus::RESOLVED->value,
            'opened_at' => '2026-03-04 08:30:00',
        ]);

        $response = $this->get("/tickets/{$ticket->id}/edit");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Tickets/Edit')
            ->where('ticket.id', $ticket->id)
            ->where('ticket.priority', 'medium')
            ->where('ticket.status', 'resolved')
            ->where('ticket.assigned_to', $user->id)
            // A edição usa o formato aceito por <input type="datetime-local">.
            ->where('ticket.opened_at', '2026-03-04T08:30')
            ->has('users', 1)
            ->has('priorities', 3)
            ->has('statuses', 4)
        );
    }

    public function test_tela_de_cadastro_oferece_a_data_atual_como_padrao(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 4)->setTime(8, 30));

        $this->get('/tickets/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tickets/Create')
                ->where('default_opened_at', '2026-03-04T08:30')
                ->has('priorities', 3)
                ->has('statuses', 4)
            );
    }
}
