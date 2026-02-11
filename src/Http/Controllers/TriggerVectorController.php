<?php

namespace Iquesters\Dev\Http\Controllers;

use Illuminate\Routing\Controller;
use Iquesters\Integration\Models\Integration;
use Iquesters\Integration\Jobs\SyncVectorJob;
use Iquesters\Dev\Constants\Constants;
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

            $integrations = Integration::query()
                ->whereHas('supportedIntegration', function ($query) {
                    $query->where('name', Constants::WOOCOMMERCE);
                })
                ->with([
                    'supportedIntegration',
                    'organisations',
                    'creator',
                    'metas'
                ])
                ->where('status', 'active')
                ->get();

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

            $integration = Integration::where('uid', $uid)
                ->with(['metas', 'supportedIntegration'])
                ->firstOrFail();

            // Validate integration type
            if (
                !$integration->supportedIntegration ||
                $integration->supportedIntegration->name !== Constants::WOOCOMMERCE
            ) {
                $this->logWarning("Invalid integration type for UID: {$uid}");
                $this->logMethodEnd('INVALID TYPE');

                return back()->with('error', 'Invalid integration type.');
            }

            // Build payload from stored meta
            $payload = [
                'integration_id'       => $integration->id,
                'integration_uid'      => $integration->uid,
                'url'                  => $integration->getMeta('website_url'),
                'consumer_key'         => $integration->getMeta('consumer_key'),
                'consumer_secret'      => $integration->getMeta('consumer_secret'),
                'integration_provider' => $integration->supportedIntegration->name,
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
}