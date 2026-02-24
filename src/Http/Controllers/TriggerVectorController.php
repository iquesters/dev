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
                ->orderByDesc('id')
                ->get();

            $integrationNames = Integration::query()
                ->whereIn('id', $vectorResponses->pluck('integration_id')->unique()->filter()->values())
                ->pluck('name', 'id');

            $this->logInfo('Vector responses loaded: ' . $vectorResponses->count());
            $this->logMethodEnd();

            return view('dev::vector-responses.index', compact('vectorResponses', 'integrationNames'));
        } catch (\Throwable $e) {
            $this->logError('Vector responses list failed: ' . $e->getMessage());
            $this->logMethodEnd('FAILED');

            return back()->with('error', $e->getMessage());
        }
    }
}
