<?php

namespace App\Http\Controllers\ISP\Web;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\NotificationService;
use App\Support\Export\CsvExporter;
use App\Support\Search\SearchHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HelpdeskController extends Controller
{
    use InteractsWithListings;

    public function __construct(protected NotificationService $notifications) {}

    public function index(Request $request): View
    {
        $isp = Auth::user()->isp;

        $query = $this->filteredHelpdeskQuery($request, $isp->id);
        $perPage = $this->resolvePerPage($request);
        $tickets = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('isp.helpdesk.index'), [
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

        $exportFormats = $this->listingExportFormats($request, route('isp.helpdesk.export.csv'));

        return view('isp.helpdesk.index', compact('tickets', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $isp = Auth::user()->isp;
        $query = $this->filteredHelpdeskQuery($request, $isp->id);
        $filename = 'helpdesk-tickets-'.now()->format('Ymd-His').'.csv';

        return CsvExporter::download($filename, [
            'ID', 'Subject', 'Status', 'Priority', 'Created by', 'Assigned to', 'Created', 'Updated',
        ], function ($out) use ($query) {
            $query->chunk(200, function ($tickets) use ($out) {
                foreach ($tickets as $ticket) {
                    fputcsv($out, [
                        $ticket->id,
                        $ticket->subject,
                        $ticket->status,
                        $ticket->priority,
                        $ticket->createdBy?->name,
                        $ticket->assignedTo?->name,
                        $ticket->created_at?->format('Y-m-d H:i:s'),
                        $ticket->updated_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
        });
    }

    protected function filteredHelpdeskQuery(Request $request, int $ispId): Builder
    {
        $query = Ticket::with(['createdBy', 'assignedTo'])
            ->where('isp_id', $ispId)
            ->platformTickets()
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

        $this->notifications->supportTicketEvent($ticket, 'created', 'Opened by '.$user->name.' ('.$isp->name.')');

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

        $this->notifications->supportTicketEvent(
            $ticket,
            'status',
            'Status set to '.$data['status'].' by '.Auth::user()->name
        );

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

        $this->notifications->supportTicketEvent(
            $ticket,
            'replied',
            'ISP reply from '.Auth::user()->name
        );

        return back()->with('success', 'Reply added successfully.');
    }

    protected function authorizePlatformTicket(Ticket $ticket): void
    {
        $isp = Auth::user()->isp;

        abort_unless($isp && $ticket->isp_id === $isp->id && $ticket->isPlatformTicket(), 404);
    }
}
