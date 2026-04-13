<?php

namespace Iquesters\Dev\Http\Controllers;

use Illuminate\Routing\Controller;
use Iquesters\Integration\Models\Integration;
use Iquesters\Integration\Jobs\SyncVectorJob;
use Iquesters\Dev\Constants\Constants;
use Iquesters\Dev\Models\VectorResponse;
use Iquesters\Foundation\System\Traits\Loggable;

class TriggerVectorController extends Controller
{
    use Loggable;

    /**
     * Display all active WooCommerce integrations
     */
    public function index()
    {
        $this->logMethodStart('Loading WooCommerce integrations');

        try {
            // Load active WooCommerce integrations
            $integrations = Integration::query()
                ->whereHas('supportedIntegration', function ($query) {
                    $query->where('name', Constants::WOOCOMMERCE);
                })
                ->with([
                    'supportedIntegration',
                    'organisations',
                    'creator',
                    'metas',
                ])
                ->where('status', 'active')
                ->get();

            // Load latest successful vector response for each integration
            $integrationIds = $integrations->pluck('id')->toArray();

            $latestVectorResponses = \Iquesters\Dev\Models\VectorResponse::query()
                ->whereIn('integration_id', $integrationIds)
                ->where('status', 'active')
                ->select('integration_id', 'finished_at')
                ->orderBy('finished_at', 'desc')
                ->get()
                ->groupBy('integration_id')
                ->map(function ($responses) {
                    return $responses->first(); // take the latest finished_at
                });

            // Attach latest finished_at to each integration
            foreach ($integrations as $integration) {
                $integration->last_vector_sync = optional($latestVectorResponses[$integration->id] ?? null)->finished_at;
            }

            $this->logInfo('Integrations loaded: ' . $integrations->count());
            $this->logMethodEnd();

            return view('dev::trigger-vectors.index', compact('integrations'));

        } catch (\Throwable $e) {
            $this->logError('Index failed: ' . $e->getMessage());
            $this->logMethodEnd('FAILED');

            return back()->with('error', $e->getMessage());
        }
    }
    /**
     * Trigger SyncVectorJob manually
     */
    public function trigger(string $uid)
    {
        $this->logMethodStart("Trigger requested for UID: {$uid}");

        try {

            $supportedProviders = [
                Constants::WOOCOMMERCE,
                // add new ecommerce providers here later
            ];

            $integration = Integration::where('uid', $uid)
                ->with(['metas', 'supportedIntegration'])
                ->firstOrFail();

            $providerName = $integration->supportedIntegration->name ?? null;

            if (!$providerName || !in_array($providerName, $supportedProviders, true)) {
                $this->logWarning("Unsupported integration type for UID: {$uid}");
                $this->logMethodEnd('INVALID TYPE');
                return back()->with('error', 'Unsupported integration provider.');
            }

            // Check optional flags from modal checkboxes
            $forceCleanup = (bool) request()->input('force_cleanup', false);
            $recreateFlag = (bool) request()->input('recreate_flag', false);

            $this->logDebug("Force cleanup flag is: " . ($forceCleanup ? 'true' : 'false'));
            $this->logDebug("Recreate flag is: " . ($recreateFlag ? 'true' : 'false'));

            $payload = [
                'integration_id' => $integration->id,
                'force_cleanup'  => $forceCleanup,
                'systems' => [
                    [
                        'integration_provider' => $providerName,
                        'integration_uuid'     => $integration->uid,
                        'recreate_flag'        => $recreateFlag,
                    ]
                ],
            ];

            $this->logDebug('Dispatching SyncVectorJob', json_encode($payload));
            SyncVectorJob::dispatch($payload);

            $this->logInfo("Job dispatched for UID: {$uid}");
            $this->logMethodEnd();

            return back()->with(
                'success',
                'Vector sync job dispatched successfully for ' . $integration->name . '.'
            );

        } catch (\Throwable $e) {
            $this->logCritical("Trigger failed for UID {$uid}: " . $e->getMessage());
            $this->logMethodEnd('FAILED');

            return back()->with('error', 'Unable to trigger vector job.');
        }
    }

