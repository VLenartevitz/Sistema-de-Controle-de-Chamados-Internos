<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    /**
     * Tamanho mínimo da descrição, exigido pelo requisito 2.2 do desafio.
     * Fica no model para que os dois FormRequests compartilhem o mesmo número.
     */
    public const MIN_DESCRIPTION_LENGTH = 10;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'assigned_to',
        'opened_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'opened_at' => 'datetime',
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withDefault([
            'name' => 'Sem responsável',
            'email' => '',
        ]);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', TicketStatus::openStatuses());
    }

    public function scopeByPriority($query, ?string $priority)
    {
        if (blank($priority)) {
            return $query;
        }

        return $query->where('priority', $priority);
    }

    public function scopeByStatus($query, ?string $status)
    {
        if (blank($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeByAssignee($query, ?int $assignee)
    {
        if (blank($assignee)) {
            return $query;
        }

        return $query->where('assigned_to', $assignee);
    }

    public function scopeByOpenedAtRange($query, ?string $from, ?string $to)
    {
        if (! blank($from)) {
            $query->whereDate('opened_at', '>=', $from);
        }

        if (! blank($to)) {
            $query->whereDate('opened_at', '<=', $to);
        }

        return $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if (blank($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
