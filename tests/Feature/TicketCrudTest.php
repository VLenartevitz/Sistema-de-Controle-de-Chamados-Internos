<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_tickets(): void
    {
        User::factory()->create();
        Ticket::factory()->count(3)->create();

        $response = $this->get('/tickets');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tickets/Index')->has('tickets'));
    }

    public function test_can_create_ticket_form(): void
    {
        User::factory()->count(3)->create();

        $response = $this->get('/tickets/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tickets/Create')->has('users')->has('priorities')->has('statuses'));
    }

    public function test_can_store_ticket_with_valid_data(): void
    {
        $user = User::factory()->create();

        $data = [
            'title' => 'Impressora não funciona',
            'description' => 'A impressora do segundo andar está com erro de papel atolado há 2 dias.',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => $user->id,
            'opened_at' => now()->toDateTimeString(),
        ];

        $response = $this->post('/tickets', $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'title' => 'Impressora não funciona',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => $user->id,
        ]);
    }

    public function test_store_defaults_opened_at_when_not_provided(): void
    {
        $user = User::factory()->create();

        $data = [
            'title' => 'Computador travou',
            'description' => 'Meu computador travou e não liga mais, preciso de ajuda urgente.',
            'priority' => 'medium',
            'status' => 'open',
            'assigned_to' => $user->id,
        ];

        $this->post('/tickets', $data);

        $this->assertDatabaseHas('tickets', ['title' => 'Computador travou']);
        $ticket = Ticket::where('title', 'Computador travou')->first();
        $this->assertNotNull($ticket->opened_at);
    }

    public function test_can_show_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->get("/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tickets/Show')->has('ticket'));
    }

    public function test_can_edit_ticket_form(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->get("/tickets/{$ticket->id}/edit");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tickets/Edit')->has('ticket'));
    }

    public function test_can_update_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $newUser = User::factory()->create();

        $data = [
            'title' => 'Título atualizado',
            'description' => 'Descrição atualizada com mais de dez caracteres.',
            'priority' => 'low',
            'status' => 'in_progress',
            'assigned_to' => $newUser->id,
            'opened_at' => $ticket->opened_at->toDateTimeString(),
        ];

        $response = $this->put("/tickets/{$ticket->id}", $data);

        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'title' => 'Título atualizado',
            'priority' => 'low',
            'status' => 'in_progress',
            'assigned_to' => $newUser->id,
        ]);
    }

    public function test_root_redirects_to_tickets(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('tickets.index'));
    }
}
