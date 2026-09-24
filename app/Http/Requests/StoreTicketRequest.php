<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', Rule::in(TicketPriority::values())],
            'status' => ['required', Rule::in(TicketStatus::values())],
            'assigned_to' => ['required', 'exists:users,id'],
            'opened_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O título é obrigatório.',
            'description.required' => 'A descrição é obrigatória.',
            'priority.required' => 'A prioridade é obrigatória.',
            'status.required' => 'O status é obrigatório.',
            'assigned_to.required' => 'O responsável é obrigatório.',
            'assigned_to.exists' => 'O responsável selecionado é inválido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->opened_at)) {
            $this->merge(['opened_at' => now()->toDateTimeString()]);
        }
    }
}
