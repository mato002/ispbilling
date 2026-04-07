<?php

namespace App\Http\Controllers\ISP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class BaseController extends Controller
{
    /**
     * Get the current user's ISP ID
     */
    protected function getIspId(): ?int
    {
        return auth()->user()?->isp_id;
    }

    /**
     * Ensure the resource belongs to the current ISP
     */
    protected function ensureIspAccess($model, ?int $ispId = null): void
    {
        $ispId = $ispId ?? $this->getIspId();
        
        if (!$ispId) {
            abort(403, 'No ISP associated with your account');
        }

        if (isset($model->isp_id) && $model->isp_id !== $ispId) {
            abort(403, 'You do not have access to this resource');
        }
    }

    /**
     * Scope query to current ISP
     */
    protected function scopeToIsp($query, ?int $ispId = null)
    {
        $ispId = $ispId ?? $this->getIspId();
        
        if (!$ispId) {
            abort(403, 'No ISP associated with your account');
        }

        return $query->where('isp_id', $ispId);
    }
}





