<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $responsaveis = [
            User::factory()->create([
                'name' => 'João Silva',
                'email' => 'joao.silva@example.com',
            ]),
            User::factory()->create([
                'name' => 'Maria Souza',
                'email' => 'maria.souza@example.com',
            ]),
            User::factory()->create([
                'name' => 'Carlos Oliveira',
                'email' => 'carlos.oliveira@example.com',
            ]),
        ];

        foreach ($this->chamados() as $chamado) {
            Ticket::create([
                'title' => $chamado['title'],
                'description' => $chamado['description'],
                'priority' => $chamado['priority'],
                'status' => $chamado['status'],
                'assigned_to' => $responsaveis[$chamado['responsavel']]->id,
                'opened_at' => now()->subDays($chamado['dias'])->setTime(9, 15),
            ]);
        }
    }

    /**
     * Chamados de exemplo com o vocabulário do cliente: problemas de informática
     * do dia a dia. A carga é desigual de propósito, para a tela de listagem já
     * nascer com histórico e a distribuição automática ter o que balancear.
     *
     * @return array<int, array{title: string, description: string, priority: string, status: string, responsavel: int, dias: int}>
     */
    private function chamados(): array
    {
        return [
            [
                'title' => 'Impressora do 3º andar não está funcionando',
                'description' => 'A impressora compartilhada do terceiro andar está desligada e não responde ao botão de.power. Ninguém consegue imprimir desde ontem de manhã.',
                'priority' => TicketPriority::HIGH->value,
                'status' => TicketStatus::OPEN->value,
                'responsavel' => 0,
                'dias' => 1,
            ],
            [
                'title' => 'Computador trava ao abrir o Excel',
                'description' => 'A estação de trabalho do financeiro trava sempre que abre uma planilha grande. O Windows reinicia sozinho e o usuário perde o que estava digitando.',
                'priority' => TicketPriority::HIGH->value,
                'status' => TicketStatus::IN_PROGRESS->value,
                'responsavel' => 1,
                'dias' => 2,
            ],
            [
                'title' => 'Solicitação de cadeira nova',
                'description' => 'A cadeira do meu lugar está com um rodízio quebrado e está causando dor nas costas. Gostaria de uma cadeira nova para a minha mesa.',
                'priority' => TicketPriority::LOW->value,
                'status' => TicketStatus::OPEN->value,
                'responsavel' => 2,
                'dias' => 2,
            ],
            [
                'title' => 'Rede lenta no andar contábil',
                'description' => 'A rede do andar contábil fica muito lenta em horário de fechamento mensal. Às vezes a página do sistema bancário não carrega de jeito nenhum.',
                'priority' => TicketPriority::MEDIUM->value,
                'status' => TicketStatus::IN_PROGRESS->value,
                'responsavel' => 0,
                'dias' => 4,
            ],
            [
                'title' => 'Senha do e-mail corporativo expirou',
                'description' => 'Minha senha do e-mail corporativo expirou e não estou recebendo o código de verificação no celular cadastrado. Preciso de ajuda para recuperar o acesso.',
                'priority' => TicketPriority::HIGH->value,
                'status' => TicketStatus::RESOLVED->value,
                'responsavel' => 1,
                'dias' => 6,
            ],
            [
                'title' => 'Mouse sem fio não responde',
                'description' => 'O mouse sem fio da minha mesa parou de responder. Troquei as pilhas e o problema continuou. Preciso de um mouse novo ou de um wired emprestado.',
                'priority' => TicketPriority::LOW->value,
                'status' => TicketStatus::CLOSED->value,
                'responsavel' => 2,
                'dias' => 9,
            ],
            [
                'title' => 'Monitor com manchas na tela',
                'description' => 'O monitor da recepção está com manchas escuras na tela, como se fosse um vazamento de líquido. Precisa de avaliação técnica para ver se vale a troca.',
                'priority' => TicketPriority::MEDIUM->value,
                'status' => TicketStatus::IN_PROGRESS->value,
                'responsavel' => 2,
                'dias' => 11,
            ],
            [
                'title' => 'Instalação do antivírus em três máquinas',
                'description' => 'Preciso da instalação e configuração do antivírus corporativo em três estações de trabalho do departamento comercial.',
                'priority' => TicketPriority::MEDIUM->value,
                'status' => TicketStatus::OPEN->value,
                'responsavel' => 0,
                'dias' => 13,
            ],
            [
                'title' => 'Ponto de acesso sem sinal na sala de reunião',
                'description' => 'O wi-fi da sala de reunião não pega sinal desde a última mudança de layout da parede. Quem está do lado de fora do escritório também não consegue acessar.',
                'priority' => TicketPriority::MEDIUM->value,
                'status' => TicketStatus::OPEN->value,
                'responsavel' => 1,
                'dias' => 15,
            ],
            [
                'title' => 'Backup do servidor falhou na madrugada',
                'description' => 'O job de backup agendado para as três da manhã registrou falha no log. O banco de produção aparentemente não foi copiado para o storage externo.',
                'priority' => TicketPriority::HIGH->value,
                'status' => TicketStatus::IN_PROGRESS->value,
                'responsavel' => 0,
                'dias' => 18,
            ],
            [
                'title' => 'Solicitação de segunda monitor',
                'description' => 'Para facilitar a conferência de planilhas, gostaria de um segundo monitor na minha mesa. Hoje trabalho com o notebook e um monitor pequeno.',
                'priority' => TicketPriority::LOW->value,
                'status' => TicketStatus::RESOLVED->value,
                'responsavel' => 2,
                'dias' => 21,
            ],
            [
                'title' => 'Teclado com teclas travadas',
                'description' => 'As teclas F e G do meu teclado às vezes não respondem quando digito. Já reiniciei e o problema continua aparecendo.',
                'priority' => TicketPriority::MEDIUM->value,
                'status' => TicketStatus::CLOSED->value,
                'responsavel' => 1,
                'dias' => 24,
            ],
            [
                'title' => 'Acesso ao sistema de folha não liberado',
                'description' => 'Fui desligado recentemente no sistema de folha e não consigo mais registrar minhas horas. Preciso de liberação de acesso.',
                'priority' => TicketPriority::HIGH->value,
                'status' => TicketStatus::CLOSED->value,
                'responsavel' => 0,
                'dias' => 27,
            ],
            [
                'title' => 'Vazamento de água perto do rack',
                'description' => 'Há umidade no chão próximo ao rack de servidores no corredor técnico. Alguém pode ter derramado líquido ali. Aviso registrado para ação preventiva do TI.',
                'priority' => TicketPriority::HIGH->value,
                'status' => TicketStatus::RESOLVED->value,
                'responsavel' => 1,
                'dias' => 29,
            ],
        ];
    }
}
