<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:'.Ticket::MIN_DESCRIPTION_LENGTH],
            'priority' => ['required', Rule::in(TicketPriority::values())],
            'status' => ['required', Rule::in(TicketStatus::values())],
            'assigned_to' => ['required', 'exists:users,id'],
            'opened_at' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O título é obrigatório.',
            'description.required' => 'A descrição é obrigatória.',
            'description.min' => 'A descrição deve ter pelo menos '.Ticket::MIN_DESCRIPTION_LENGTH.' caracteres.',
            'priority.required' => 'A prioridade é obrigatória.',
            'status.required' => 'O status é obrigatório.',
            'assigned_to.required' => 'O responsável é obrigatório.',
            'assigned_to.exists' => 'O responsável selecionado é inválido.',
        ];
    }
}
