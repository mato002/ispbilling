<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(protected NotificationService $notifications) {}

    public function index(): View|RedirectResponse
    {
        $customer = $this->currentCustomer();
        if (! $customer) {
            return redirect()->route('customer.login');
        }

        $tickets = Ticket::query()
            ->where('isp_id', $customer->isp_id)
            ->where('customer_id', $customer->id)
            ->customerTickets()
            ->latest()
            ->paginate(20);

        return view('customer.tickets.index', compact('customer', 'tickets'));
    }

    public function create(): View|RedirectResponse
    {
        $customer = $this->currentCustomer();
        if (! $customer) {
            return redirect()->route('customer.login');
        }

        return view('customer.tickets.create', compact('customer'));
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $this->currentCustomer();
        if (! $customer) {
            return redirect()->route('customer.login');
        }

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,normal,high'],
        ]);

        $ticket = Ticket::create([
            'isp_id' => $customer->isp_id,
            'customer_id' => $customer->id,
            'created_by_user_id' => null,
            'assigned_to_user_id' => null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => Ticket::STATUS_OPEN,
            'priority' => $data['priority'],
            'channel' => 'customer_portal',
            'ticket_kind' => Ticket::KIND_CUSTOMER,
        ]);

        $this->notifications->supportTicketEvent(
            $ticket,
            'created',
            'Opened by '.$customer->full_name.' via customer portal'
        );

        return redirect()
            ->route('customer.tickets.show', $ticket)
            ->with('success', 'Support ticket submitted. Our team will respond soon.');
    }

    public function show(Ticket $ticket): View|RedirectResponse
    {
        $customer = $this->currentCustomer();
        if (! $customer) {
            return redirect()->route('customer.login');
        }

        $this->authorizeTicket($ticket, $customer);

        $ticket->load(['replies.user', 'replies.customer', 'assignedTo']);

        return view('customer.tickets.show', compact('customer', 'ticket'));
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $customer = $this->currentCustomer();
        if (! $customer) {
            return redirect()->route('customer.login');
        }

        $this->authorizeTicket($ticket, $customer);

        if (in_array($ticket->status, [Ticket::STATUS_CLOSED, Ticket::STATUS_RESOLVED], true)) {
            return back()->with('error', 'This ticket is closed. Open a new ticket if you still need help.');
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'customer_id' => $customer->id,
            'message' => $data['message'],
        ]);

        if ($ticket->status === Ticket::STATUS_OPEN) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        $this->notifications->supportTicketEvent(
            $ticket,
            'replied',
            'Customer reply from '.$customer->full_name
        );

        return back()->with('success', 'Your reply was sent.');
    }

    protected function currentCustomer(): ?Customer
    {
        $customerId = session('customer_id');
        if (! $customerId) {
            return null;
        }

        return Customer::query()->with('isp')->find($customerId);
    }

    protected function authorizeTicket(Ticket $ticket, Customer $customer): void
    {
        abort_unless(
            $ticket->isCustomerTicket()
            && (int) $ticket->customer_id === (int) $customer->id
            && (int) $ticket->isp_id === (int) $customer->isp_id,
            404
        );
    }
}
