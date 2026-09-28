<?php

namespace App\Enums;

enum TicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Aberto',
            self::IN_PROGRESS => 'Em andamento',
            self::RESOLVED => 'Resolvido',
            self::CLOSED => 'Fechado',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function openStatuses(): array
    {
        return [self::OPEN->value, self::IN_PROGRESS->value];
    }

    /**
     * Formato que o formulário e a listagem consomem. O enum é o dono do
     * próprio rótulo, então a serialização também mora aqui.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
