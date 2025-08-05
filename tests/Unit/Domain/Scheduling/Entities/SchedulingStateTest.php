<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\Entities;

use App\Domain\Scheduling\Entities\Customer;
use App\Domain\Scheduling\Entities\PendingService;
use App\Domain\Scheduling\Entities\ScheduledRoute;
use App\Domain\Scheduling\Entities\SchedulingState;
use App\Domain\Scheduling\Entities\ServicePoint;
use App\Domain\Scheduling\ValueObjects\CustomerPreferences;
use App\Domain\Scheduling\ValueObjects\ResignedTechAssignment;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\Scheduling\AppointmentFactory;
use Tests\Tools\Factories\Scheduling\ClusterOfServicesFactory;
use Tests\Tools\Factories\Scheduling\CustomerFactory;
use Tests\Tools\Factories\Scheduling\PendingServiceFactory;
use Tests\Tools\Factories\Scheduling\ScheduledRouteFactory;
use Tests\Tools\Factories\Scheduling\ServicePointFactory;
use Tests\Tools\Factories\ServiceProFactory;
use Tests\Tools\TestValue;

class SchedulingStateTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created(): void
    {
        $schedulingState = new SchedulingState(
            1,
            Carbon::today(),
            OfficeFactory::make(['id' => TestValue::OFFICE_ID])
        );
        $scheduledRoute = ScheduledRouteFactory::make([
            'appointments' => [],
            'pendingServices' => [],
        ]);
        $schedulingState->addScheduledRoutes(collect([$scheduledRoute]));
        $pendingService = PendingServiceFactory::make();
        $schedulingState->addPendingServices(collect([$pendingService]));

        $this->assertEquals(1, $schedulingState->getId());
        $this->assertEquals(Carbon::today()->toDateString(), $schedulingState->getDate()->toDateString());
        $this->assertEquals(TestValue::OFFICE_ID, $schedulingState->getOffice()->getId());
        $this->assertCount(1, $schedulingState->getScheduledRoutes());
        $this->assertCount(1, $schedulingState->getPendingServices());
    }

    /**
     * @test
     *
     * ::getTotalCapacity
     */
    public function it_returns_total_capacity(): void
    {
        $schedulingState = new SchedulingState(1, Carbon::today(), OfficeFactory::make());
        $scheduledRoute = ScheduledRouteFactory::make([
            'appointments' => [],
            'pendingServices' => [],
            'actualCapacityCount' => 22,
        ]);
        $schedulingState->addScheduledRoutes(collect([$scheduledRoute]));

        $this->assertEquals(16, $schedulingState->getTotalCapacity());
    }

    /**
     * @test
     *
     * ::getInitialClusters
     */
    public function it_returns_initial_clusters(): void
    {
        /** @var ScheduledRoute $scheduledRoute */
        $scheduledRoute = ScheduledRouteFactory::make([
            'appointments' => [],
            'pendingServices' => [],
        ]);

        $schedulingState = new SchedulingState(1, Carbon::today(), OfficeFactory::make());
        $schedulingState->addScheduledRoutes(collect([$scheduledRoute]));

        $clusters = $schedulingState->getInitialClusters();

        $this->assertCount(1, $clusters);
        $this->assertEquals($scheduledRoute->getId(), $clusters->first()->getId());
    }

    /**
     * @test
     *
     * ::getPendingServicePointsForScheduledDate
     */
    public function it_returns_pending_service_points_for_scheduled_date(): void
    {
        $specificDate = Carbon::today();
        $matchingDayOfWeek = $specificDate->dayOfWeek;
        $nonMatchingDayOfWeek = ($matchingDayOfWeek + 1) % 7;

        /** @var PendingService $matchingService */
        $matchingService = PendingServiceFactory::make([
            'customerPreferences' => new CustomerPreferences(preferredDay: $matchingDayOfWeek),
        ]);
        /** @var PendingService $nonMatchingService */
        $nonMatchingService = PendingServiceFactory::make([
            'customerPreferences' => new CustomerPreferences(preferredDay: $nonMatchingDayOfWeek),
        ]);
        /** @var PendingService $defaultService */
        $defaultService = PendingServiceFactory::make();

        $schedulingState = new SchedulingState(1, $specificDate, OfficeFactory::make());
        $schedulingState->addPendingServices(collect([$defaultService, $matchingService, $nonMatchingService]));

        $servicePoints = $schedulingState->getPendingServicePointsForScheduledDate();

        $this->assertCount(2, $servicePoints);
        /** @var ServicePoint $servicePoint */
        $servicePoint = $servicePoints->first();
        $this->assertEquals(0, $servicePoint->getId());
        $this->assertEquals($defaultService->getSubscriptionId(), $servicePoint->getReferenceId());
    }

    /**
     * @test
     *
     * ::getStats
     */
    public function it_returns_scheduling_stats(): void
    {
        $schedulingState = new SchedulingState(1, Carbon::today(), OfficeFactory::make());
        $schedulingState->addScheduledRoutes(collect([
            ScheduledRouteFactory::make([
                'appointments' => AppointmentFactory::many(5),
                'pendingServices' => [],
                'servicePro' => ServiceProFactory::make(),
                'actualCapacityCount' => 16,
            ]),
        ]));
        $schedulingState->setMetricsBeforeScheduling();

        $schedulingState->addPendingServices(collect([
            PendingServiceFactory::make(),
            PendingServiceFactory::make([
                'previousAppointment' => AppointmentFactory::make(),
            ]),
        ]));

        $schedulingState->addScheduledRoutes(collect([
            ScheduledRouteFactory::make([
                'appointments' => [],
                'pendingServices' => PendingServiceFactory::many(4),
                'servicePro' => ServiceProFactory::make(),
                'actualCapacityCount' => 22,
            ]),
            ScheduledRouteFactory::make([
                'appointments' => AppointmentFactory::many(3),
                'pendingServices' => [
                    PendingServiceFactory::make([
                        'previousAppointment' => AppointmentFactory::make(),
                    ]),
                ],
                'servicePro' => ServiceProFactory::make(),
                'actualCapacityCount' => 20,
            ]),
        ]));

        $stats = $schedulingState->getStats();

        $this->assertEquals(1, $stats->pendingServicesCount);
        $this->assertEquals(1, $stats->pendingRescheduledServices);
        $this->assertEquals(3, $stats->routesCount);
        $this->assertEquals(4, $stats->scheduledServicesCount);
        $this->assertEquals(1, $stats->rescheduledServicesCount);
        $this->assertEquals(5, $stats->capacityBeforeScheduling);
        $this->assertEquals(27, $stats->capacityAfterScheduling);
        $this->assertEquals(8, $stats->appointmentsCount);
        $this->assertEquals(0, $stats->pendingHighPriorityServices);
        $this->assertEquals(0, $stats->scheduledHighPriorityServices);
    }

    /**
     * @test
     *
     * ::assignServicesFromClusters
     */
    public function it_adds_pending_services_from_cluster(): void
    {
        $schedulingState = new SchedulingState(1, Carbon::today(), OfficeFactory::make());
        $schedulingState->addScheduledRoutes(
            collect([
                ScheduledRouteFactory::make([
                    'id' => 1,
                    'appointments' => [],
                    'pendingServices' => [],
                ]),
            ])
        );
        $schedulingState->addPendingServices(
            collect([
                PendingServiceFactory::make([
                    'subscriptionId' => TestValue::SUBSCRIPTION_ID,
                ]),
            ])
        );
        $schedulingState->addPendingServices(
            collect([
                PendingServiceFactory::make([
                    'subscriptionId' => TestValue::SUBSCRIPTION_ID + 1,
                ]),
            ])
        );

        $clusters = collect([
            ClusterOfServicesFactory::make([
                'id' => 1,
                'servicePoints' => [
                    ServicePointFactory::make(['referenceId' => TestValue::SUBSCRIPTION_ID]),
                ],
            ]),
        ]);

        $schedulingState->assignServicesFromClusters($clusters);

        $this->assertCount(1, $schedulingState->getPendingServices());
        $this->assertCount(1, $schedulingState->getScheduledRoutes()->first()->getPendingServices());
    }

    /**
     * @test
     *
     * ::assignServicesFromClusters
     */
    public function it_does_not_add_pending_services_if_cluster_empty(): void
    {
        $schedulingState = new SchedulingState(1, Carbon::today(), OfficeFactory::make());
        $schedulingState->addScheduledRoutes(
            collect([
                ScheduledRouteFactory::make([
                    'id' => 1,
                    'appointments' => [],
                    'pendingServices' => [],
                ]),
            ])
        );
        $schedulingState->addPendingServices(
            collect([
                PendingServiceFactory::make([
                    'subscriptionId' => TestValue::SUBSCRIPTION_ID,
                ]),
            ])
        );

        $clusters = collect([
            ClusterOfServicesFactory::make([
                'id' => 1,
                'servicePoints' => [],
            ]),
        ]);

        $schedulingState->assignServicesFromClusters($clusters);

        $this->assertCount(1, $schedulingState->getPendingServices());
        $this->assertCount(0, $schedulingState->getScheduledRoutes()->first()->getPendingServices());
    }

    /**
     * @test
     *
     * @dataProvider customerDataProvider
     */
    public function it_returns_resigned_tech_assignments_with_resigned_service_pro(
        array $activeEmployeeIds,
        Customer $customer,
        bool $expectedAssignments
    ): void {
        /** @var PendingService $pendingService */
        $pendingService = PendingServiceFactory::make([
            'customerPreferences' => new CustomerPreferences(preferredEmployeeId: $customer->getPreferredTechId()),
            'customer' => $customer,
        ]);

        $schedulingState = new SchedulingState(1, Carbon::today(), OfficeFactory::make());
        $mockScheduledRoute = Mockery::mock(ScheduledRoute::class);
        $mockScheduledRoute->shouldReceive('getPendingServices')
            ->andReturn(collect([$pendingService]));

        $schedulingState->addScheduledRoutes(collect([$mockScheduledRoute]));
        $schedulingState->setAllActiveEmployeeIds($activeEmployeeIds);

        $this->assertEquals($expectedAssignments, $schedulingState->getResignedTechAssignments()->isNotEmpty());

        if ($expectedAssignments) {
            /** @var ResignedTechAssignment $resignedTechAssignment */
            $resignedTechAssignment = $schedulingState->getResignedTechAssignments()->first();
            $this->assertInstanceOf(ResignedTechAssignment::class, $resignedTechAssignment);
            $this->assertEquals($customer->getId(), $resignedTechAssignment->customerId);
            $this->assertEquals($customer->getName(), $resignedTechAssignment->customerName);
            $this->assertEquals($customer->getEmail(), $resignedTechAssignment->customerEmail);
            $this->assertEquals($pendingService->getSubscriptionId(), $resignedTechAssignment->subscriptionId);
            $this->assertEquals($pendingService->getPreferredEmployeeId(), $resignedTechAssignment->preferredTechId);
        }
    }

    /**
     * @test
     */
    public function it_resets_preferred_tech_id_of_customers_with_resigned_service_pro(): void
    {
        $schedulingState = new SchedulingState(
            1,
            Carbon::today(),
            OfficeFactory::make(['id' => TestValue::OFFICE_ID])
        );

        $customerWithResignedTech = CustomerFactory::make(['preferredTechId' => 3]);
        $pendingServiceWithResignedTech = PendingServiceFactory::make([
            'customerPreferences' => new CustomerPreferences(preferredEmployeeId: 3),
            'customer' => $customerWithResignedTech,
        ]);

        $scheduledRoute = ScheduledRouteFactory::make([
            'appointments' => [],
            'pendingServices' => [
                PendingServiceFactory::make([
                    'customerPreferences' => new CustomerPreferences(preferredEmployeeId: 1),
                    'customer' => CustomerFactory::make(['preferredTechId' => 1]),
                ]),
                PendingServiceFactory::make([
                    'customerPreferences' => new CustomerPreferences(preferredEmployeeId: 2),
                    'customer' => CustomerFactory::make(['preferredTechId' => 2]),
                ]),
                $pendingServiceWithResignedTech,
            ],
        ]);

        $schedulingState->addScheduledRoutes(collect([$scheduledRoute]));
        $schedulingState->setAllActiveEmployeeIds([1, 2]);

        $schedulingState->resetPreferredTechId();
        $expectedPendingService = $schedulingState->getScheduledRoutes()
            ->flatMap(fn (ScheduledRoute $scheduledRoute) => $scheduledRoute->getPendingServices())
            ->filter(fn (PendingService $pendingService) => $pendingService->getSubscriptionId() === $pendingServiceWithResignedTech->getSubscriptionId())
            ->first();

        $this->assertNull($expectedPendingService->getPreferredEmployeeId());
        $this->assertNull($expectedPendingService->getCustomer()->getPreferredTechId());
    }

    public static function customerDataProvider()
    {
        return [
            'Customer preferredTechId is not null & is active should not be expected' => [
                [1, 4, 6],
                CustomerFactory::make(['preferredTechId' => 1]),
                false,
            ],
            'Customer preferredTechId is not null but inactive should be expected' => [
                [1, 4, 6],
                CustomerFactory::make(['preferredTechId' => 20]),
                true,
            ],
            'Customer preferredTechId is null should not be expected' => [
                [1, 4, 6],
                CustomerFactory::make(['preferredTechId' => null]),
                false,
            ],
        ];
    }
}