    /**
     * Display vector responses in descending order for UI datatable.
     */
    public function vectorResponses()
    {
        $this->logMethodStart('Loading vector responses for UI');

        try {
            $vectorResponses = VectorResponse::query()
                ->with('integration.supportedIntegration')
                ->orderByDesc('id')
                ->get();

            $this->logInfo('Vector responses loaded: ' . $vectorResponses->count());
            $this->logMethodEnd();

            $operations = $vectorResponses
                ->groupBy(fn (VectorResponse $row) => (string) ($row->operation_id ?: $row->id))
                ->map(function ($rows) {
                    $latest = $rows->sortByDesc('id')->first();
                    $first = $rows->sortBy('id')->first();

                    return [
                        'operation_id' => (int) ($latest->operation_id ?: $latest->id),
                        'latest' => $latest,
                        'first' => $first,
                        'rows_count' => $rows->count(),
                    ];
                })
                ->sortByDesc(fn (array $item) => $item['latest']->id)
                ->values();

            return view('dev::vector-responses.index', compact('operations'));
        } catch (\Throwable $e) {
            $this->logError('Vector responses list failed: ' . $e->getMessage());
            $this->logMethodEnd('FAILED');

            return back()->with('error', $e->getMessage());
        }
    }

    public function showVectorOperation(int $operationId)
    {
        $this->logMethodStart("Loading vector operation {$operationId}");

        try {
            $records = VectorResponse::query()
                ->with('integration.supportedIntegration')
                ->where('operation_id', $operationId)
                ->orderBy('id')
                ->get();

            if ($records->isEmpty()) {
                return redirect()
                    ->route('vectors.responses.index')
                    ->with('error', "Operation {$operationId} was not found.");
            }

            $latest = $records->last();
            $progress = $this->resolveProgressPercentage($latest, $records);

            $this->logMethodEnd();

            return view('dev::vector-responses.show', [
                'operationId' => $operationId,
                'records' => $records,
                'latest' => $latest,
                'progress' => $progress,
            ]);
        } catch (\Throwable $e) {
            $this->logError('Vector operation view failed: ' . $e->getMessage());
            $this->logMethodEnd('FAILED');

            return back()->with('error', $e->getMessage());
        }
    }

    private function resolveProgressPercentage(VectorResponse $latest, $records): int
    {
        try {
            $maxSteps = 6;
            $stepStatus = (int) ($latest->step_status ?? 0);
            $normalizedStatus = strtolower((string) $latest->status);
            $recordsCount = $records->count();
            $decodedResponse = $latest->decoded_response;
            $accepted = (bool) data_get($decodedResponse, 'response_body.accepted', false);
            $duplicateSuppressed = (bool) data_get($decodedResponse, 'response_body.duplicate_suppressed', false);
            $productsDone = data_get($decodedResponse, 'payload.products_done');
            $productsTotal = data_get($decodedResponse, 'payload.products_total');

            if ($stepStatus === -1 || $normalizedStatus === 'failed') {
                return 100;
            }

            if ($stepStatus === 3 || $normalizedStatus === 'processing') {
                $processingRows = $records->filter(function (VectorResponse $record) {
                    return (int) ($record->step_status ?? 0) === 3 || strtolower((string) $record->status) === 'processing';
                })->count();

                if ($processingRows > 0) {
                    $stepProgress = (int) round((min($processingRows, $maxSteps) / $maxSteps) * 100);

                    if (is_numeric($productsDone) && is_numeric($productsTotal) && (int) $productsTotal > 0) {
                        $productProgress = (int) round((((int) $productsDone / (int) $productsTotal) * 100));

                        return max($stepProgress, min(99, $productProgress));
                    }

                    return min(99, max(16, $stepProgress));
                }

                if (is_numeric($productsDone) && is_numeric($productsTotal) && (int) $productsTotal > 0) {
                    $ratio = ((int) $productsDone / (int) $productsTotal) * 100;

                    return max(5, min(99, (int) round($ratio)));
                }

                return 16;
            }

            if ($stepStatus === 0 && $duplicateSuppressed) {
                return 100;
            }

            if ($accepted && $recordsCount === 1) {
                return 16;
            }

            if (in_array($stepStatus, [0, 1, 2], true) || $normalizedStatus === 'completed') {
                return 100;
            }

            return min(95, max(15, $recordsCount * 20));
        } catch (\Throwable $e) {
            $this->logWarning('Progress percentage fallback used: ' . $e->getMessage());

            return 16;
        }
    }
}
