<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\InteractsWithListings;
use App\Http\Controllers\Controller;
use App\Models\SystemAnnouncement;
use App\Support\Export\CsvExporter;
use App\Support\Search\SearchHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemAnnouncementController extends Controller
{
    use InteractsWithListings;

    public function index(Request $request): View
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $query = $this->filteredAnnouncementsQuery($request);
        $perPage = $this->resolvePerPage($request, 20);
        $announcements = $query->paginate($perPage)->withQueryString();

        $filterChips = $this->listingFilterChips($request, route('admin.announcements.index'), [
            'search' => 'Search',
            'severity' => [
                'label' => 'Severity',
                'values' => [
                    'info' => 'Info',
                    'warning' => 'Warning',
                    'critical' => 'Critical',
                ],
            ],
            'is_active' => [
                'label' => 'Active',
                'values' => ['1' => 'Yes', '0' => 'No'],
            ],
        ]);

        $exportFormats = $this->listingExportFormats($request, route('admin.announcements.export.csv'));

        return view('admin.announcements.index', compact('announcements', 'filterChips', 'exportFormats'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $query = $this->filteredAnnouncementsQuery($request);

        return CsvExporter::download(
            'announcements-'.now()->format('Ymd-His').'.csv',
            ['ID', 'Title', 'Severity', 'Active', 'Starts', 'Ends', 'Created By', 'Created'],
            function ($out) use ($query) {
                $query->chunk(200, function ($rows) use ($out) {
                    foreach ($rows as $row) {
                        fputcsv($out, [
                            $row->id,
                            $row->title,
                            $row->severity,
                            $row->is_active ? 'Yes' : 'No',
                            $row->starts_at?->format('Y-m-d H:i:s'),
                            $row->ends_at?->format('Y-m-d H:i:s'),
                            $row->createdBy?->name,
                            $row->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });
            },
        );
    }

    protected function filteredAnnouncementsQuery(Request $request)
    {
        $query = SystemAnnouncement::query()->with('createdBy')->latest();

        $pattern = SearchHelper::likePattern($request->search);

        if ($pattern !== null) {
            $query->where(function ($q) use ($pattern) {
                $q->where('title', 'like', $pattern)
                    ->orWhere('body', 'like', $pattern);
            });
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active') === '1');
        }

        return $query;
    }

    public function create(): View
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        return view('admin.announcements.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $data = $this->validated($request);
        $data['created_by_user_id'] = Auth::id();

        SystemAnnouncement::create($data);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement created.');
    }

    public function edit(SystemAnnouncement $announcement): View
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, SystemAnnouncement $announcement): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $announcement->update($this->validated($request));

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement updated.');
    }

    public function destroy(SystemAnnouncement $announcement): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $announcement->delete();

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'severity' => ['required', 'in:info,warning,critical'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['starts_at'] = ! empty($data['starts_at']) ? $data['starts_at'] : null;
        $data['ends_at'] = ! empty($data['ends_at']) ? $data['ends_at'] : null;

        return $data;
    }
}
