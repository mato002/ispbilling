<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Export\CsvExporter;
use App\Support\FormSettingsService;
use App\Support\Search\SearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    use InteractsWithListings;

    public function __construct(
        protected NotificationService $notifications,
        protected FormSettingsService $formSettings,
    ) {}

    public function index(Request $request): View
    {
        $isp = Auth::user()->isp;

        $query = $this->filteredTicketsQuery($request, $isp->id);
        $perPage = $this->resolvePerPage($request);
        $tickets = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('isp.tickets.index'), [
            'q' => 'Search',
            'status' => [
                'label' => 'Status',
                'values' => [
                    'open' => 'Open',
                    'in_progress' => 'In progress',
                    'resolved' => 'Resolved',
                    'closed' => 'Closed',
                ],
            ],
            'priority' => [
                'label' => 'Priority',
                'values' => [
                    'low' => 'Low',
                    'normal' => 'Normal',
                    'high' => 'High',
                ],
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('isp.tickets.export.csv'));

        return view('isp.tickets.index', compact('tickets', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $isp = Auth::user()->isp;
        $query = $this->filteredTicketsQuery($request, $isp->id);
        $filename = 'customer-tickets-'.now()->format('Ymd-His').'.csv';

        return CsvExporter::download($filename, [
            'ID', 'Subject', 'Customer', 'Status', 'Priority', 'Channel', 'Created', 'Updated',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($tickets) use ($out) {
                foreach ($tickets as $ticket) {
                    fputcsv($out, [
                        $ticket->id,
                        $ticket->subject,
                        $ticket->customer?->full_name,
                        $ticket->status,
                        $ticket->priority,
                        $ticket->channel,
                        $ticket->created_at?->format('Y-m-d H:i:s'),
                        $ticket->updated_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    protected function filteredTicketsQuery(Request $request, int $ispId): Builder
    {
        $query = Ticket::with(['customer', 'assignedTo'])
            ->where('isp_id', $ispId)
            ->customerTickets()
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($pattern = SearchHelper::likePattern($request->q)) {
            $query->where(function ($q) use ($pattern) {
                $q->where('subject', 'like', $pattern)
                    ->orWhere('message', 'like', $pattern);
            });
        }

        return $query;
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
            'formFields' => $this->formSettings->resolvedFields('ticket', $isp->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $isp = $user->isp;

        $data = $this->formSettings->validateRequest($request, 'ticket', [
            'customer_id' => [
                Rule::exists('customers', 'id')->where('isp_id', $isp->id),
            ],
            'subject' => ['string', 'max:255'],
            'message' => ['string'],
            'priority' => ['in:low,normal,high'],
        ], $isp->id);

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

        $this->notifications->supportTicketEvent($ticket, 'created', 'Opened by '.$user->name);

        return redirect()
            ->route('isp.tickets.show', $ticket)
            ->with('success', 'Ticket created successfully.');
    }

    public function show(Ticket $ticket): View
    {
        $this->authorizeTicket($ticket);

        $ticket->load(['customer', 'createdBy', 'assignedTo', 'replies.user', 'replies.customer']);

        $staffUsers = User::query()
            ->where('isp_id', $ticket->isp_id)
            ->whereIn('role', ['isp_admin', 'isp_staff'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('isp.tickets.show', compact('ticket', 'staffUsers'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeTicket($ticket);

        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'priority' => ['required', 'in:low,normal,high'],
            'assigned_to_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('isp_id', $ticket->isp_id),
            ],
        ]);

        $previousAssignee = $ticket->assigned_to_user_id;
        $previousStatus = $ticket->status;

        $ticket->update($data);

        if (($data['assigned_to_user_id'] ?? null) != $previousAssignee && ! empty($data['assigned_to_user_id'])) {
            $assignee = User::find($data['assigned_to_user_id']);
            $this->notifications->supportTicketEvent(
                $ticket,
                'assigned',
                'Assigned to '.($assignee?->name ?? 'staff')
            );
        }

        if ($data['status'] !== $previousStatus) {
            $this->notifications->supportTicketEvent(
                $ticket,
                'status',
                'Status changed to '.$data['status']
            );
        }

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
            'customer_id' => null,
            'message' => $data['message'],
        ]);

        if ($ticket->status === Ticket::STATUS_OPEN) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);
        }

        $this->notifications->supportTicketEvent(
            $ticket,
            'replied',
            'Reply from '.Auth::user()->name
        );

        return back()->with('success', 'Reply added successfully.');
    }

    protected function authorizeTicket(Ticket $ticket): void
    {
        $isp = Auth::user()->isp;

        abort_unless($isp && $ticket->isp_id === $isp->id && $ticket->isCustomerTicket(), 404);
    }
}
