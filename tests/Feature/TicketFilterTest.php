<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Busca, filtros e normalização de parâmetros da listagem.
 *
 * A listagem ignora silenciosamente filtros desconhecidos em vez de devolver 422,
 * para não interromper a navegação de quem está digitando (ver os testes de
 * valores inválidos).
 */
class TicketFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ids dos chamados presentes na página Inertia, na ordem de renderização.
     *
     * @return array<int, int>
     */
    private function ids(TestResponse $response): array
    {
        return collect($response->viewData('page')['props']['tickets']['data'])
            ->pluck('id')
            ->all();
    }

    private function props(TestResponse $response): array
    {
        return $response->viewData('page')['props'];
    }

    public function test_busca_no_titulo(): void
    {
        $alvo = Ticket::factory()->create([
            'title' => 'Impressora do segundo andar',
            'description' => 'Papel atolado no compartimento traseiro.',
        ]);
        Ticket::factory()->create([
            'title' => 'Rede instável',
            'description' => 'A conexão cai de forma intermitente.',
        ]);

        $this->assertSame([$alvo->id], $this->ids($this->get('/tickets?search=impressora')));
    }

    public function test_busca_na_descricao(): void
    {
        $alvo = Ticket::factory()->create([
            'title' => 'Chamado de rede',
            'description' => 'O cabo de rede foi rompido na obra.',
        ]);
        Ticket::factory()->create([
            'title' => 'Chamado de energia',
            'description' => 'Falta de tensão na tomada do corredor.',
        ]);

        $this->assertSame([$alvo->id], $this->ids($this->get('/tickets?search=cabo')));
    }

    public function test_busca_sem_correspondencia_devolve_pagina_vazia(): void
    {
        Ticket::factory()->create(['title' => 'Impressora quebrada']);

        $response = $this->get('/tickets?search=termopar');

        $this->assertSame([], $this->ids($response));
        $this->assertSame(0, $this->props($response)['tickets']['total']);
    }

    public function test_busca_vazia_nao_filtra_nada(): void
    {
        $um = Ticket::factory()->create(['title' => 'Impressora quebrada']);
        $dois = Ticket::factory()->create(['title' => 'Rede instável']);

        $this->assertEqualsCanonicalizing(
            [$um->id, $dois->id],
            $this->ids($this->get('/tickets?search='))
        );
    }

    public function test_filtra_por_prioridade(): void
    {
        $alta = Ticket::factory()->create(['priority' => TicketPriority::HIGH->value]);
        $baixa = Ticket::factory()->create(['priority' => TicketPriority::LOW->value]);

        $this->assertSame([$alta->id], $this->ids($this->get('/tickets?priority=high')));
        $this->assertSame([$baixa->id], $this->ids($this->get('/tickets?priority=low')));
    }

    public function test_filtra_por_status(): void
    {
        $aberto = Ticket::factory()->create(['status' => TicketStatus::OPEN->value]);
        $fechado = Ticket::factory()->create(['status' => TicketStatus::CLOSED->value]);

        $this->assertSame([$aberto->id], $this->ids($this->get('/tickets?status=open')));
        $this->assertSame([$fechado->id], $this->ids($this->get('/tickets?status=closed')));
    }

    public function test_filtra_por_responsavel(): void
    {
        $joao = User::factory()->create();
        $maria = User::factory()->create();

        $deles = Ticket::factory()->create(['assigned_to' => $joao->id]);
        $dela = Ticket::factory()->create(['assigned_to' => $maria->id]);

        $this->assertSame([$deles->id], $this->ids($this->get("/tickets?assigned_to={$joao->id}")));
        $this->assertSame([$dela->id], $this->ids($this->get("/tickets?assigned_to={$maria->id}")));
    }

    public function test_filtros_desconhecidos_sao_ignorados_em_vez_de_quebrar_a_listagem(): void
    {
        $ticket = Ticket::factory()->create([
            'priority' => TicketPriority::HIGH->value,
            'status' => TicketStatus::OPEN->value,
        ]);

        $response = $this->get('/tickets?priority=urgent&status=arquivado&assigned_to=abc');

        $response->assertOk();
        $this->assertSame([$ticket->id], $this->ids($response));
        $this->assertNull($this->props($response)['filters']['priority']);
        $this->assertNull($this->props($response)['filters']['status']);
        $this->assertNull($this->props($response)['filters']['assigned_to']);
    }

    public function test_filtra_por_data_de_abertura_inicial(): void
    {
        $antigo = Ticket::factory()->create(['opened_at' => '2026-01-05 09:00:00']);
        $recente = Ticket::factory()->create(['opened_at' => '2026-03-20 09:00:00']);

        $this->assertSame([$recente->id], $this->ids($this->get('/tickets?opened_from=2026-02-01')));
        $this->assertEqualsCanonicalizing(
            [$antigo->id, $recente->id],
            $this->ids($this->get('/tickets?opened_from=2025-12-31'))
        );
    }

    public function test_filtra_por_data_de_abertura_final(): void
    {
        $antigo = Ticket::factory()->create(['opened_at' => '2026-01-05 09:00:00']);
        $recente = Ticket::factory()->create(['opened_at' => '2026-03-20 09:00:00']);

        $this->assertSame([$antigo->id], $this->ids($this->get('/tickets?opened_to=2026-02-01')));
        $this->assertEqualsCanonicalizing(
            [$antigo->id, $recente->id],
            $this->ids($this->get('/tickets?opened_to=2026-12-31'))
        );
    }

    public function test_filtra_por_intervalo_de_datas(): void
    {
        $antes = Ticket::factory()->create(['opened_at' => '2026-01-05 09:00:00']);
        $dentro = Ticket::factory()->create(['opened_at' => '2026-02-10 09:00:00']);
        $depois = Ticket::factory()->create(['opened_at' => '2026-03-20 09:00:00']);

        $this->assertSame(
            [$dentro->id],
            $this->ids($this->get('/tickets?opened_from=2026-02-01&opened_to=2026-02-28'))
        );
        $this->assertEqualsCanonicalizing(
            [$antes->id, $dentro->id],
            $this->ids($this->get('/tickets?opened_from=2026-01-01&opened_to=2026-02-28'))
        );
    }

    public function test_combina_busca_com_filtros(): void
    {
        $user = User::factory()->create();

        $combina = Ticket::factory()->create([
            'assigned_to' => $user->id,
            'status' => TicketStatus::OPEN->value,
            'title' => 'Impressora sem toner',
        ]);
        Ticket::factory()->create([
            'assigned_to' => $user->id,
            'status' => TicketStatus::CLOSED->value,
            'title' => 'Impressora com toner',
        ]);
        Ticket::factory()->create([
            'status' => TicketStatus::OPEN->value,
            'title' => 'Impressora sem toner em outro setor',
        ]);

        $this->assertSame(
            [$combina->id],
            $this->ids($this->get("/tickets?search=toner&status=open&assigned_to={$user->id}"))
        );
    }

    public function test_expoe_os_filtros_normalizados_para_a_tela(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create();

        $response = $this->get("/tickets?search=toner&priority=high&status=open&assigned_to={$user->id}&opened_from=2026-01-01&sort=title&direction=asc");

        $this->assertSame([
            'search' => 'toner',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => $user->id,
            'opened_from' => '2026-01-01',
            'opened_to' => null,
            'sort' => 'title',
            'direction' => 'asc',
        ], $this->props($response)['filters']);
    }

    public function test_expoe_a_lista_de_responsaveis_e_de_opcoes_ordenados(): void
    {
        $joao = User::factory()->create(['name' => 'João Silva']);
        $carlos = User::factory()->create(['name' => 'Carlos Oliveira']);

        $props = $this->props($this->get('/tickets'));

        // O select do formulário precisa vir em ordem alfabética.
        $this->assertSame(
            [$carlos->id, $joao->id],
            collect($props['users'])->pluck('id')->all()
        );
        $this->assertSame(
            ['Baixa', 'Média', 'Alta'],
            collect($props['priorities'])->pluck('label')->all()
        );
        $this->assertSame(
            ['Aberto', 'Em andamento', 'Resolvido', 'Fechado'],
            collect($props['statuses'])->pluck('label')->all()
        );
    }

    public function test_descarta_filtros_de_data_fora_do_formato_aceito(): void
    {
        $antigo = Ticket::factory()->create(['opened_at' => '2025-01-01 09:00']);
        $recente = Ticket::factory()->create(['opened_at' => '2026-05-10 09:00']);

        // Data em formato brasileiro, data impossível e lixo: nada disso pode
        // chegar ao whereDate, onde a comparação seria inválida e o resultado
        // da listagem silenciosamente errado.
        foreach (['01/02/2026', '2026-13-45', 'ontem', '2026-1-1'] as $invalida) {
            $props = $this->props($this->get('/tickets?opened_from='.urlencode($invalida)));

            $this->assertNull($props['filters']['opened_from'], "Filtro inválido aceito: {$invalida}");
        }

        // Com o filtro descartado, a listagem volta a trazer tudo.
        $this->assertEqualsCanonicalizing(
            [$antigo->id, $recente->id],
            $this->ids($this->get('/tickets?opened_from=01%2F02%2F2026'))
        );
    }

    public function test_aceita_filtro_de_data_no_formato_do_navegador(): void
    {
        $antigo = Ticket::factory()->create(['opened_at' => '2025-01-01 09:00']);
        $recente = Ticket::factory()->create(['opened_at' => '2026-05-10 09:00']);

        $response = $this->get('/tickets?opened_from=2026-01-01');

        $this->assertSame([$recente->id], $this->ids($response));
        $this->assertSame('2026-01-01', $this->props($response)['filters']['opened_from']);
        $this->assertNotContains($antigo->id, $this->ids($response));
    }
}
