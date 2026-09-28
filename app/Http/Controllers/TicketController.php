<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketAssignmentService;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $allowedSorts = ['opened_at', 'priority', 'status', 'created_at', 'title'];
        $sort = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        // Validação leve: ignora valores inválidos em vez de 422 para manter UX de listagem
        $priority = in_array($request->input('priority'), TicketPriority::values(), true) ? $request->input('priority') : null;
        $status = in_array($request->input('status'), TicketStatus::values(), true) ? $request->input('status') : null;
        $assignedTo = $request->input('assigned_to');
        $assignedTo = is_numeric($assignedTo) ? (int) $assignedTo : null;
        $openedFrom = $this->dateOrNull($request->input('opened_from'));
        $openedTo = $this->dateOrNull($request->input('opened_to'));
        $search = $request->input('search');

        $tickets = Ticket::with('assignedUser')
            ->byPriority($priority)
            ->byStatus($status)
            ->byAssignee($assignedTo)
            ->byOpenedAtRange($openedFrom, $openedTo)
            ->search($search)
            ->when($sort === 'priority', function ($q) use ($direction) {
                // Ordenação semântica: high > medium > low via CASE
                $q->orderByRaw('CASE priority WHEN \'high\' THEN 3 WHEN \'medium\' THEN 2 WHEN \'low\' THEN 1 ELSE 0 END '.($direction === 'asc' ? 'ASC' : 'DESC'));
            }, function ($q) use ($sort, $direction) {
                $q->orderBy($sort, $direction);
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'filters' => [
                'search' => $search,
                'priority' => $priority,
                'status' => $status,
                'assigned_to' => $assignedTo,
                'opened_from' => $openedFrom,
                'opened_to' => $openedTo,
                'sort' => $sort,
                'direction' => $direction,
            ],
            ...$this->formOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tickets/Create', [
            ...$this->formOptions(),
            'default_opened_at' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(StoreTicketRequest $request)
    {
        $ticket = Ticket::create($request->validated());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Chamado criado com sucesso!');
    }

    public function nextAssignee(TicketAssignmentService $service): JsonResponse
    {
        try {
            $user = $service->resolve();
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'Cadastre um responsável antes de usar atribuição automática.',
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'reason' => $service->preview($user),
        ]);
    }

    public function show(Ticket $ticket): Response
    {
        $ticket->load('assignedUser');

        return Inertia::render('Tickets/Show', [
            'ticket' => [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'priority' => $ticket->priority->value,
                'priority_label' => $ticket->priority->label(),
                'status' => $ticket->status->value,
                'status_label' => $ticket->status->label(),
                'assigned_to' => $ticket->assigned_to,
                'assigned_user' => $ticket->assignedUser ? ['id' => $ticket->assignedUser->id, 'name' => $ticket->assignedUser->name, 'email' => $ticket->assignedUser->email] : null,
                'opened_at' => $ticket->opened_at->format('d/m/Y H:i'),
                'created_at' => $ticket->created_at->format('d/m/Y H:i'),
                'updated_at' => $ticket->updated_at->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function edit(Ticket $ticket): Response
    {
        return Inertia::render('Tickets/Edit', [
            'ticket' => [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'priority' => $ticket->priority->value,
                'status' => $ticket->status->value,
                'assigned_to' => $ticket->assigned_to,
                'opened_at' => $ticket->opened_at->format('Y-m-d\TH:i'),
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        $ticket->update($request->validated());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Chamado atualizado com sucesso!');
    }

    /**
     * Aceita apenas o formato Y-m-d, que é o que os <input type="date"> do
     * formulário produzem. Qualquer outra coisa é descartada em vez de chegar
     * crua ao whereDate do model, que compararia a data com um valor sem
     * sentido e devolveria um resultado silenciosamente errado.
     */
    private function dateOrNull($value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    /**
     * Opções que as telas de cadastro, edição e listagem precisam montar de
     * forma idêntica. Os rótulos vêm dos próprios enums, então o PHP continua
     * sendo a única fonte de verdade.
     *
     * @return array{users: Collection<int, User>, priorities: array, statuses: array}
     */
    private function formOptions(): array
    {
        return [
            'users' => $this->responsaveis(),
            'priorities' => TicketPriority::options(),
            'statuses' => TicketStatus::options(),
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function responsaveis()
    {
        return User::orderBy('name')->get(['id', 'name', 'email']);
    }
}
