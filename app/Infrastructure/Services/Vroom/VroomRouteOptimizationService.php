<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom;

use App\Application\Events\Vroom\VroomRequestFailed;
use App\Application\Events\Vroom\VroomRequestSent;
use App\Application\Events\Vroom\VroomResponseReceived;
use App\Domain\Contracts\Services\RouteOptimizationService;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Enums\OptimizationEngine;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Infrastructure\Exceptions\VroomErrorResponseException;
use App\Infrastructure\Services\Vroom\DataTranslators\DomainToVroomPlanModeTranslator;
use App\Infrastructure\Services\Vroom\DataTranslators\DomainToVroomTranslator;
use App\Infrastructure\Services\Vroom\DataTranslators\VroomToDomainTranslator;
use App\Infrastructure\Services\Vroom\DTO\VroomInputData;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

readonly class VroomRouteOptimizationService implements RouteOptimizationService
{
    private const HTTP_RESPONSE_TIMEOUT_SECONDS = 120;
    private const HTTP_CONNECT_TIMEOUT_SECONDS = 10;

    private string $vroomHost;
    private int $millisecondsToWaitBetweenRetries;
    private int $retries;

    public function __construct(
        private VroomToDomainTranslator $vroomToDomainTranslator,
        private DomainToVroomTranslator $domainToVroomTranslator,
        private DomainToVroomPlanModeTranslator $domainToVroomPlanModeTranslator,
        private VroomBusinessRulesCastService $ruleCastService
    ) {
        $this->vroomHost = config('vroom.connection.url');
        $this->retries = (int) config('vroom.connection.retries');
        $this->millisecondsToWaitBetweenRetries = (int) config('vroom.connection.milliseconds_to_wait_between_retries');
    }

    /**
     * Optimizes a given array of routes and returns the optimized routes
     *
     * @param OptimizationState $sourceState
     * @param Collection $rules
     *
     * @return OptimizationState
     * @throws VroomErrorResponseException
     */
    public function optimize(OptimizationState $sourceState, Collection $rules): OptimizationState
    {
        $vroomInputData = $this->ruleCastService->castRules(
            $this->domainToVroomTranslator->translate($sourceState),
            $sourceState,
            $rules
        );

        $response = $this->sendRequest($vroomInputData, $sourceState->getDate(), $sourceState->getOffice()->getId());

        return $this->vroomToDomainTranslator->translate(
            json_decode($response->body(), true),
            $sourceState,
            OptimizationStatus::POST
        );
    }

    /**
     * Processes OptimizationState and returns the planned routes with drive distance and time
     *
     * @param OptimizationState $sourceData
     *
     * @return OptimizationState
     * @throws VroomErrorResponseException
     */
    public function plan(OptimizationState $sourceData): OptimizationState
    {
        $vroomInputData = $this->domainToVroomPlanModeTranslator->translate($sourceData);

        $response = $this->sendRequest($vroomInputData, $sourceData->getDate(), $sourceData->getOffice()->getId());

        return $this->vroomToDomainTranslator->translate(
            json_decode($response->body(), true),
            $sourceData,
            OptimizationStatus::PLAN
        );
    }

    /**
     * @param Route $route
     *
     * @return Route
     * @throws VroomErrorResponseException
     */
    public function optimizeSingleRoute(Route $route): Route
    {
        $vroomInputData = $this->domainToVroomTranslator->translateSingleRoute($route);

        $response = $this->sendRequest(
            $vroomInputData,
            $route->getTimeWindow()->getStartAt(),
            $route->getOfficeId()
        );
        $responseData = json_decode($response->body(), true);

        return $this->vroomToDomainTranslator->translateSingleRoute($responseData, $route);
    }

    private function sendRequest(VroomInputData $vroomInputData, CarbonInterface $optimizationDate, int $officeId): Response
    {
        $requestId = uniqid();

        VroomRequestSent::dispatch($requestId, $this->vroomHost, $optimizationDate, $officeId, $vroomInputData);

        try {
            $response = Http::timeout(self::HTTP_RESPONSE_TIMEOUT_SECONDS)
                ->connectTimeout(self::HTTP_CONNECT_TIMEOUT_SECONDS)
                ->retry($this->retries, $this->millisecondsToWaitBetweenRetries)
                ->post($this->vroomHost, $vroomInputData->toArray());

            VroomResponseReceived::dispatch($requestId, $optimizationDate, $officeId, $response);

            $this->validateVroomResponse($response);
        } catch (Throwable $exception) {
            VroomRequestFailed::dispatch($requestId);

            throw new VroomErrorResponseException($exception->getMessage());
        }

        return $response;
    }

    private function validateVroomResponse(Response $response): void
    {
        if (!$response->successful()) {
            throw VroomErrorResponseException::requestUnsuccessful($response->status());
        }
    }

    /**
     * Returns optimization engine identifier
     *
     * @return OptimizationEngine
     */
    public function getIdentifier(): OptimizationEngine
    {
        return OptimizationEngine::VROOM;
    }
}
