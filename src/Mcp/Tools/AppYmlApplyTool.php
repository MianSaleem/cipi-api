<?php

namespace CipiApi\Mcp\Tools;

use CipiApi\Mcp\Support\McpArgValidator;
use CipiApi\Services\CipiJobService;
use CipiApi\Services\CipiValidationService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Apply an app\'s cipi.yml to the server now (cipi yml apply --yes). Check AppYmlShow first: nothing is applied while blockers are listed. Async: returns job_id; JobShow result has outcome (applied, unchanged, partial, blocked, invalid, no-file), applied, failed, failures, blockers, errors. Requires Cipi 5.6.4+.')]
class AppYmlApplyTool extends Tool
{
    public function __construct(
        protected CipiJobService $jobs,
        protected CipiValidationService $validator,
    ) {}

    public function handle(Request $request): Response
    {
        [$name, $error] = McpArgValidator::requiredString($request, 'name');
        if ($error !== null) {
            return $error;
        }

        if (! $this->validator->appExists($name)) {
            return Response::text("Error: App '{$name}' not found");
        }

        $command = 'yml apply ' . escapeshellarg($name) . ' --yes';
        $job = $this->jobs->dispatch('app-yml-apply', $command, ['app' => $name]);

        return Response::text("Job dispatched: {$job->id} (status: pending). Poll JobShow with id {$job->id} for result.");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('App name')->required(),
        ];
    }
}
