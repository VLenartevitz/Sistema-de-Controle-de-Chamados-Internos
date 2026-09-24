<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketAssignmentService
{
    /**
     * Resolve o responsável com menor carga seguindo RN03/RN04/RN06 (atualizada).
     * Ordem: total OPEN+IN_PROGRESS ASC, high ASC, medium ASC, low ASC, id ASC
     * Apenas OPEN e IN_PROGRESS entram na contagem.
     */
    public function resolve(): User
    {
        return DB::transaction(function () {
            $user = User::query()
                ->withCount([
                    'tickets as open_count' => fn ($q) => $q->open(),
                    'tickets as high_count' => fn ($q) => $q->open()->byPriority(TicketPriority::HIGH->value),
                    'tickets as medium_count' => fn ($q) => $q->open()->byPriority(TicketPriority::MEDIUM->value),
                    'tickets as low_count' => fn ($q) => $q->open()->byPriority(TicketPriority::LOW->value),
                ])
                ->orderBy('open_count')
                ->orderBy('high_count')
                ->orderBy('medium_count')
                ->orderBy('low_count')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $user) {
                throw new \RuntimeException('Nenhum responsável disponível para atribuição automática.');
            }

            return $user;
        });
    }

    /**
     * Retorna array com contagens para preview / justificativa.
     */
    public function preview(User $user): array
    {
        // Reusa as contagens já carregadas se existirem, senão calcula
        if (isset($user->open_count)) {
            return [
                'open' => (int) $user->open_count,
                'high' => (int) $user->high_count,
                'medium' => (int) $user->medium_count,
                'low' => (int) $user->low_count,
            ];
        }

        return [
            'open' => (int) $user->tickets()->open()->count(),
            'high' => (int) $user->tickets()->open()->byPriority(TicketPriority::HIGH->value)->count(),
            'medium' => (int) $user->tickets()->open()->byPriority(TicketPriority::MEDIUM->value)->count(),
            'low' => (int) $user->tickets()->open()->byPriority(TicketPriority::LOW->value)->count(),
        ];
    }
}
