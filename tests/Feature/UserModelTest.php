<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_tickets_lista_apenas_os_chamados_do_usuario(): void
    {
        $user = User::factory()->create();
        $outro = User::factory()->create();

        $meu = Ticket::factory()->create(['assigned_to' => $user->id]);
        $dele = Ticket::factory()->create(['assigned_to' => $outro->id]);

        $this->assertTrue($user->tickets->first()->is($meu));
        $this->assertCount(1, $user->tickets);
        $this->assertTrue($outro->tickets->first()->is($dele));
    }

    public function test_tickets_usa_assigned_to_como_chave_estrangeira(): void
    {
        // A relação é resolvida por `assigned_to` e não pelo `user_id` padrão.
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['assigned_to' => $user->id]);

        $this->assertSame(
            $ticket->id,
            $user->tickets()->where('tickets.id', $ticket->id)->value('id')
        );
    }

    public function test_senha_e_criptografada_na_propria_atribuicao(): void
    {
        $user = User::factory()->create(['password' => 'senha-em-texto-puro']);

        // O cast 'hashed' evita gravar a senha em claro quando a atribuição
        // acontece fora do cadastro.
        $this->assertNotSame('senha-em-texto-puro', $user->password);
        $this->assertTrue(Hash::check('senha-em-texto-puro', $user->password));
    }

    public function test_email_verified_at_e_tratado_como_data(): void
    {
        $verificado = User::factory()->create(['email_verified_at' => '2026-02-03 10:00:00']);
        $naoVerificado = User::factory()->unverified()->create();

        $this->assertInstanceOf(Carbon::class, $verificado->email_verified_at);
        $this->assertSame('2026-02-03 10:00', $verificado->email_verified_at->format('Y-m-d H:i'));
        $this->assertNull($naoVerificado->email_verified_at);
    }

    public function test_credenciais_nao_sao_expostas_na_serializacao(): void
    {
        $user = User::factory()->create();

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
    }

    public function test_somente_campos_declarados_sao_preenchiveis_em_massa(): void
    {
        $user = new User;
        $user->fill([
            'name' => 'Maria Souza',
            'email' => 'maria.souza@example.com',
            'password' => 'segredo',
            'is_admin' => true,
        ]);

        $this->assertSame('Maria Souza', $user->name);
        $this->assertSame('maria.souza@example.com', $user->email);
        $this->assertNull($user->is_admin);
    }

    public function test_o_model_de_chamado_e_os_usos_que_o_recebem_continuam_preservados(): void
    {
        // Guarda contra alteração acidental do fillable do Ticket: qualquer
        // campo novo precisa entrar na lista para o store() funcionar.
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'assigned_to' => $user->id,
            'priority' => TicketPriority::HIGH->value,
            'status' => TicketStatus::OPEN->value,
        ]);

        $this->assertSame(TicketPriority::HIGH, $ticket->priority);
        $this->assertSame(TicketStatus::OPEN, $ticket->status);
        $this->assertTrue($ticket->assignedUser->is($user));
    }
}
