<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeploySyncController extends Controller
{
    public function syncPublic(Request $request): Response
    {
        require_once public_path('deploy-sync-lib.php');

        $expected = deploy_extract_expected_token(base_path());

        if ($expected === '') {
            return $this->plainResponse(
                "DEPLOY_EXTRACT_TOKEN is not configured in .env.\n",
                403
            );
        }

        abort_unless($request->isMethod('post'), 405);
        $provided = (string) $request->bearerToken();

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return $this->plainResponse("Forbidden.\n", 403);
        }

        $result = deploy_sync_public_tree(base_path());

        if (is_string($result)) {
            return $this->plainResponse($result."\n", 500);
        }

        if ($result['css_count'] < 1 && $result['copied'] !== []) {
            return $this->plainResponse(
                deploy_sync_public_report($result)."\nWarning: no CSS files in build/assets.\n",
                500
            );
        }

        return $this->plainResponse(deploy_sync_public_report($result));
    }

    private function plainResponse(string $body, int $status = 200): Response
    {
        return response($body, $status, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
