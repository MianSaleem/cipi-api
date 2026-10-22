<?php

namespace CipiApi\Http\Controllers;

use CipiApi\Services\CipiJobService;
use CipiApi\Services\CipiValidationService;
use CipiApi\Services\CipiYmlCliService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * cipi.yml of an app (Cipi CLI ≥ 5.6.4 + API sudoers ≥ 5.6.4): the plan, applying
 * it, and the automatic apply after every successful deploy.
 */
class YmlController extends Controller
{
    public function __construct(
        protected CipiYmlCliService $yml,
        protected CipiJobService $jobs,
        protected CipiValidationService $validator,
    ) {}

    public function show(string $name): JsonResponse
    {
        if (! $this->validator->appExists($name)) {
            return response()->json(['error' => "App '{$name}' not found"], 404);
        }

        try {
            return response()->json(['data' => $this->yml->plan($name)], 200);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }

    public function apply(string $name): JsonResponse
    {
        if (! $this->validator->appExists($name)) {
            return response()->json(['error' => "App '{$name}' not found"], 404);
        }

        $command = 'yml apply ' . escapeshellarg($name) . ' --yes';
        $job = $this->jobs->dispatch('app-yml-apply', $command, ['app' => $name]);

        return response()->json(['job_id' => $job->id, 'status' => 'pending'], 202);
    }

    public function autoEnable(string $name): JsonResponse
    {
        return $this->setAuto($name, true);
    }

    public function autoDisable(string $name): JsonResponse
    {
        return $this->setAuto($name, false);
    }

    protected function setAuto(string $name, bool $enabled): JsonResponse
    {
        if (! $this->validator->appExists($name)) {
            return response()->json(['error' => "App '{$name}' not found"], 404);
        }

        try {
            return response()->json(['data' => $this->yml->setAuto($name, $enabled)], 200);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }
}
