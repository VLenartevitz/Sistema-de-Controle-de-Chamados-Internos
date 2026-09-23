<?php

namespace Tests\Unit;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_priority_and_status_to_enums(): void
    {
        $ticket = Ticket::factory()->create([
            'priority' => TicketPriority::HIGH->value,
            'status' => TicketStatus::OPEN->value,
        ]);

        $this->assertInstanceOf(TicketPriority::class, $ticket->priority);
        $this->assertInstanceOf(TicketStatus::class, $ticket->status);
        $this->assertEquals(TicketPriority::HIGH, $ticket->priority);
        $this->assertEquals(TicketStatus::OPEN, $ticket->status);
    }

    public function test_belongs_to_assigned_user(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['assigned_to' => $user->id]);

        $this->assertTrue($ticket->assignedUser->is($user));
        $this->assertEquals($user->id, $ticket->assignedUser->id);
    }

    public function test_factory_creates_valid_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $this->assertNotEmpty($ticket->title);
        $this->assertNotEmpty($ticket->description);
        $this->assertContains($ticket->priority->value, TicketPriority::values());
        $this->assertContains($ticket->status->value, TicketStatus::values());
        $this->assertNotNull($ticket->opened_at);
    }

    public function test_enums_provide_labels(): void
    {
        $this->assertEquals('Baixa', TicketPriority::LOW->label());
        $this->assertEquals('Alta', TicketPriority::HIGH->label());
        $this->assertEquals('Aberto', TicketStatus::OPEN->label());
        $this->assertEquals('Fechado', TicketStatus::CLOSED->label());
    }
}
