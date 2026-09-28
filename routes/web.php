<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('tickets.index');
});

Route::get('/tickets/next-assignee', [TicketController::class, 'nextAssignee'])->name('tickets.nextAssignee');

// `destroy` fica de fora: a especificação não pede exclusão de chamados e não
// há método no controller. Sem o `except`, a rota existia e o DELETE devolvia 500.
Route::resource('tickets', TicketController::class)->except(['destroy']);
