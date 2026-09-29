<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Popula os dados de demonstração apenas quando o banco está vazio.
 *
 * O entrypoint do container chama este comando depois das migrations. Sem ele,
 * quem clonasse o projeto teria um sistema com zero responsáveis — e como o
 * campo `assigned_to` é obrigatório, não conseguiria nem abrir o primeiro
 * chamado. O requisito 3.4 do desafio pede "pelo menos 3 responsáveis
 * disponíveis na aplicação", então o seed precisa acontecer no primeiro boot.
 *
 * A guarda é por `users` vazio, e não um `migrate --seed` incondicional: o
 * seeder cria os registros com `create()`, então rodá-lo a cada boot duplicaria
 * os responsáveis e os chamados a cada reinício do container.
 */
class SeedDemoData extends Command
{
    protected $signature = 'chamados:seed-if-empty';

    protected $description = 'Popula responsáveis e chamados de exemplo somente se o banco ainda estiver vazio.';

    public function handle(): int
    {
        // Antes das migrations a tabela nem existe; o entrypoint chama este
        // comando logo depois de `migrate`, mas o comando também é seguro de
        // rodar na mão contra um banco em branco.
        if (! Schema::hasTable('users')) {
            $this->components->warn('Tabela users inexistente: rode as migrations antes do seed.');

            return self::FAILURE;
        }

        if (User::query()->exists()) {
            $this->components->info('Banco já possui responsáveis: seed de demonstração ignorado.');

            return self::SUCCESS;
        }

        $this->components->info('Banco vazio: populando responsáveis e chamados de demonstração.');
        $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        $this->components->info(sprintf(
            'Pronto: %d responsáveis e %d chamados de exemplo.',
            User::query()->count(),
            Ticket::query()->count(),
        ));

        return self::SUCCESS;
    }
}
