<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(): View
    {
        $query = SupportTicket::with(['user', 'assignee'])->latest();

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($priority = request('priority')) {
            $query->where('priority', $priority);
        }

        if ($search = request('search')) {
            $query->where('subject', 'like', "%{$search}%");
        }

        $tickets = $query->paginate(15);

        return view('admin.support-tickets.index', compact('tickets'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'assignee']);
        $admins = User::role('admin')->get();

        return view('admin.support-tickets.show', compact('ticket', 'admins'));
    }

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status'      => 'nullable|in:open,in_progress,resolved,closed',
            'priority'    => 'nullable|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update($validated);

        return redirect()->route('admin.support-tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully.');
    }

    public function close(SupportTicket $ticket): RedirectResponse
    {
        $ticket->update([
            'status'     => 'closed',
            'resolved_at' => now(),
        ]);

        return redirect()->route('admin.support-tickets.index')
            ->with('success', 'Ticket closed successfully.');
    }
}
