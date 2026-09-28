<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_title(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/tickets', [
            'title' => '',
            'description' => 'Descrição válida com mais de dez caracteres.',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => $user->id,
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_requires_description(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/tickets', [
            'title' => 'Título válido',
            'description' => '',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => $user->id,
        ]);

        $response->assertSessionHasErrors('description');
    }

    public function test_rejects_short_description(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/tickets', [
            'title' => 'Título válido',
            'description' => 'curta',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => $user->id,
        ]);

        $response->assertSessionHasErrors('description');
    }

    public function test_rejects_invalid_priority(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/tickets', [
            'title' => 'Título válido',
            'description' => 'Descrição válida com mais de dez caracteres.',
            'priority' => 'urgent',
            'status' => 'open',
            'assigned_to' => $user->id,
        ]);

        $response->assertSessionHasErrors('priority');
    }

    public function test_rejects_invalid_status(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/tickets', [
            'title' => 'Título válido',
            'description' => 'Descrição válida com mais de dez caracteres.',
            'priority' => 'high',
            'status' => 'invalid_status',
            'assigned_to' => $user->id,
        ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_requires_assigned_to(): void
    {
        $response = $this->post('/tickets', [
            'title' => 'Título válido',
            'description' => 'Descrição válida com mais de dez caracteres.',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => '',
        ]);

        $response->assertSessionHasErrors('assigned_to');
    }

    public function test_rejects_nonexistent_assignee(): void
    {
        $response = $this->post('/tickets', [
            'title' => 'Título válido',
            'description' => 'Descrição válida com mais de dez caracteres.',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => 9999,
        ]);

        $response->assertSessionHasErrors('assigned_to');
    }

    public function test_rejects_title_over_255(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/tickets', [
            'title' => str_repeat('a', 256),
            'description' => 'Descrição válida com mais de dez caracteres.',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => $user->id,
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function test_update_requires_all_fields(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['assigned_to' => $user->id]);

        $response = $this->put("/tickets/{$ticket->id}", [
            'title' => '',
            'description' => '',
            'priority' => '',
            'status' => '',
            'assigned_to' => '',
            'opened_at' => '',
        ]);

        $response->assertSessionHasErrors(['title', 'description', 'priority', 'status', 'assigned_to', 'opened_at']);
    }

    public function test_update_rejects_short_description(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['assigned_to' => $user->id]);

        $response = $this->put("/tickets/{$ticket->id}", [
            'title' => 'Título válido',
            'description' => 'curta',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => $user->id,
            'opened_at' => '2026-09-01 09:00',
        ]);

        $response->assertSessionHasErrors([
            'description' => 'A descrição deve ter pelo menos 10 caracteres.',
        ]);
    }

    public function test_nao_expoe_rota_de_exclusao_de_chamados(): void
    {
        $ticket = Ticket::factory()->create();

        // A especificação não pede exclusão; sem o `except(['destroy'])` na
        // rota, este DELETE chegava a um método inexistente e devolvia 500.
        $this->delete("/tickets/{$ticket->id}")->assertStatus(405);

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
    }
}
