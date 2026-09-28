<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Ordenação da listagem.
 *
 * Prioridade não é ordenável por `orderBy` comum: 'high', 'low' e 'medium' são
 * strings, e a collation as ordenaria alfabeticamente. O controller usa
 * `orderByRaw` com CASE para restaurar a ordem semântica (high > medium > low).
 */
class TicketSortingTest extends TestCase
{
    use RefreshDatabase;

    /**
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

    private function ticket(string $title, TicketPriority $priority, TicketStatus $status, string $openedAt, string $createdAt): Ticket
    {
        $ticket = Ticket::factory()->create([
            'title' => $title,
            'priority' => $priority->value,
            'status' => $status->value,
            'opened_at' => $openedAt,
        ]);

        // created_at não é fillable, então a coluna que controla a ordenação
        // precisa ser gravada fora do mass assignment.
        $ticket->forceFill(['created_at' => $createdAt])->save();

        return $ticket;
    }

    /**
     * Três chamados escolhidos para que nenhuma ordenação coincida com a ordem
     * de inserção — do contrário o `orderBy('id', 'desc')` do controller
     * mascararia uma ordenação quebrada.
     *
     * @return array<string, Ticket>
     */
    private function seedParaOrdenacao(): array
    {
        return [
            'b' => $this->ticket('Bala', TicketPriority::LOW, TicketStatus::CLOSED, '2026-01-02 10:00:00', '2026-01-02 10:00:00'),
            'a' => $this->ticket('Abacaxi', TicketPriority::HIGH, TicketStatus::OPEN, '2026-03-02 10:00:00', '2026-03-02 10:00:00'),
            'c' => $this->ticket('Caju', TicketPriority::MEDIUM, TicketStatus::IN_PROGRESS, '2026-02-02 10:00:00', '2026-02-02 10:00:00'),
        ];
    }

    /**
     * Cada coluna traz a ordem esperada em formato de chaves do seed, para que a
     * asserção não dependa dos ids gerados.
     *
     * @return array<string, array{0: string, 1: array<int, string>, 2: array<int, string>}>
     */
    public static function colunasOrdenaveis(): array
    {
        return [
            // 'high' < 'in_progress' < 'open' como string: a coluna de status é
            // ordenada lexicalmente, sem CASE. É o comportamento atual.
            'status' => ['status', ['b', 'c', 'a'], ['a', 'c', 'b']],
            'título' => ['title', ['a', 'b', 'c'], ['c', 'b', 'a']],
            'data de abertura' => ['opened_at', ['b', 'c', 'a'], ['a', 'c', 'b']],
            'data de criação' => ['created_at', ['b', 'c', 'a'], ['a', 'c', 'b']],
        ];
    }

    #[DataProvider('colunasOrdenaveis')]
    public function test_ordena_por_cada_coluna_permitida(string $coluna, array $asc, array $desc): void
    {
        $tickets = $this->seedParaOrdenacao();

        $this->assertSame($this->idsDe($tickets, $asc), $this->ids($this->get("/tickets?sort={$coluna}&direction=asc")));
        $this->assertSame($this->idsDe($tickets, $desc), $this->ids($this->get("/tickets?sort={$coluna}&direction=desc")));
    }

    public function test_ordena_por_prioridade_respeitando_a_ordem_semantica(): void
    {
        $tickets = $this->seedParaOrdenacao();

        // high (A) > medium (C) > low (B). Uma ordenação alfabética devolveria
        // B, C, A — high < low < medium — e estas asserções falhariam.
        $this->assertSame(
            $this->idsDe($tickets, ['a', 'c', 'b']),
            $this->ids($this->get('/tickets?sort=priority&direction=desc'))
        );
        $this->assertSame(
            $this->idsDe($tickets, ['b', 'c', 'a']),
            $this->ids($this->get('/tickets?sort=priority&direction=asc'))
        );
    }

    public function test_usa_ordenacao_decrescente_por_criacao_como_padrao(): void
    {
        $tickets = $this->seedParaOrdenacao();

        $response = $this->get('/tickets');

        $this->assertSame($this->idsDe($tickets, ['a', 'c', 'b']), $this->ids($response));
        $this->assertSame('created_at', $this->props($response)['filters']['sort']);
        $this->assertSame('desc', $this->props($response)['filters']['direction']);
    }

    public function test_descarta_ordem_e_sentido_desconhecidos(): void
    {
        $tickets = $this->seedParaOrdenacao();

        $response = $this->get('/tickets?sort=description&direction=sideways');

        // `description` e `sideways` não estão na whitelist: vale o padrão.
        $this->assertSame('created_at', $this->props($response)['filters']['sort']);
        $this->assertSame('desc', $this->props($response)['filters']['direction']);
        $this->assertSame($this->idsDe($tickets, ['a', 'c', 'b']), $this->ids($response));
    }

    public function test_pagina_de_dez_em_dez_preservando_a_consulta_na_url(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->count(25)->create([
            'assigned_to' => $user->id,
            'status' => TicketStatus::OPEN->value,
        ]);

        $props = $this->props($this->get('/tickets?status=open'));

        $this->assertCount(10, $props['tickets']['data']);
        $this->assertSame(25, $props['tickets']['total']);
        $this->assertSame(3, $props['tickets']['last_page']);
        $this->assertStringContainsString('status=open', $props['tickets']['next_page_url']);

        $segunda = $this->props($this->get('/tickets?status=open&page=2'));

        $this->assertCount(10, $segunda['tickets']['data']);
        $this->assertSame(2, $segunda['tickets']['current_page']);
        $this->assertStringContainsString('status=open', $segunda['tickets']['prev_page_url']);
    }

    /**
     * @param  array<string, Ticket>  $tickets
     * @param  array<int, string>  $ordem
     * @return array<int, int>
     */
    private function idsDe(array $tickets, array $ordem): array
    {
        return array_map(fn (string $chave) => $tickets[$chave]->id, $ordem);
    }
}
