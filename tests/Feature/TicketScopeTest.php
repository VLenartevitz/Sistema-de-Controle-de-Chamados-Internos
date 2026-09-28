<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Scopes de filtro do Ticket, exercitados direto na query.
 *
 * O `index` sempre encadeia os seis scopes, mas com parâmetros vazios: a
 * listagem só prova que o *early return* funciona. Aqui cada corpo de `where`
 * é de fato executado.
 */
class TicketScopeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, Ticket>  $tickets
     * @return array<int, int>
     */
    private function idsDe(array $tickets): array
    {
        return array_map(fn (Ticket $ticket) => $ticket->id, $tickets);
    }

    public function test_scope_open_seleciona_abertos_e_em_andamento(): void
    {
        Ticket::factory()->create(['status' => TicketStatus::RESOLVED->value]);
        Ticket::factory()->create(['status' => TicketStatus::CLOSED->value]);

        $emAberto = Ticket::factory()->create(['status' => TicketStatus::OPEN->value]);
        $emAndamento = Ticket::factory()->create(['status' => TicketStatus::IN_PROGRESS->value]);

        $this->assertEqualsCanonicalizing(
            $this->idsDe([$emAberto, $emAndamento]),
            Ticket::open()->pluck('id')->all()
        );
    }

    public function test_scope_by_priority_ignora_valor_vazio(): void
    {
        $ticket = Ticket::factory()->create(['priority' => TicketPriority::HIGH->value]);

        $this->assertSame($this->idsDe([$ticket]), Ticket::byPriority(null)->pluck('id')->all());
        $this->assertSame($this->idsDe([$ticket]), Ticket::byPriority('')->pluck('id')->all());
    }

    public function test_scope_by_priority_filtra_quando_informado(): void
    {
        $alta = Ticket::factory()->create(['priority' => TicketPriority::HIGH->value]);
        Ticket::factory()->create(['priority' => TicketPriority::LOW->value]);

        $this->assertSame(
            $this->idsDe([$alta]),
            Ticket::byPriority(TicketPriority::HIGH->value)->pluck('id')->all()
        );
    }

    public function test_scope_by_status_ignora_valor_vazio(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::OPEN->value]);

        $this->assertSame($this->idsDe([$ticket]), Ticket::byStatus(null)->pluck('id')->all());
        $this->assertSame($this->idsDe([$ticket]), Ticket::byStatus('')->pluck('id')->all());
    }

    public function test_scope_by_status_filtra_quando_informado(): void
    {
        $fechado = Ticket::factory()->create(['status' => TicketStatus::CLOSED->value]);
        Ticket::factory()->create(['status' => TicketStatus::OPEN->value]);

        $this->assertSame(
            $this->idsDe([$fechado]),
            Ticket::byStatus(TicketStatus::CLOSED->value)->pluck('id')->all()
        );
    }

    public function test_scope_by_assignee_ignora_valor_vazio(): void
    {
        $ticket = Ticket::factory()->create();

        $this->assertSame($this->idsDe([$ticket]), Ticket::byAssignee(null)->pluck('id')->all());
    }

    public function test_scope_by_assignee_trata_zero_como_filtro_e_nao_como_vazio(): void
    {
        Ticket::factory()->create();

        // `blank(0)` é falso: zero é um id procurado, não um filtro ausente.
        $this->assertSame([], Ticket::byAssignee(0)->pluck('id')->all());
    }

    public function test_scope_by_assignee_filtra_quando_informado(): void
    {
        $user = User::factory()->create();
        $dele = Ticket::factory()->create(['assigned_to' => $user->id]);
        Ticket::factory()->create();

        $this->assertSame(
            $this->idsDe([$dele]),
            Ticket::byAssignee($user->id)->pluck('id')->all()
        );
    }

    public function test_scope_by_opened_at_range_aceita_apenas_um_dos_limites(): void
    {
        $antigo = Ticket::factory()->create(['opened_at' => '2026-01-05 09:00:00']);
        $recente = Ticket::factory()->create(['opened_at' => '2026-03-20 09:00:00']);

        $this->assertSame(
            $this->idsDe([$recente]),
            Ticket::byOpenedAtRange('2026-02-01', null)->pluck('id')->all()
        );
        $this->assertSame(
            $this->idsDe([$antigo]),
            Ticket::byOpenedAtRange(null, '2026-02-01')->pluck('id')->all()
        );
        $this->assertCount(2, Ticket::byOpenedAtRange(null, null)->get());
    }

    public function test_scope_search_cobre_titulo_e_descricao(): void
    {
        $noTitulo = Ticket::factory()->create([
            'title' => 'Impressora sem toner',
            'description' => 'O cartucho vazou no fundo da mesa.',
        ]);
        $naDescricao = Ticket::factory()->create([
            'title' => 'Rede instável',
            'description' => 'O roteador reinicia sozinho',
        ]);
        Ticket::factory()->create([
            'title' => 'Chamado sem relação',
            'description' => 'Nada a ver aqui.',
        ]);

        $this->assertSame(
            $this->idsDe([$noTitulo]),
            Ticket::search('toner')->pluck('id')->all()
        );
        $this->assertSame(
            $this->idsDe([$naDescricao]),
            Ticket::search('roteador')->pluck('id')->all()
        );
    }

    public function test_scope_search_ignora_valor_vazio(): void
    {
        $um = Ticket::factory()->create();
        $dois = Ticket::factory()->create();

        $this->assertEqualsCanonicalizing(
            $this->idsDe([$um, $dois]),
            Ticket::search(null)->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            $this->idsDe([$um, $dois]),
            Ticket::search('   ')->pluck('id')->all()
        );
    }
}
