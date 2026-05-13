<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HelpdeskController extends Controller
{
    public function index(Request $request): View
    {
        $isp = Auth::user()->isp;

        $query = Ticket::with(['createdBy', 'assignedTo'])
            ->where('isp_id', $isp->id)
            ->platformTickets()
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

        return view('isp.helpdesk.index', compact('tickets'));
    }

    public function create(): View
    {
        abort_unless(Auth::user()->isIspAdmin(), 403);

        return view('isp.helpdesk.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isIspAdmin(), 403);

        $user = Auth::user();
        $isp = $user->isp;

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'priority' => ['required', 'in:low,normal,high'],
        ]);

        $ticket = Ticket::create([
            'isp_id' => $isp->id,
            'customer_id' => null,
            'created_by_user_id' => $user->id,
            'assigned_to_user_id' => null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => Ticket::STATUS_OPEN,
            'priority' => $data['priority'],
            'channel' => 'platform',
            'ticket_kind' => Ticket::KIND_PLATFORM,
        ]);

        return redirect()
            ->route('isp.helpdesk.show', $ticket)
            ->with('success', 'Your request was sent to PrimeBilling support.');
    }

    public function show(Ticket $ticket): View
    {
        $this->authorizePlatformTicket($ticket);

        $ticket->load(['createdBy', 'assignedTo', 'replies.user', 'replies.customer']);

        return view('isp.helpdesk.show', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizePlatformTicket($ticket);

        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'priority' => ['required', 'in:low,normal,high'],
        ]);

        $ticket->update($data);

        return back()->with('success', 'Ticket updated successfully.');
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizePlatformTicket($ticket);

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

        return back()->with('success', 'Reply added successfully.');
    }

    protected function authorizePlatformTicket(Ticket $ticket): void
    {
        $isp = Auth::user()->isp;

        abort_unless($isp && $ticket->isp_id === $isp->id && $ticket->isPlatformTicket(), 404);
    }
}
