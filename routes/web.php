<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('tickets.index');
});

Route::get('/tickets/next-assignee', [TicketController::class, 'nextAssignee'])->name('tickets.nextAssignee');
Route::resource('tickets', TicketController::class);
