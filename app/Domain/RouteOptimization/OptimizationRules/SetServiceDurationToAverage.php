<?php

declare(strict_types=1);

namespace App\Domain\RouteOptimization\OptimizationRules;

use App\Domain\RouteOptimization\DomainContext;
use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Services\AverageDurationService;
use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use App\Domain\SharedKernel\ValueObjects\Duration;
use Illuminate\Support\Collection;

class SetServiceDurationToAverage extends AbstractGeneralOptimizationRule
{
    /** @var int[] */
    private array $customerIds = [];
    private OptimizationState $optimizationState;

    public function __construct(
        private readonly AverageDurationService $averageDurationService,
    ) {
    }

    /**
     * Sets the service duration to the average duration of the service for the customer
     *
     * @param OptimizationState $optimizationState
     *
     * @return RuleExecutionResult
     */
    public function process(OptimizationState $optimizationState): RuleExecutionResult
    {
        $this->optimizationState = $optimizationState;
        $this->averageDurationService->preload(
            $optimizationState->getOffice()->getId(),
            $optimizationState->getOptimizationTimeFrame()->getStartAt()->quarter,
            ...$this->getCustomerIds($optimizationState->getRoutes())
        );
        $this->setDurationUsingAverage();

        return $this->buildSuccessExecutionResult();
    }

    /**
     * @param Collection $routes
     *
     * @return array<int>
     */
    private function getCustomerIds(Collection $routes): array
    {
        foreach ($routes as $route) {
            $this->customerIds = array_merge(
                $this->customerIds,
                $route->getAppointments()
                    ->map(fn (Appointment $appointment) => $appointment->getCustomerId())
                    ->all()
            );
        }

        return $this->customerIds;
    }

    private function setDurationUsingAverage(): void
    {
        foreach ($this->optimizationState->getAssignedAppointments() as $appointment) {
            $appointment->setDuration($this->getAverageServiceDuration($appointment));
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
        return 'Set Service Duration To Average';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'This rule sets the service duration to the average duration of the service for the customer.';
    }
}
