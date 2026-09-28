<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Regra de distribuição automática (requisito 4.0 do desafio).
 *
 * Os cenários usam carga explícita, nunca `fake()` aleatório: a regra decide por
 * contagem, e um dado randômico tornaria a falha indepurável.
 */
class TicketAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function resolve(): User
    {
        return (new TicketAssignmentService)->resolve();
    }

    private function charge(User $user, TicketStatus $status, TicketPriority $priority, int $times = 1): void
    {
        Ticket::factory()->count($times)->create([
            'assigned_to' => $user->id,
            'status' => $status->value,
            'priority' => $priority->value,
        ]);
    }

    public function test_atribui_ao_responsavel_com_menos_chamados_em_aberto(): void
    {
        $joao = User::factory()->create(['name' => 'João Silva']);
        $maria = User::factory()->create(['name' => 'Maria Souza']);
        $carlos = User::factory()->create(['name' => 'Carlos Oliveira']);

        $this->charge($joao, TicketStatus::OPEN, TicketPriority::HIGH, 3);
        $this->charge($maria, TicketStatus::OPEN, TicketPriority::HIGH);
        $this->charge($carlos, TicketStatus::OPEN, TicketPriority::HIGH, 2);

        $this->assertTrue($this->resolve()->is($maria));
    }

    public function test_conta_apenas_chamados_abertos_e_em_andamento(): void
    {
        $sobrecarregado = User::factory()->create();
        $disponivel = User::factory()->create();

        $this->charge($sobrecarregado, TicketStatus::OPEN, TicketPriority::LOW);
        $this->charge($sobrecarregado, TicketStatus::IN_PROGRESS, TicketPriority::LOW);

        // Trabalho concluído não pode pesar na escolha de quem recebe o próximo.
        $this->charge($sobrecarregado, TicketStatus::RESOLVED, TicketPriority::HIGH, 5);
        $this->charge($sobrecarregado, TicketStatus::CLOSED, TicketPriority::HIGH, 5);

        $this->assertTrue($this->resolve()->is($disponivel));
    }

    public function test_desempata_por_prioridade_alta(): void
    {
        $sem_alta = User::factory()->create();
        $com_alta = User::factory()->create();

        // Mesmo total (2), mesmo total por prioridade: só o count de `high` separa.
        $this->charge($sem_alta, TicketStatus::OPEN, TicketPriority::MEDIUM, 2);
        $this->charge($com_alta, TicketStatus::OPEN, TicketPriority::HIGH);
        $this->charge($com_alta, TicketStatus::OPEN, TicketPriority::MEDIUM);

        $this->assertTrue($this->resolve()->is($sem_alta));
    }

    public function test_desempata_por_prioridade_media(): void
    {
        $sem_media = User::factory()->create();
        $com_media = User::factory()->create();

        $this->charge($sem_media, TicketStatus::OPEN, TicketPriority::HIGH);
        $this->charge($sem_media, TicketStatus::OPEN, TicketPriority::LOW);
        $this->charge($com_media, TicketStatus::OPEN, TicketPriority::HIGH);
        $this->charge($com_media, TicketStatus::OPEN, TicketPriority::MEDIUM);

        $this->assertTrue($this->resolve()->is($sem_media));
    }

    public function test_desempata_por_prioridade_baixa(): void
    {
        $com_baixa = User::factory()->create();
        $sem_baixa = User::factory()->create();

        $this->charge($com_baixa, TicketStatus::OPEN, TicketPriority::MEDIUM, 2);
        $this->charge($sem_baixa, TicketStatus::OPEN, TicketPriority::MEDIUM);
        $this->charge($sem_baixa, TicketStatus::OPEN, TicketPriority::LOW);

        $this->assertTrue($this->resolve()->is($sem_baixa));
    }

    public function test_desempata_por_id_quando_toda_a_carga_empata(): void
    {
        $primeiro = User::factory()->create();
        $segundo = User::factory()->create();

        // Carga idêntica: o desempate final tem de ser o menor id, para o
        // resultado ser determinístico entre execuções.
        foreach ([$primeiro, $segundo] as $user) {
            $this->charge($user, TicketStatus::OPEN, TicketPriority::HIGH);
            $this->charge($user, TicketStatus::OPEN, TicketPriority::MEDIUM);
            $this->charge($user, TicketStatus::CLOSED, TicketPriority::LOW);
        }

        $this->assertLessThan($segundo->id, $primeiro->id);
        $this->assertTrue($this->resolve()->is($primeiro));
    }

    public function test_lanca_excecao_quando_nao_ha_responsaveis_cadastrados(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Nenhum responsável disponível para atribuição automática.');

        $this->resolve();
    }

    public function test_preview_reusa_as_contagens_ja_carregadas(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create([
            'assigned_to' => $user->id,
            'status' => TicketStatus::OPEN->value,
            'priority' => TicketPriority::HIGH->value,
        ]);
        Ticket::factory()->create([
            'assigned_to' => $user->id,
            'status' => TicketStatus::OPEN->value,
            'priority' => TicketPriority::MEDIUM->value,
        ]);
        Ticket::factory()->create([
            'assigned_to' => $user->id,
            'status' => TicketStatus::IN_PROGRESS->value,
            'priority' => TicketPriority::LOW->value,
        ]);
        Ticket::factory()->create([
            'assigned_to' => $user->id,
            'status' => TicketStatus::CLOSED->value,
            'priority' => TicketPriority::HIGH->value,
        ]);

        // `resolve()` devolve o model já com os aliases de withCount.
        $resolved = $this->resolve();

        $this->assertTrue($resolved->is($user));
        $this->assertSame(
            ['open' => 3, 'high' => 1, 'medium' => 1, 'low' => 1],
            (new TicketAssignmentService)->preview($resolved)
        );
    }

    public function test_preview_calcula_as_contagens_quando_nao_estao_carregadas(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create([
            'assigned_to' => $user->id,
            'status' => TicketStatus::OPEN->value,
            'priority' => TicketPriority::HIGH->value,
        ]);
        Ticket::factory()->create([
            'assigned_to' => $user->id,
            'status' => TicketStatus::IN_PROGRESS->value,
            'priority' => TicketPriority::MEDIUM->value,
        ]);
        Ticket::factory()->create([
            'assigned_to' => $user->id,
            'status' => TicketStatus::RESOLVED->value,
            'priority' => TicketPriority::HIGH->value,
        ]);

        $this->assertArrayNotHasKey('open_count', $user->getAttributes());

        $this->assertSame(
            ['open' => 2, 'high' => 1, 'medium' => 1, 'low' => 0],
            (new TicketAssignmentService)->preview($user)
        );
    }
}
