<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\Contracts\FeatureFlagService;
use App\Domain\Contracts\Queries\CustomerPropertyDetailsQuery;
use App\Domain\Contracts\Queries\HistoricalAppointmentsQuery;
use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\Customer;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Services\AverageDurationService;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Illuminate\Support\Collection;

class SetServiceDurationWithPredictiveModel extends AbstractGeneralOptimizationRule
{
    private const PREDICTIVE_SERVICE_DURATION_FEATURE_FLAG = 'isPredictiveServiceDurationEnabled';

    /** @var int[] */
    private array $customerIds = [];
    private OptimizationState $optimizationState;
    private Collection $customersPropertyDetails;
    private Collection $historicalAppointments;

    public function __construct(
        private readonly AverageDurationService $averageDurationService,
        private CustomerPropertyDetailsQuery $customerPropertyDetailsQuery,
        private HistoricalAppointmentsQuery $historicalAppointmentsQuery,
        private FeatureFlagService $featureFlagService,
    ) {
    }

    /**
     * Sets the service duration to the average duration of the service for the customer using predictive model
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        $this->optimizationState = $optimizationState;

        if (!$this->isPredictiveServiceDurationEnabled()) {
            return $this->buildTriggeredExecutionResult();
        }

        $this->preparePredictiveModelData();
        $this->setDurationUsingPredictiveModel();

        return $this->buildSuccessExecutionResult();
    }

    private function isPredictiveServiceDurationEnabled(): bool
    {
        return $this->featureFlagService->isFeatureEnabledForOffice(
            $this->optimizationState->getOffice()->getId(),
            self::PREDICTIVE_SERVICE_DURATION_FEATURE_FLAG,
        );
    }

    private function preparePredictiveModelData(): void
    {
        $this->customersPropertyDetails = $this->customerPropertyDetailsQuery->get($this->customerIds)->keyBy(fn (Customer $customer) => $customer->getId());
        $this->historicalAppointments = $this->historicalAppointmentsQuery->find($this->customerIds, $this->optimizationState->getOffice()->getId());
    }

    private function setDurationUsingPredictiveModel(): void
    {
        foreach ($this->optimizationState->getAssignedAppointments() as $appointment) {
            $customerId = $appointment->getCustomerId();
            $customerPropertyDetails = $this->customersPropertyDetails->get($customerId);

            if ($customerPropertyDetails) {
                $this->calculateAndSetAppointmentDurations($appointment);
            } else {
                $appointment->setDuration($this->getAverageServiceDuration($appointment));
            }
        }
    }

    private function calculateAndSetAppointmentDurations(Appointment $appointment): void
    {
        $customerId = $appointment->getCustomerId();
        $customerPropertyDetails = $this->customersPropertyDetails->get($customerId);

        if ($customerPropertyDetails) {
            $appointmentRouteId = $appointment->getRouteId();
            $historicalAppointmentsForCustomer = $this->historicalAppointments->get($customerId);
            $serviceProId = $this->optimizationState->getRoutes()->first(fn ($route) => $route->getId() === $appointmentRouteId)?->getServicePro()->getId();

            if ($historicalAppointmentsForCustomer) {
                $historicalAppointmentAverageDuration = $historicalAppointmentsForCustomer
                    ->where('servicedBy', $serviceProId)
                    ->avg('duration');
            }

            $appointment->resolveServiceDuration(
                $customerPropertyDetails->getPropertyDetails(),
                $historicalAppointmentAverageDuration ?? null,
                $this->optimizationState->getWeatherInfo(),
            );
        }
    }

    private function getAverageServiceDuration(Appointment $appointment): Duration
    {
        if ($appointment->isInitial()) {
            return Duration::fromMinutes(DomainContext::getInitialAppointmentDuration());
        }

        $averageDuration = $this->averageDurationService->getAverageServiceDuration(
            $appointment->getOfficeId(),
            $appointment->getCustomerId(),
            $appointment->getExpectedArrival()->getStartAt()->quarter
        );

        if ($averageDuration === null) {
            return Duration::fromMinutes(DomainContext::getRegularAppointmentDuration());
        }

        return $averageDuration;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'Set Service Duration With Predictive Model';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule sets the service duration to the average duration of the service for the customer by using predictive model.';
    }
}
