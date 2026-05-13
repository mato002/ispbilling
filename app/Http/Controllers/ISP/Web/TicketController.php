<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $isp = Auth::user()->isp;

        $query = Ticket::with(['customer', 'assignedTo'])
            ->where('isp_id', $isp->id)
            ->customerTickets()
            ->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', '%'.$search.'%')
                    ->orWhere('message', 'like', '%'.$search.'%');
            });
        }

        $tickets = $query->paginate(20)->withQueryString();

        return view('isp.tickets.index', compact('tickets'));
    }

    public function create(Request $request): View
    {
        $isp = Auth::user()->isp;

        $customers = Customer::where('isp_id', $isp->id)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'username']);

        $preselectedCustomer = null;
        if ($request->has('customer_id')) {
            $preselectedCustomer = $customers->firstWhere('id', (int) $request->get('customer_id'));
        }

        return view('isp.tickets.create', [
            'customers' => $customers,
            'preselectedCustomer' => $preselectedCustomer,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $isp = $user->isp;

        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'priority' => ['required', 'in:low,normal,high'],
        ]);

        $ticket = Ticket::create([
            'isp_id' => $isp->id,
            'customer_id' => $data['customer_id'] ?? null,
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => Ticket::STATUS_OPEN,
            'priority' => $data['priority'],
            'channel' => 'portal',
            'ticket_kind' => Ticket::KIND_CUSTOMER,
        ]);

        return redirect()
            ->route('isp.tickets.show', $ticket)
            ->with('success', 'Ticket created successfully.');
    }

    public function show(Ticket $ticket): View
    {
        $this->authorizeTicket($ticket);

        $ticket->load(['customer', 'createdBy', 'assignedTo', 'replies.user', 'replies.customer']);

        return view('isp.tickets.show', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicket($ticket);

        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'priority' => ['required', 'in:low,normal,high'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $ticket->update($data);

        return back()->with('success', 'Ticket updated successfully.');
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicket($ticket);

        $data = $request->validate([
            'message' => ['required', 'string'],
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'customer_id' => $ticket->customer_id,
            'message' => $data['message'],
        ]);

        if ($ticket->status === Ticket::STATUS_OPEN) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        return back()->with('success', 'Reply added successfully.');
    }

    protected function authorizeTicket(Ticket $ticket): void
    {
        $isp = Auth::user()->isp;

        abort_unless($isp && $ticket->isp_id === $isp->id && $ticket->isCustomerTicket(), 404);
    }
}
