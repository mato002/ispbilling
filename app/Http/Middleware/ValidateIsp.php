<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ISP;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class ValidateIsp
{
    /**
     * Validate that ISP exists and is active
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ispId = $request->input('isp_id') ?? $request->route('isp_id');

        if (!$ispId) {
            Log::warning('ValidateIsp middleware: ISP ID missing', [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);
            abort(400, 'ISP ID is required.');
        }

        $isp = ISP::find($ispId);

        if (!$isp) {
            Log::warning('ValidateIsp middleware: ISP not found', [
                'isp_id' => $ispId,
                'url' => $request->fullUrl(),
            ]);
            abort(404, 'ISP not found.');
        }

        if (!$isp->is_active) {
            Log::warning('ValidateIsp middleware: ISP is inactive', [
                'isp_id' => $ispId,
                'url' => $request->fullUrl(),
            ]);
            abort(403, 'ISP is inactive.');
        }

        // Attach ISP to request for use in controller
        $request->merge(['validated_isp' => $isp]);

        return $next($request);
    }
}





