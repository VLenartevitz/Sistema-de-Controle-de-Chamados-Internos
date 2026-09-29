<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * O entrypoint do container chama `chamados:seed-if-empty` depois das
 * migrations. É o que faz um clone limpo já nascer com os responsáveis do
 * requisito 3.4 e com a listagem populate — sem ele o sistema abriria vazio e,
 * como `assigned_to` é obrigatório, não daria para abrir o primeiro chamado.
 */
class SeedDemoDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_popula_responsaveis_e_chamados_quando_o_banco_esta_vazio(): void
    {
        $this->artisan('chamados:seed-if-empty')
            ->assertSuccessful();

        $this->assertSame(3, User::count());
        $this->assertSame(14, Ticket::count());
    }

    public function test_nao_duplica_dados_quando_o_banco_ja_tem_responsaveis(): void
    {
        User::factory()->create(['name' => 'Quem Já Estava Aqui']);

        $this->artisan('chamados:seed-if-empty')
            ->assertSuccessful();

        // O seed inteiro é ignorado, não só os usuários duplicados: o chamado
        // de demonstração também não entra, para não haver um volume que não
        // corresponde a nenhum responsável do seeder.
        $this->assertSame(1, User::count());
        $this->assertSame(0, Ticket::count());
        $this->assertSame('Quem Já Estava Aqui', User::query()->firstOrFail()->name);
    }

    public function test_pode_rodar_repetidamente_sem_alterar_nada(): void
    {
        $this->artisan('chamados:seed-if-empty')->assertSuccessful();
        $this->artisan('chamados:seed-if-empty')->assertSuccessful();
        $this->artisan('chamados:seed-if-empty')->assertSuccessful();

        // O guard de `users` vazio é o que torna o boot do container seguro:
        // `docker compose restart` passa por aqui de novo a cada vez.
        $this->assertSame(3, User::count());
        $this->assertSame(14, Ticket::count());
    }

    public function test_falha_quando_a_tabela_de_responsaveis_nao_existe(): void
    {
        // `tickets` cai primeiro porque é a tabela que referencia `users`.
        Schema::drop('tickets');
        Schema::drop('users');

        $this->artisan('chamados:seed-if-empty')
            ->assertFailed();
    }
}
