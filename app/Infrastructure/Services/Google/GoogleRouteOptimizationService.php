<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Google;

use App\Domain\Contracts\OptimizationRule;
use App\Domain\Contracts\Services\RouteOptimizationService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\Exceptions\WorkEventNotFoundAfterOptimizationException;
use App\Infrastructure\Services\Google\DataTranslators\DomainToGoogleTranslator;
use App\Infrastructure\Services\Google\DataTranslators\GoogleToDomainTranslator;
use App\Infrastructure\Services\Google\Exceptions\OptimizationFailedException;
use Google\ApiCore\ApiException;
use Google\Cloud\Optimization\V1\Client\FleetRoutingClient;
use Google\Cloud\Optimization\V1\OptimizeToursRequest;
use Google\Cloud\Optimization\V1\OptimizeToursResponse;
use Google\Cloud\Optimization\V1\ShipmentModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GoogleRouteOptimizationService implements RouteOptimizationService
{
    private const MESSAGE_REQUEST = OptimizationEngine::GOOGLE->value . ' - Request';
    private const MESSAGE_RESPONSE = OptimizationEngine::GOOGLE->value . ' - Response';

    private string $parent;

    public function __construct(
        private readonly DomainToGoogleTranslator $domainToGoogleTranslator,
        private readonly GoogleToDomainTranslator $googleToDomainTranslator,
        private readonly FleetRoutingClient $client,
    ) {
        $this->parent = 'projects/' . config('googleapis.auth.project_id');
    }

    /**
     * @param OptimizationState $sourceState
     * @param Collection<OptimizationRule> $rules
     *
     * @return OptimizationState
     * @throws OptimizationFailedException
     * @throws WorkEventNotFoundAfterOptimizationException
     */
    public function optimize(OptimizationState $sourceState, Collection $rules): OptimizationState
    {
        $request = $this->buildOptimizationToursRequest($sourceState);

        try {
            $response = $this->getOptimizationResponse($request);

            return $this->googleToDomainTranslator->translate(
                $request->getModel(),
                $response,
                $sourceState,
                OptimizationStatus::POST,
            );
        } catch (ApiException $ex) {
            throw new OptimizationFailedException($ex->getMessage());
        }
    }

    /**
     * @param OptimizationState $sourceData
     *
     * @return OptimizeToursRequest
     */
    private function buildOptimizationToursRequest(OptimizationState $sourceData): OptimizeToursRequest
    {
        $optimizeToursRequest = $this->getOptimizationRequest(
            $this->domainToGoogleTranslator->translate($sourceData)
        );

        $optimizeToursRequest->setConsiderRoadTraffic($sourceData->isTrafficConsiderationEnabled());

        return $optimizeToursRequest;
    }

    public function plan(OptimizationState $sourceData): OptimizationState
    {
        // TODO: Implement plan() method.
        return $sourceData;
    }

    /**
     * Returns optimization engine identifier
     *
     * @return OptimizationEngine
     */
    public function getIdentifier(): OptimizationEngine
    {
        return OptimizationEngine::GOOGLE;
    }

    public function optimizeSingleRoute(Route $route): Route
    {
        $request = $this->getOptimizationRequest(
            $this->domainToGoogleTranslator->translateSingleRoute($route)
        );

        try {
            $response = $this->getOptimizationResponse($request);

            return $this->googleToDomainTranslator->translateSingleRoute($response, $route);
        } catch (ApiException $ex) {
            throw new OptimizationFailedException($ex->getMessage());
        }
    }

    private function getOptimizationRequest(ShipmentModel $shipmentModel): OptimizeToursRequest
    {
        $request = (new OptimizeToursRequest())
            ->setModel($shipmentModel)
            ->setParent($this->parent);

        Log::info(self::MESSAGE_REQUEST, json_decode($request->serializeToJsonString(), true));

        return $request;
    }

    /**
     * @throws ApiException
     */
    private function getOptimizationResponse(OptimizeToursRequest $request): OptimizeToursResponse
    {
        $response = $this->client->optimizeTours($request);

        Log::info(self::MESSAGE_RESPONSE, json_decode($response->serializeToJsonString(), true));

        return $response;
    }
}
