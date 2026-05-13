<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HelpdeskTicketController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $query = Ticket::with(['isp', 'createdBy', 'assignedTo'])
            ->platformTickets()
            ->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($ispId = $request->get('isp_id')) {
            $query->where('isp_id', $ispId);
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', '%'.$search.'%')
                    ->orWhere('message', 'like', '%'.$search.'%');
            });
        }

        $tickets = $query->paginate(25)->withQueryString();
        $isps = ISP::orderBy('name')->get(['id', 'name']);

        return view('admin.helpdesk.index', compact('tickets', 'isps'));
    }

    public function show(Ticket $ticket): View
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
        abort_unless($ticket->isPlatformTicket(), 404);

        $ticket->load(['isp', 'customer', 'createdBy', 'assignedTo', 'replies.user', 'replies.customer']);

        return view('admin.helpdesk.show', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
        abort_unless($ticket->isPlatformTicket(), 404);

        if (! $request->filled('assigned_to_user_id')) {
            $request->merge(['assigned_to_user_id' => null]);
        }

        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'priority' => ['required', 'in:low,normal,high'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $data['assigned_to_user_id'] = $data['assigned_to_user_id'] ?: null;

        $ticket->update($data);

        return back()->with('success', 'Ticket updated.');
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
        abort_unless($ticket->isPlatformTicket(), 404);

        $data = $request->validate([
            'message' => ['required', 'string'],
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'customer_id' => null,
            'message' => $data['message'],
        ]);

        if ($ticket->status === Ticket::STATUS_OPEN) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        return back()->with('success', 'Reply sent to the ISP.');
    }
}
