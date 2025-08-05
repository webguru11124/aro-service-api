<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Factories;

use App\Domain\Calendar\Entities\Employee;
use App\Domain\Contracts\Queries\Office\OfficeEmployeeQuery;
use App\Domain\Contracts\Queries\PlansQuery;
use App\Domain\Contracts\Repositories\RescheduledPendingServiceRepository;
use App\Domain\Contracts\Repositories\PendingServiceRepository;
use App\Domain\Contracts\Repositories\ScheduledRouteRepository;
use App\Domain\Contracts\Repositories\SchedulingStateRepository;
use App\Domain\Scheduling\Entities\Plan;
use App\Domain\Scheduling\Entities\SchedulingState;
use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\NoRegularRoutesFoundException;
use App\Infrastructure\Exceptions\NoServiceProFoundException;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SchedulingStateFactory
{
    private CarbonInterface $date;
    private Office $office;

    private SchedulingState $schedulingState;
    /** @var Collection<Plan> */
    private Collection $plans;

    public function __construct(
        private PlansQuery $plansQuery,
        private SchedulingStateRepository $schedulingStateRepository,
        private PendingServiceRepository $pendingServiceRepository,
        private ScheduledRouteRepository $scheduledRouteRepository,
        private RescheduledPendingServiceRepository $rescheduledPendingServiceRepository,
        private OfficeEmployeeQuery $officeEmployeeQuery,
    ) {
    }

    /**
     * Creates scheduling state for a given date and office
     *
     * @param CarbonInterface $date
     * @param Office $office
     *
     * @return SchedulingState
     * @throws NoRegularRoutesFoundException
     * @throws NoServiceProFoundException
     */
    public function create(CarbonInterface $date, Office $office): SchedulingState
    {
        $this->date = $date;
        $this->office = $office;

        $this->buildSchedulingState();

        $this->resolvePlans();
        $this->resolveScheduledRoutes();
        $this->resolvePendingServices();
        $this->resolveActiveEmployeeIds();

        return $this->schedulingState;
    }

    private function buildSchedulingState(): void
    {
        $this->schedulingState = new SchedulingState(
            $this->schedulingStateRepository->getNextId(),
            $this->date,
            $this->office
        );
    }

    private function resolvePlans(): void
    {
        $this->plans = $this->plansQuery->get();
    }

    private function resolvePendingServices(): void
    {
        foreach ($this->plans as $plan) {
            $this->schedulingState->addPendingServices($this->pendingServiceRepository->findByOfficeIdAndDate(
                $this->office,
                $this->date,
                $plan
            ));
        }

        try {
            $this->schedulingState->addPendingServices($this->rescheduledPendingServiceRepository->findByOfficeIdAndDate(
                $this->office,
                $this->date
            ));
        } catch (NoRegularRoutesFoundException $e) {
        } catch (NoServiceProFoundException $e) {
        }
    }

    /**
     * @throws NoRegularRoutesFoundException
     * @throws NoServiceProFoundException
     */
    private function resolveScheduledRoutes(): void
    {
        $this->schedulingState->addScheduledRoutes($this->scheduledRouteRepository->findByOfficeIdAndDate(
            $this->office,
            $this->date,
        ));
    }

    private function resolveActiveEmployeeIds(): void
    {
        $activeEmployeeIds = $this->officeEmployeeQuery->find($this->office->getId())
            ->map(fn (Employee $employee) => $employee->getId())
            ->toArray();
        $this->schedulingState->setAllActiveEmployeeIds($activeEmployeeIds);
    }
}
