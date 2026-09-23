<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $tickets = Ticket::with('assignedUser')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tickets/Create', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'priorities' => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], TicketPriority::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], TicketStatus::cases()),
            'default_opened_at' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(StoreTicketRequest $request)
    {
        $ticket = Ticket::create($request->validated());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Chamado criado com sucesso!');
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
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'priorities' => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], TicketPriority::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], TicketStatus::cases()),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        $ticket->update($request->validated());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Chamado atualizado com sucesso!');
    }
}
