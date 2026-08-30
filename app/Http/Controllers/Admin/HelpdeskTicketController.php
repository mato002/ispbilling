<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\ISP;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\NotificationService;
use App\Support\Export\CsvExporter;
use App\Support\Search\SearchHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HelpdeskTicketController extends Controller
{
    use InteractsWithListings;

    public function __construct(protected NotificationService $notifications) {}

    public function index(Request $request): View
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $isps = ISP::orderBy('name')->get(['id', 'name']);
        $ispValues = $isps->mapWithKeys(fn ($isp) => [(string) $isp->id => $isp->name])->all();

        $query = $this->filteredTicketsQuery($request);
        $perPage = $this->resolvePerPage($request, 25);
        $tickets = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('admin.helpdesk.index'), [
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
            'isp_id' => [
                'label' => 'ISP',
                'values' => $ispValues,
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('admin.helpdesk.export.csv'));

        return view('admin.helpdesk.index', compact('tickets', 'isps', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $query = $this->filteredTicketsQuery($request);

        return CsvExporter::download(
            'helpdesk-tickets-'.now()->format('Ymd-His').'.csv',
            ['ID', 'ISP', 'Subject', 'From', 'Priority', 'Status', 'Created', 'Updated'],
            function ($out) use ($query) {
                $query->chunk(200, function ($tickets) use ($out) {
                    foreach ($tickets as $ticket) {
                        fputcsv($out, [
                            $ticket->id,
                            $ticket->isp?->name,
                            $ticket->subject,
                            $ticket->createdBy?->name,
                            $ticket->priority,
                            $ticket->status,
                            $ticket->created_at?->format('Y-m-d H:i:s'),
                            $ticket->updated_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
            },
        );
    }

    protected function filteredTicketsQuery(Request $request)
    {
        $query = Ticket::with(['isp', 'createdBy', 'assignedTo'])
            ->platformTickets()
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('isp_id')) {
            $query->where('isp_id', $request->input('isp_id'));
        }

        $pattern = SearchHelper::likePattern($request->input('q'));

        if ($pattern !== null) {
            $query->where(function ($q) use ($pattern) {
                $q->where('subject', 'like', $pattern)
                    ->orWhere('message', 'like', $pattern);
            });
        }

        return $query;
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

        $previousAssignee = $ticket->assigned_to_user_id;
        $previousStatus = $ticket->status;

        $ticket->update($data);

        if (($data['assigned_to_user_id'] ?? null) != $previousAssignee && ! empty($data['assigned_to_user_id'])) {
            $this->notifications->supportTicketEvent($ticket, 'assigned', 'Assigned by platform support');
        }

        if ($data['status'] !== $previousStatus) {
            $this->notifications->supportTicketEvent(
                $ticket,
                'status',
                'Platform set status to '.$data['status']
            );
        }

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

        $this->notifications->supportTicketEvent(
            $ticket,
            'replied',
            'Platform support replied'
        );

        return back()->with('success', 'Reply sent to the ISP.');
    }
}
