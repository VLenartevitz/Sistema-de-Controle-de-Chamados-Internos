<?php

namespace Tests\Unit;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Enums são o contrato de domínio compartilhado entre validação, filtro e tela:
 * os rótulos em PT-BR aparecem na listagem e as *values* alimentam o
 * `Rule::in()` dos FormRequests. Teste puro, sem boot de framework.
 */
class TicketEnumTest extends TestCase
{
    public static function labelsDePrioridade(): array
    {
        return [
            'low' => [TicketPriority::LOW, 'Baixa'],
            'medium' => [TicketPriority::MEDIUM, 'Média'],
            'high' => [TicketPriority::HIGH, 'Alta'],
        ];
    }

    #[DataProvider('labelsDePrioridade')]
    public function test_prioridade_expoe_rotulo_em_portugues(TicketPriority $case, string $label): void
    {
        $this->assertSame($label, $case->label());
    }

    public static function labelsDeStatus(): array
    {
        return [
            'open' => [TicketStatus::OPEN, 'Aberto'],
            'in_progress' => [TicketStatus::IN_PROGRESS, 'Em andamento'],
            'resolved' => [TicketStatus::RESOLVED, 'Resolvido'],
            'closed' => [TicketStatus::CLOSED, 'Fechado'],
        ];
    }

    #[DataProvider('labelsDeStatus')]
    public function test_status_expoe_rotulo_em_portugues(TicketStatus $case, string $label): void
    {
        $this->assertSame($label, $case->label());
    }

    public function test_values_derivam_dos_casos_do_enum(): void
    {
        $this->assertSame(['low', 'medium', 'high'], TicketPriority::values());
        $this->assertSame(['open', 'in_progress', 'resolved', 'closed'], TicketStatus::values());
    }

    public function test_open_statuses_considera_apenas_trabalho_nao_concluido(): void
    {
        // A mesma definição que `Ticket::scopeOpen()` usa para contar carga.
        $this->assertSame(
            [TicketStatus::OPEN->value, TicketStatus::IN_PROGRESS->value],
            TicketStatus::openStatuses()
        );
        $this->assertNotContains(TicketStatus::RESOLVED->value, TicketStatus::openStatuses());
        $this->assertNotContains(TicketStatus::CLOSED->value, TicketStatus::openStatuses());
    }

    public function test_pode_ser_construido_a_partir_do_valor_do_banco(): void
    {
        $this->assertSame(TicketPriority::HIGH, TicketPriority::from('high'));
        $this->assertSame(TicketStatus::IN_PROGRESS, TicketStatus::from('in_progress'));
    }
}
