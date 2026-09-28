<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Props compartilhadas do HandleInertiaRequests: a tela lê o aviso de sucesso do
 * store/update por `flash.success`, e não pelo retorno do redirect.
 */
class HandleInertiaRequestsTest extends TestCase
{
    use RefreshDatabase;

    private function props(): array
    {
        return $this->get('/tickets')->viewData('page')['props'];
    }

    public function test_compartilha_a_mensagem_de_sucesso_do_store(): void
    {
        $user = User::factory()->create();

        $this->post('/tickets', [
            'title' => 'Impressora sem toner',
            'description' => 'A impressora do segundo andar está sem toner.',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => $user->id,
        ])->assertRedirect();

        $this->assertSame('Chamado criado com sucesso!', $this->props()['flash']['success']);
    }

    public function test_compartilha_a_mensagem_de_sucesso_do_update(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['assigned_to' => $user->id]);

        $this->put("/tickets/{$ticket->id}", [
            'title' => 'Chamado atualizado',
            'description' => 'Descrição atualizada do chamado.',
            'priority' => 'low',
            'status' => 'in_progress',
            'assigned_to' => $user->id,
            'opened_at' => $ticket->opened_at->toDateTimeString(),
        ])->assertRedirect();

        $this->assertSame('Chamado atualizado com sucesso!', $this->props()['flash']['success']);
    }

    public function test_flash_de_erro_fica_nulo_quando_nao_houve_erro(): void
    {
        $props = $this->props();

        $this->assertArrayHasKey('error', $props['flash']);
        $this->assertNull($props['flash']['error']);
    }

    public function test_a_resposta_traz_a_versao_de_assets_para_o_inertia(): void
    {
        $page = $this->get('/tickets')->viewData('page');

        // O Inertia usa isso para decidir quando invalidar o cache do cliente.
        $this->assertArrayHasKey('version', $page);
    }

    public function test_os_erros_de_validacao_chegam_a_tela_em_portugues(): void
    {
        $this->post('/tickets', ['title' => ''])
            ->assertSessionHasErrors(['title', 'description', 'priority', 'status', 'assigned_to']);

        // O Inertia publica `errors` a partir do error bag da sessão; o que o
        // projeto define são as mensagens em PT-BR dos FormRequests.
        $erros = $this->post('/tickets', ['title' => ''])->assertSessionHasErrors([
            'title' => 'O título é obrigatório.',
            'description' => 'A descrição é obrigatória.',
            'priority' => 'A prioridade é obrigatória.',
            'status' => 'O status é obrigatório.',
            'assigned_to' => 'O responsável é obrigatório.',
        ]);

        $this->assertNotNull($erros);
    }
}
