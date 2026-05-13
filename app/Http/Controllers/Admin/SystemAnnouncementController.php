<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemAnnouncement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SystemAnnouncementController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);

        $announcements = SystemAnnouncement::query()
            ->with('createdBy')
            ->latest()
            ->paginate(20);

        return view('admin.announcements.index', compact('announcements'));
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
