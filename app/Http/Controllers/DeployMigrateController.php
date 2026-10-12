<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeployMigrateController extends Controller
{
    public function migrate(Request $request): Response
    {
        require_once public_path('deploy-migrate-lib.php');

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

        $result = deploy_run_migrations(base_path());

        if (is_string($result)) {
            return $this->plainResponse($result."\n", 500);
        }

        if ($result['exit_code'] !== 0) {
            return $this->plainResponse(deploy_migrate_report($result), 500);
        }

        return $this->plainResponse(deploy_migrate_report($result));
    }

    private function plainResponse(string $body, int $status = 200): Response
    {
        return response($body, $status, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
