<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SettingController extends Controller
{
    public function index()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $settings = [
            'app_name' => PlatformSetting::get('app_name', config('app.name')),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => PlatformSetting::get('app_url', config('app.url')),
            'timezone' => PlatformSetting::get('app_timezone', config('app.timezone')),
            'locale' => PlatformSetting::get('app_locale', config('app.locale')),
        ];

        $maintenanceEnabled = app()->isDownForMaintenance();
        return view('admin.settings.index', compact('settings', 'maintenanceEnabled'));
    }

    public function update(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url|max:255',
            'timezone' => 'required|timezone',
            'locale' => 'required|string|in:en,sw',
        ]);
        foreach (['app_name', 'app_url'] as $key) PlatformSetting::put($key, $validated[$key]);
        PlatformSetting::put('app_timezone', $validated['timezone']);
        PlatformSetting::put('app_locale', $validated['locale']);
        Config::set('app.name', $validated['app_name']);
        Config::set('app.url', $validated['app_url']);
        Config::set('app.timezone', $validated['timezone']);
        Config::set('app.locale', $validated['locale']);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings saved successfully.');
    }

    public function clearCache()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return redirect()->route('admin.settings.index')
                ->with('success', 'Cache cleared successfully!');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    public function optimize()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access');
        }

        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            return redirect()->route('admin.settings.index')
                ->with('success', 'Application optimized successfully!');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Failed to optimize: ' . $e->getMessage());
        }
    }

    public function toggleMaintenance()
    {
        if (! auth()->user()->isSuperAdmin()) abort(403);
        try {
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');
                $message = 'Maintenance mode disabled.';
            } else {
                $secret = bin2hex(random_bytes(24));
                Artisan::call('down', ['--secret' => $secret, '--render' => 'errors::503']);
                return response()->view('admin.settings.maintenance-enabled', [
                    'bypassUrl' => url('/'.$secret),
                ]);
            }
            return redirect()->route('admin.settings.index')->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->route('admin.settings.index')->with('error', 'Could not change maintenance mode: '.$e->getMessage());
        }
    }

    public function backup(): BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        if (! auth()->user()->isSuperAdmin()) abort(403);
        $stream = null;
        $plainPath = null;
        $encryptedPath = null;
        try {
            Storage::disk('local')->makeDirectory('backups');
            $stamp = now()->format('Ymd-His');
            $plainPath = 'backups/.database-'.$stamp.'-'.bin2hex(random_bytes(4)).'.tmp';
            $encryptedPath = 'backups/database-'.$stamp.'-'.bin2hex(random_bytes(4)).'.json.enc';
            $stream = fopen(Storage::disk('local')->path($plainPath), 'wb');
            if (! $stream) throw new \RuntimeException('Could not create the backup file.');
            @chmod(Storage::disk('local')->path($plainPath), 0600);
            fwrite($stream, '{"created_at":'.json_encode(now()->toIso8601String(), JSON_THROW_ON_ERROR)
                .',"connection":'.json_encode(DB::connection()->getName(), JSON_THROW_ON_ERROR).',"tables":{');
            $firstTable = true;
            foreach (DB::connection()->getSchemaBuilder()->getTableListing() as $table) {
                if (! is_string($table)) continue;
                if (! $firstTable) fwrite($stream, ',');
                $firstTable = false;
                fwrite($stream, json_encode($table, JSON_THROW_ON_ERROR).':[');
                $first = true;
                foreach (DB::table($table)->cursor() as $row) {
                    if (! $first) fwrite($stream, ',');
                    fwrite($stream, json_encode($row, JSON_THROW_ON_ERROR));
                    $first = false;
                }
                fwrite($stream, ']');
            }
            fwrite($stream, '}}');
            fclose($stream);
            $stream = null;
            $encrypted = Crypt::encryptString(file_get_contents(Storage::disk('local')->path($plainPath)));
            Storage::disk('local')->put($encryptedPath, $encrypted);
            Storage::disk('local')->delete($plainPath);
            $cutoff = now()->subDays(7)->timestamp;
            foreach (Storage::disk('local')->files('backups') as $oldBackup) {
                if (str_ends_with($oldBackup, '.json.enc') && Storage::disk('local')->lastModified($oldBackup) < $cutoff) {
                    Storage::disk('local')->delete($oldBackup);
                }
            }
            return response()->download(Storage::disk('local')->path($encryptedPath), basename($encryptedPath))->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            if (is_resource($stream)) fclose($stream);
            foreach ([$plainPath, $encryptedPath] as $partialPath) {
                if ($partialPath && Storage::disk('local')->exists($partialPath)) Storage::disk('local')->delete($partialPath);
            }
            return redirect()->route('admin.settings.index')->with('error', 'Database backup failed: '.$e->getMessage());
        }
    }
}
