<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Domain\RouteOptimization\Entities\OptimizationState;
use App\Domain\RouteOptimization\Entities\Route;
use App\Domain\RouteOptimization\Entities\ServicePro;
use App\Domain\RouteOptimization\Entities\WorkEvent\Appointment;
use App\Domain\RouteOptimization\Entities\WorkEvent\Lunch;
use App\Domain\RouteOptimization\Entities\WorkEvent\ReservedTime;
use App\Domain\RouteOptimization\Entities\WorkEvent\WorkBreak;
use App\Domain\RouteOptimization\Enums\OptimizationStatus;
use App\Domain\RouteOptimization\ValueObjects\EndLocation;
use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Domain\RouteOptimization\ValueObjects\StartLocation;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\ValueObjects\Coordinate;
use App\Domain\SharedKernel\ValueObjects\Distance;
use App\Domain\SharedKernel\ValueObjects\Duration;
use App\Domain\SharedKernel\ValueObjects\TimeWindow;
use App\Infrastructure\Services\Vroom\DTO\VroomInputData;
use App\Infrastructure\Services\Vroom\Enums\StepType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\Tools\Factories\AppointmentFactory;
use Tests\Tools\Factories\OptimizationStateFactory as TestOptimizationStateFactory;
use Tests\Tools\Factories\RouteFactory;
use Tests\Tools\Factories\TravelFactory;
use Tests\Tools\Factories\Vroom\JobFactory;
use Tests\Tools\Factories\Vroom\VehicleFactory;
use Tests\Tools\TestValue;

trait VroomTranslatorDataProvider
{
    use VroomDataAndObjects;

    private const DURATION_IN_SECONDS = 900;
    private const EXPECTED_ARRIVAL_WINDOW_START = 8;
    private const EXPECTED_ARRIVAL_WINDOW_END = 20;
    private const NEW_OPTIMIZATION_STATE_ID = 828282;
    private const OPTIMIZATION_STATE_ID = 8675309;
    private const ROUTE_1_ID = 1001;
    private const ROUTE_1_START_OF_DAY_TIMESTAMP = 1687002600;
    private const ROUTE_1_END_OF_DAY_TIMESTAMP = 1687046400;
    private const ROUTE_1_START_LOCATION_LATITUDE = 40.7515;
    private const ROUTE_1_START_LOCATION_LONGITUDE = -73.3324;
    private const ROUTE_1_END_LOCATION_LATITUDE = 40.699005;
    private const ROUTE_1_END_LOCATION_LONGITUDE = -73.41671;
    private const ROUTE_1_TRAVEL_TIME = 934;
    private const ROUTE_1_TRAVEL_METERS = 45225;
    private const ROUTE_2_ID = 1002;
    private const ROUTE_2_START_OF_DAY_TIMESTAMP = 1687002665;
    private const ROUTE_2_END_OF_DAY_TIMESTAMP = 1687046400;
    private const ROUTE_2_START_LOCATION_LATITUDE = 40.9103;
    private const ROUTE_2_START_LOCATION_LONGITUDE = -73.0681;
    private const ROUTE_2_END_LOCATION_LATITUDE = 40.701462;
    private const ROUTE_2_END_LOCATION_LONGITUDE = -73.471718;
    private const ROUTE_2_TRAVEL_TIME = 1264;
    private const ROUTE_2_TRAVEL_METERS = 44254;
    private const ROUTE_3_ID = 1003;
    private const ROUTE_3_START_OF_DAY_TIMESTAMP = 1687002659;
    private const ROUTE_3_END_OF_DAY_TIMESTAMP = 1687018731;
    private const ROUTE_3_START_LOCATION_LATITUDE = 40.7586;
    private const ROUTE_3_START_LOCATION_LONGITUDE = -73.2984;
    private const ROUTE_3_END_LOCATION_LATITUDE = 40.740971;
    private const ROUTE_3_END_LOCATION_LONGITUDE = -73.514908;
    private const ROUTE_3_TRAVEL_TIME = 1353;
    private const ROUTE_3_TRAVEL_METERS = 52153;
    private const SERVICE_PRO_1_NAME = 'John Smith';
    private const SERVICE_PRO_1_ID = 6432;
    private const SERVICE_PRO_1_LINK = 'SP11111';
    private const SERVICE_PRO_2_NAME = 'Debbie Jones';
    private const SERVICE_PRO_2_ID = 9876;
    private const SERVICE_PRO_2_LINK = 'SP22222';
    private const SERVICE_PRO_3_NAME = 'Mary Davis';
    private const SERVICE_PRO_3_LINK = 'SP33333';
    private const SERVICE_PRO_3_ID = 5679;
    private const NOW_DATE = '2023-08-01';
    private const TOTAL_DRIVE_TIME = 923;
    private const TOTAL_DRIVE_METERS = 21432;
    private const TRAVEL_DURATION = 60;
    private const TRAVEL_DISTANCE = 100;
    private const EXPECTED_ARRIVAL_START_TIMESTAMP = 1687006800;
    private const EXPECTED_ARRIVAL_END_TIMESTAMP = 1687028400;
    private const PERSONAL_SKILL_MULTIPLIER = 1000;

    protected Office $office;

    private function route1(): Route
    {
        return RouteFactory::make([
            'id' => self::ROUTE_1_ID,
            'officeId' => $this->office->getId(),
            'servicePro' => $this->getServicePro1(),
            'workEvents' => $this->getRoute1WorkEvents(),
            'totalDriveTime' => Duration::fromSeconds(self::ROUTE_1_TRAVEL_TIME),
            'totalDriveDistance' => Distance::fromMeters(self::ROUTE_1_TRAVEL_METERS),
        ]);
    }

    private function getUnassignedAppointments(): array
    {
        $unassignedAppointments = [
            AppointmentFactory::make([
                'id' => 29111145,
                'description' => self::APPOINTMENT_BASIC_LABEL,
                'location' => new Coordinate(40.645454, -73.686768),
                'customerId' => 12345,
            ]),
            AppointmentFactory::make([
                'id' => 29113515,
                'description' => self::APPOINTMENT_PRO_PLUS_LABEL,
                'location' => new Coordinate(40.782555, -73.510155),
                'customerId' => 67891,
            ]),
        ];
        /** @var Appointment $unassignedAppointment */
        foreach ($unassignedAppointments as $unassignedAppointment) {
            $unassignedAppointment->setTimeWindow(null);
        }

        return $unassignedAppointments;
    }

    private function getExpectedArrival(): TimeWindow
    {
        return new TimeWindow(
            Carbon::createFromTimestamp(self::EXPECTED_ARRIVAL_START_TIMESTAMP),
            Carbon::createFromTimestamp(self::EXPECTED_ARRIVAL_END_TIMESTAMP),
        );
    }

    private function getRoute1WorkEvents(): array
    {
        $travelId = 1;
        $distance = Distance::fromMeters(self::TRAVEL_DISTANCE);

        $events[] = new StartLocation(
            Carbon::createFromTimestamp(self::ROUTE_1_START_OF_DAY_TIMESTAMP),
            new Coordinate(
                self::ROUTE_1_START_LOCATION_LATITUDE,
                self::ROUTE_1_START_LOCATION_LONGITUDE,
            ),
        );

        $endAt = Carbon::createFromTimestamp(1687003726);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = (new Appointment(
            28924002,
            self::APPOINTMENT_BASIC_LABEL,
            new Coordinate(
                40.840076,
                -73.069832,
            ),
            false,
            TestValue::OFFICE_ID,
            TestValue::CUSTOMER_ID,
            null,
            collect([new Skill(Skill::NY)]),
        ))
            ->setPriority(20)
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687003726),
                Carbon::createFromTimestamp(1687005406)
            ))
            ->setDuration($this->domainDuration25Minutes())
            ->setSetupDuration($this->domainDuration3Minutes())
            ->setExpectedArrival($this->getExpectedArrival());

        $events[] = (new WorkBreak(
            self::WORK_BREAK_15_MINUTE_ID,
            self::WORK_BREAK_15_MINUTE_LABEL,
        ))
            ->setDuration($this->domainDuration15Minutes())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687007100),
                Carbon::createFromTimestamp(1687008000)
            ))
            ->setExpectedArrival(new TimeWindow(
                Carbon::createFromTimestamp(1687007100),
                Carbon::createFromTimestamp(1687008000)
            ));

        $events[] = (new ReservedTime(
            self::RESERVED_BREAK_ID,
            self::RESERVED_BREAK_LABEL,
        ))
            ->setDuration($this->domainDuration15Minutes())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687007100),
                Carbon::createFromTimestamp(1687008000)
            ))
            ->setExpectedArrival(new TimeWindow(
                Carbon::createFromTimestamp(1687007100),
                Carbon::createFromTimestamp(1687008000)
            ));

        $endAt = Carbon::createFromTimestamp(1687008122);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow($endAt->clone()->subSeconds(self::TRAVEL_DURATION), $endAt),
        ]);

        $events[] = (new Appointment(
            28960887,
            self::APPOINTMENT_PRO_PLUS_LABEL,
            new Coordinate(
                40.959431,
                -73.011078,
            ),
            false,
            TestValue::OFFICE_ID,
            TestValue::CUSTOMER_ID,
            null,
            collect([new Skill(Skill::NY)]),
        ))
            ->setPriority(20)
            ->setExpectedArrival($this->getExpectedArrival())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687008122),
                Carbon::createFromTimestamp(1687009944)
            ))
            ->setDuration(Duration::fromSeconds(1642))
            ->setSetupDuration($this->domainDuration3Minutes());

        $events[] = (new Lunch(
            self::WORK_BREAK_LUNCH_ID,
            self::WORK_BREAK_LUNCH_LABEL,
        ))
            ->setDuration($this->domainDuration30Minutes())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687015350),
                Carbon::createFromTimestamp(1687017150)
            ))
            ->setExpectedArrival(new TimeWindow(
                Carbon::createFromTimestamp(1687015350),
                Carbon::createFromTimestamp(1687017150)
            ));

        $endAt = Carbon::createFromTimestamp(1687010021);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = (new Appointment(
            28983100,
            self::APPOINTMENT_PRO_PLUS_LABEL,
            new Coordinate(
                40.676319,
                -73.451286,
            ),
            false,
            TestValue::OFFICE_ID,
            TestValue::CUSTOMER_ID,
            null,
            collect([new Skill(Skill::NY)]),
        ))
            ->setPriority(20)
            ->setExpectedArrival($this->getExpectedArrival())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687010021),
                Carbon::createFromTimestamp(1687011528)
            ))
            ->setDuration(Duration::fromSeconds(1327))
            ->setSetupDuration($this->domainDuration3Minutes());

        $endAt = Carbon::createFromTimestamp(1687044720);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = (new Appointment(
            28985269,
            self::APPOINTMENT_BASIC_LABEL,
            new Coordinate(
                self::ROUTE_1_END_LOCATION_LATITUDE,
                self::ROUTE_1_END_LOCATION_LONGITUDE,
            ),
            false,
            TestValue::OFFICE_ID,
            TestValue::CUSTOMER_ID,
            null,
            collect([new Skill(Skill::NY)]),
        ))
            ->setPriority(20)
            ->setExpectedArrival($this->getExpectedArrival())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687044720),
                Carbon::createFromTimestamp(self::ROUTE_1_END_OF_DAY_TIMESTAMP)
            ))
            ->setDuration($this->domainDuration25Minutes())
            ->setSetupDuration($this->domainDuration3Minutes());

        $endAt = Carbon::createFromTimestamp(self::ROUTE_1_END_OF_DAY_TIMESTAMP);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = new EndLocation(
            Carbon::createFromTimestamp(self::ROUTE_1_END_OF_DAY_TIMESTAMP),
            new Coordinate(
                self::ROUTE_1_END_LOCATION_LATITUDE,
                self::ROUTE_1_END_LOCATION_LONGITUDE,
            ),
        );

        return $events;
    }

    private function route2(): Route
    {
        return RouteFactory::make([
            'id' => self::ROUTE_2_ID,
            'officeId' => $this->office->getId(),
            'servicePro' => $this->getServicePro2(),
            'workEvents' => $this->getRoute2WorkEvents(),
            'totalDriveTime' => Duration::fromSeconds(self::ROUTE_2_TRAVEL_TIME),
            'totalDriveDistance' => Distance::fromMeters(self::ROUTE_2_TRAVEL_METERS),
        ]);
    }

    private function getRoute2WorkEvents(): array
    {
        $travelId = 1;
        $distance = Distance::fromMeters(self::TRAVEL_DISTANCE);

        $events[] = new StartLocation(
            Carbon::createFromTimestamp(self::ROUTE_2_START_OF_DAY_TIMESTAMP),
            new Coordinate(
                self::ROUTE_2_START_LOCATION_LATITUDE,
                self::ROUTE_2_START_LOCATION_LONGITUDE,
            ),
        );

        $endAt = Carbon::createFromTimestamp(1687003225);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = (new Appointment(
            28766936,
            self::APPOINTMENT_BASIC_LABEL,
            new Coordinate(
                40.814091,
                -72.976273,
            ),
            false,
            TestValue::OFFICE_ID,
            TestValue::CUSTOMER_ID,
            null,
            collect([new Skill(Skill::NY)]),
        ))
            ->setPriority(20)
            ->setExpectedArrival($this->getExpectedArrival())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687003225),
                Carbon::createFromTimestamp(1687004537)
            ))
            ->setDuration(Duration::fromSeconds(1132))
            ->setSetupDuration($this->domainDuration3Minutes());

        $events[] = (new WorkBreak(
            self::WORK_BREAK_15_MINUTE_ID,
            self::WORK_BREAK_15_MINUTE_LABEL,
        ))
            ->setDuration($this->domainDuration15Minutes())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687007759),
                Carbon::createFromTimestamp(1687008659)
            ))
            ->setExpectedArrival(new TimeWindow(
                Carbon::createFromTimestamp(1687007759),
                Carbon::createFromTimestamp(1687008659)
            ));

        $endAt = Carbon::createFromTimestamp(1687011167);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = (new Appointment(
            29034431,
            self::APPOINTMENT_BASIC_LABEL,
            new Coordinate(
                40.716633,
                -73.388763,
            ),
            false,
            TestValue::OFFICE_ID,
            TestValue::CUSTOMER_ID,
            null,
            collect([new Skill(Skill::NY)]),
        ))
            ->setPriority(20)
            ->setExpectedArrival($this->getExpectedArrival())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687011167),
                Carbon::createFromTimestamp(1687012831)
            ))
            ->setDuration(Duration::fromSeconds(1484))
            ->setSetupDuration($this->domainDuration3Minutes());

        $events[] = (new Lunch(
            self::WORK_BREAK_LUNCH_ID,
            self::WORK_BREAK_LUNCH_LABEL,
        ))
            ->setDuration($this->domainDuration30Minutes())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687016396),
                Carbon::createFromTimestamp(1687018196)
            ))
            ->setExpectedArrival(new TimeWindow(
                Carbon::createFromTimestamp(1687016396),
                Carbon::createFromTimestamp(1687018196)
            ));

        $endAt = Carbon::createFromTimestamp(1687014114);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = (new Appointment(
            29051826,
            self::APPOINTMENT_QUARTERLY_SERVICE_LABEL,
            new Coordinate(
                40.700161,
                -73.542686,
            ),
            false,
            TestValue::OFFICE_ID,
            TestValue::CUSTOMER_ID,
            null,
            collect([new Skill(Skill::NY)]),
        ))
            ->setPriority(20)
            ->setExpectedArrival($this->getExpectedArrival())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687014114),
                Carbon::createFromTimestamp(1687015491)
            ))
            ->setDuration(Duration::fromSeconds(1197))
            ->setSetupDuration($this->domainDuration3Minutes());

        $endAt = Carbon::createFromTimestamp(1687044313);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = (new Appointment(
            29074527,
            self::APPOINTMENT_BASIC_LABEL,
            new Coordinate(
                self::ROUTE_2_END_LOCATION_LATITUDE,
                self::ROUTE_2_END_LOCATION_LONGITUDE,
            ),
            false,
            TestValue::OFFICE_ID,
            TestValue::CUSTOMER_ID,
            null,
            collect([new Skill(Skill::NY)]),
        ))
            ->setPriority(20)
            ->setExpectedArrival($this->getExpectedArrival())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687044313),
                Carbon::createFromTimestamp(self::ROUTE_2_END_OF_DAY_TIMESTAMP)
            ))
            ->setDuration(Duration::fromSeconds(1907))
            ->setSetupDuration($this->domainDuration3Minutes());

        $endAt = Carbon::createFromTimestamp(self::ROUTE_2_END_OF_DAY_TIMESTAMP);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = new EndLocation(
            Carbon::createFromTimestamp(self::ROUTE_2_END_OF_DAY_TIMESTAMP),
            new Coordinate(
                self::ROUTE_2_END_LOCATION_LATITUDE,
                self::ROUTE_2_END_LOCATION_LONGITUDE,
            ),
        );

        return $events;
    }

    private function route3(): Route
    {
        return RouteFactory::make([
            'id' => self::ROUTE_3_ID,
            'officeId' => $this->office->getId(),
            'servicePro' => $this->getServicePro3(),
            'workEvents' => $this->getRoute3WorkEvents(),
            'totalDriveTime' => Duration::fromSeconds(self::ROUTE_3_TRAVEL_TIME),
            'totalDriveDistance' => Distance::fromMeters(self::ROUTE_3_TRAVEL_METERS),
        ]);
    }

    private function getRoute3WorkEvents(): array
    {
        $travelId = 1;
        $distance = Distance::fromMeters(self::TRAVEL_DISTANCE);

        $events[] = new StartLocation(
            Carbon::createFromTimestamp(self::ROUTE_3_START_OF_DAY_TIMESTAMP),
            new Coordinate(
                self::ROUTE_3_START_LOCATION_LATITUDE,
                self::ROUTE_3_START_LOCATION_LONGITUDE,
            ),
        );

        $endAt = Carbon::createFromTimestamp(1687003200);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = (new Appointment(
            29095727,
            self::APPOINTMENT_BASIC_LABEL,
            new Coordinate(
                40.823002,
                -73.102203,
            ),
            false,
            TestValue::OFFICE_ID,
            TestValue::CUSTOMER_ID,
            null,
            collect([new Skill(Skill::NY)]),
        ))
            ->setPriority(20)
            ->setExpectedArrival($this->getExpectedArrival())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687003200),
                Carbon::createFromTimestamp(1687004910)
            ))
            ->setDuration(Duration::fromSeconds(1530))
            ->setSetupDuration($this->domainDuration3Minutes());

        $events[] = (new WorkBreak(
            self::WORK_BREAK_15_MINUTE_ID,
            self::WORK_BREAK_15_MINUTE_LABEL,
        ))
            ->setDuration($this->domainDuration15Minutes())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687009262),
                Carbon::createFromTimestamp(1687010162)
            ))
            ->setExpectedArrival(new TimeWindow(
                Carbon::createFromTimestamp(1687009262),
                Carbon::createFromTimestamp(1687010162)
            ));

        $endAt = Carbon::createFromTimestamp(1687008805);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = (new Appointment(
            29096259,
            self::APPOINTMENT_PRO_PLUS_LABEL,
            new Coordinate(
                40.776199,
                -72.999313,
            ),
            false,
            TestValue::OFFICE_ID,
            TestValue::CUSTOMER_ID,
            null,
            collect([new Skill(Skill::NY)]),
        ))
            ->setPriority(20)
            ->setExpectedArrival($this->getExpectedArrival())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687008805),
                Carbon::createFromTimestamp(1687009580)
            ))
            ->setDuration(Duration::fromSeconds(595))
            ->setSetupDuration($this->domainDuration3Minutes());

        $events[] = (new Lunch(
            self::WORK_BREAK_LUNCH_ID,
            self::WORK_BREAK_LUNCH_LABEL,
        ))
            ->setDuration($this->domainDuration30Minutes())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687016396),
                Carbon::createFromTimestamp(1687018196)
            ))
            ->setExpectedArrival(new TimeWindow(
                Carbon::createFromTimestamp(1687016396),
                Carbon::createFromTimestamp(1687018196)
            ));

        $endAt = Carbon::createFromTimestamp(1687017100);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = (new Appointment(
            29109922,
            self::APPOINTMENT_BASIC_LABEL,
            new Coordinate(
                self::ROUTE_3_END_LOCATION_LATITUDE,
                self::ROUTE_3_END_LOCATION_LONGITUDE,
            ),
            false,
            TestValue::OFFICE_ID,
            TestValue::CUSTOMER_ID,
            null,
            collect([new Skill(Skill::NY)]),
        ))
            ->setPriority(20)
            ->setExpectedArrival($this->getExpectedArrival())
            ->setTimeWindow(new TimeWindow(
                Carbon::createFromTimestamp(1687017100),
                Carbon::createFromTimestamp(self::ROUTE_3_END_OF_DAY_TIMESTAMP)
            ))
            ->setDuration(Duration::fromSeconds(1451))
            ->setSetupDuration($this->domainDuration3Minutes());

        $endAt = Carbon::createFromTimestamp(self::ROUTE_3_END_OF_DAY_TIMESTAMP);
        $events[] = TravelFactory::make([
            'id' => $travelId++,
            'distance' => $distance,
            'timeWindow' => new TimeWindow(
                $endAt->clone()->subSeconds(self::TRAVEL_DURATION),
                $endAt,
            ),
        ]);

        $events[] = new EndLocation(
            Carbon::createFromTimestamp(self::ROUTE_3_END_OF_DAY_TIMESTAMP),
            new Coordinate(
                self::ROUTE_3_END_LOCATION_LATITUDE,
                self::ROUTE_3_END_LOCATION_LONGITUDE,
            ),
        );

        return $events;
    }

    private function getVroomInputData(): VroomInputData
    {
        return new VroomInputData(
            new Collection(VehicleFactory::many(2)),
            new Collection(JobFactory::many(5))
        );
    }

    private function vroomResponse(): array
    {
        return [
            'code' => 0,
            'summary' => [
                'cost' => 26562,
                'routes' => 5,
                'unassigned' => 12,
                'delivery' => [
                    34,
                ],
                'amount' => [
                    34,
                ],
                'pickup' => [
                    0,
                ],
                'setup' => 7020,
                'service' => 68883,
                'duration' => self::TOTAL_DRIVE_TIME,
                'waiting_time' => 0,
                'priority' => 1520,
                'distance' => self::TOTAL_DRIVE_METERS,
                'violations' => [
                ],
                'computing_times' => [
                    'loading' => 1515,
                    'solving' => 54,
                    'routing' => 48,
                ],
            ],
            'unassigned' => [
                [
                    'id' => 29111145,
                    'location' => [
                        -73.686768,
                        40.645454,
                    ],
                    'type' => StepType::JOB->value,
                    'description' => self::APPOINTMENT_BASIC_LABEL,
                ],
                [
                    'id' => 29113515,
                    'location' => [
                        -73.510155,
                        40.782555,
                    ],
                    'type' => StepType::JOB->value,
                    'description' => self::APPOINTMENT_PRO_PLUS_LABEL,
                ],
            ],
            'routes' => [
                [
                    'vehicle' => self::SERVICE_PRO_1_ID,
                    'cost' => 5903,
                    'description' => self::SERVICE_PRO_1_NAME,
                    'delivery' => [
                        7,
                    ],
                    'amount' => [
                        7,
                    ],
                    'pickup' => [
                        0,
                    ],
                    'setup' => 1560,
                    'service' => 13271,
                    'duration' => self::ROUTE_1_TRAVEL_TIME,
                    'waiting_time' => 0,
                    'priority' => 220,
                    'distance' => self::ROUTE_1_TRAVEL_METERS,
                    'steps' => [
                        [
                            'type' => StepType::START->value,
                            'location' => [
                                self::ROUTE_1_START_LOCATION_LONGITUDE,
                                self::ROUTE_1_START_LOCATION_LATITUDE,
                            ],
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'load' => [
                                7,
                            ],
                            'arrival' => 1687002600,
                            'duration' => $travelDuration = 0,
                            'violations' => [
                            ],
                            'distance' => $travelDistance = 0,
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => 28924002,
                            'arrival' => 1687003726,
                            'service' => self::TIMESTAMP_25_MINUTES,
                            'load' => [
                                6,
                            ],
                            'location' => [
                                -73.069832,
                                40.840076,
                            ],
                            'description' => self::APPOINTMENT_BASIC_LABEL,
                            'priority' => 20,
                            'setup' => self::TIMESTAMP_3_MINUTES,
                            'duration' => $travelDuration += self::TRAVEL_DURATION,
                            'distance' => $travelDistance += self::TRAVEL_DISTANCE,
                        ],
                        [
                            'type' => StepType::BREAK->value,
                            'description' => self::WORK_BREAK_15_MINUTE_LABEL,
                            'location_index' => 0,
                            'id' => self::WORK_BREAK_15_MINUTE_ID,
                            'setup' => 0,
                            'service' => self::DURATION_IN_SECONDS,
                            'waiting_time' => 0,
                            'load' => [
                                5,
                            ],
                            'arrival' => 1687007100,
                            'duration' => $travelDuration,
                            'violations' => [
                            ],
                            'distance' => $travelDistance,
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => 28960887,
                            'arrival' => 1687008122,
                            'service' => 1642,
                            'location' => [
                                -73.011078,
                                40.959431,
                            ],
                            'description' => self::APPOINTMENT_PRO_PLUS_LABEL,
                            'priority' => 20,
                            'setup' => self::TIMESTAMP_3_MINUTES,
                            'duration' => $travelDuration += self::TRAVEL_DURATION,
                            'distance' => $travelDistance += self::TRAVEL_DISTANCE,
                        ],
                        [
                            'type' => StepType::BREAK->value,
                            'description' => self::WORK_BREAK_LUNCH_LABEL,
                            'location_index' => 0,
                            'id' => self::WORK_BREAK_LUNCH_ID,
                            'setup' => 0,
                            'service' => 1800,
                            'waiting_time' => 0,
                            'load' => [
                                2,
                            ],
                            'arrival' => 1687015350,
                            'duration' => $travelDuration,
                            'violations' => [
                            ],
                            'distance' => $travelDistance,
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => 28983100,
                            'arrival' => 1687010021,
                            'service' => 1327,
                            'location' => [
                                -73.451286,
                                40.676319,
                            ],
                            'description' => self::APPOINTMENT_PRO_PLUS_LABEL,
                            'priority' => 20,
                            'setup' => self::TIMESTAMP_3_MINUTES,
                            'duration' => $travelDuration += self::TRAVEL_DURATION,
                            'distance' => $travelDistance += self::TRAVEL_DISTANCE,
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => 28985269,
                            'arrival' => 1687044720,
                            'service' => self::TIMESTAMP_25_MINUTES,
                            'location' => [
                                self::ROUTE_1_END_LOCATION_LONGITUDE,
                                self::ROUTE_1_END_LOCATION_LATITUDE,
                            ],
                            'description' => self::APPOINTMENT_BASIC_LABEL,
                            'priority' => 20,
                            'delivery' => [
                                1,
                            ],
                            'setup' => self::TIMESTAMP_3_MINUTES,
                            'duration' => $travelDuration += self::TRAVEL_DURATION,
                            'distance' => $travelDistance += self::TRAVEL_DISTANCE,
                        ],
                        [
                            'type' => StepType::END->value,
                            'location' => [
                                self::ROUTE_1_END_LOCATION_LONGITUDE,
                                self::ROUTE_1_END_LOCATION_LATITUDE,
                            ],
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'load' => [
                                7,
                            ],
                            'arrival' => self::ROUTE_1_END_OF_DAY_TIMESTAMP,
                            'duration' => $travelDuration + self::TRAVEL_DURATION,
                            'violations' => [
                            ],
                            'distance' => $travelDistance + self::TRAVEL_DISTANCE,
                        ],
                    ],
                    'violations' => [],
                    'geometry' => 'TODO',
                ],
                [
                    'vehicle' => self::SERVICE_PRO_2_ID,
                    'cost' => 5579,
                    'description' => self::SERVICE_PRO_2_NAME,
                    'delivery' => [
                        7,
                    ],
                    'amount' => [
                        7,
                    ],
                    'pickup' => [
                        0,
                    ],
                    'setup' => 1260,
                    'service' => 14109,
                    'duration' => self::ROUTE_2_TRAVEL_TIME,
                    'waiting_time' => 0,
                    'priority' => 300,
                    'distance' => self::ROUTE_2_TRAVEL_METERS,
                    'steps' => [
                        [
                            'type' => StepType::START->value,
                            'location' => [
                                self::ROUTE_2_START_LOCATION_LONGITUDE,
                                self::ROUTE_2_START_LOCATION_LATITUDE,
                            ],
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'load' => [
                                7,
                            ],
                            'arrival' => 1687002665,
                            'duration' => $travelDuration = 0,
                            'violations' => [
                            ],
                            'distance' => $travelDistance = 0,
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => 28766936,
                            'arrival' => 1687003225,
                            'service' => 1132,
                            'location' => [
                                -72.976273,
                                40.814091,
                            ],
                            'description' => self::APPOINTMENT_BASIC_LABEL,
                            'priority' => 20,
                            'setup' => self::TIMESTAMP_3_MINUTES,
                            'duration' => $travelDuration += self::TRAVEL_DURATION,
                            'distance' => $travelDistance += self::TRAVEL_DISTANCE,
                        ],
                        [
                            'type' => StepType::BREAK->value,
                            'description' => self::WORK_BREAK_15_MINUTE_LABEL,
                            'location_index' => 0,
                            'id' => self::WORK_BREAK_15_MINUTE_ID,
                            'setup' => 0,
                            'service' => self::DURATION_IN_SECONDS,
                            'waiting_time' => 0,
                            'load' => [
                                5,
                            ],
                            'arrival' => 1687007759,
                            'duration' => $travelDuration,
                            'violations' => [
                            ],
                            'distance' => $travelDistance,
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => 29034431,
                            'arrival' => 1687011167,
                            'service' => 1484,
                            'location' => [
                                -73.388763,
                                40.716633,
                            ],
                            'description' => self::APPOINTMENT_BASIC_LABEL,
                            'setup' => self::TIMESTAMP_3_MINUTES,
                            'duration' => $travelDuration += self::TRAVEL_DURATION,
                            'distance' => $travelDistance += self::TRAVEL_DISTANCE,
                        ],
                        [
                            'type' => StepType::BREAK->value,
                            'description' => self::WORK_BREAK_LUNCH_LABEL,
                            'location_index' => 0,
                            'id' => self::WORK_BREAK_LUNCH_ID,
                            'setup' => 0,
                            'service' => 1800,
                            'waiting_time' => 0,
                            'load' => [
                                1,
                            ],
                            'arrival' => 1687016396,
                            'duration' => $travelDuration,
                            'violations' => [
                            ],
                            'distance' => $travelDistance,
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => 29051826,
                            'arrival' => 1687014114,
                            'service' => 1197,
                            'location' => [
                                -73.542686,
                                40.700161,
                            ],
                            'description' => self::APPOINTMENT_QUARTERLY_SERVICE_LABEL,
                            'priority' => 20,
                            'setup' => self::TIMESTAMP_3_MINUTES,
                            'duration' => $travelDuration += self::TRAVEL_DURATION,
                            'distance' => $travelDistance += self::TRAVEL_DISTANCE,
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => 29074527,
                            'arrival' => 1687044313,
                            'service' => 1907,
                            'location' => [
                                self::ROUTE_2_END_LOCATION_LONGITUDE,
                                self::ROUTE_2_END_LOCATION_LATITUDE,
                            ],
                            'description' => self::APPOINTMENT_BASIC_LABEL,
                            'setup' => self::TIMESTAMP_3_MINUTES,
                            'duration' => $travelDuration += self::TRAVEL_DURATION,
                            'distance' => $travelDistance += self::TRAVEL_DISTANCE,
                        ],
                        [
                            'type' => StepType::END->value,
                            'location' => [
                                self::ROUTE_2_END_LOCATION_LONGITUDE,
                                self::ROUTE_2_END_LOCATION_LATITUDE,
                            ],
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'load' => [
                                7,
                            ],
                            'arrival' => self::ROUTE_2_END_OF_DAY_TIMESTAMP,
                            'duration' => $travelDuration + self::TRAVEL_DURATION,
                            'violations' => [
                            ],
                            'distance' => $travelDistance + self::TRAVEL_DISTANCE,
                        ],
                    ],
                    'violations' => [],
                    'geometry' => 'TODO',
                ],
                [
                    'vehicle' => self::SERVICE_PRO_3_ID,
                    'cost' => 4702,
                    'description' => self::SERVICE_PRO_3_NAME,
                    'delivery' => [
                        7,
                    ],
                    'amount' => [
                        7,
                    ],
                    'pickup' => [
                        0,
                    ],
                    'setup' => 1260,
                    'service' => 13636,
                    'duration' => self::ROUTE_3_TRAVEL_TIME,
                    'waiting_time' => 0,
                    'priority' => 380,
                    'distance' => self::ROUTE_3_TRAVEL_METERS,
                    'steps' => [
                        [
                            'type' => StepType::START->value,
                            'location' => [
                                self::ROUTE_3_START_LOCATION_LONGITUDE,
                                self::ROUTE_3_START_LOCATION_LATITUDE,
                            ],
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'load' => [
                                6,
                            ],
                            'arrival' => self::ROUTE_3_START_OF_DAY_TIMESTAMP,
                            'duration' => $travelDuration = 0,
                            'violations' => [
                            ],
                            'distance' => $travelDistance = 0,
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => 29095727,
                            'arrival' => 1687003200,
                            'service' => 1530,
                            'location' => [
                                -73.102203,
                                40.823002,
                            ],
                            'description' => self::APPOINTMENT_BASIC_LABEL,
                            'priority' => 20,
                            'delivery' => [
                                1,
                            ],
                            'setup' => self::TIMESTAMP_3_MINUTES,
                            'duration' => $travelDuration += self::TRAVEL_DURATION,
                            'distance' => $travelDistance += self::TRAVEL_DISTANCE,
                        ],
                        [
                            'type' => StepType::BREAK->value,
                            'description' => self::WORK_BREAK_15_MINUTE_LABEL,
                            'location_index' => 0,
                            'id' => self::WORK_BREAK_15_MINUTE_ID,
                            'setup' => 0,
                            'service' => self::TIMESTAMP_15_MINUTES,
                            'waiting_time' => 0,
                            'load' => [
                                4,
                            ],
                            'arrival' => 1687009262,
                            'duration' => $travelDuration,
                            'violations' => [
                            ],
                            'distance' => $travelDistance,
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => 29096259,
                            'arrival' => 1687008805,
                            'service' => 595,
                            'location' => [
                                -72.999313,
                                40.776199,
                            ],
                            'description' => self::APPOINTMENT_PRO_PLUS_LABEL,
                            'setup' => self::TIMESTAMP_3_MINUTES,
                            'duration' => $travelDuration += self::TRAVEL_DURATION,
                            'distance' => $travelDistance += self::TRAVEL_DISTANCE,
                        ],
                        [
                            'type' => StepType::BREAK->value,
                            'description' => self::WORK_BREAK_LUNCH_LABEL,
                            'location_index' => 0,
                            'id' => self::WORK_BREAK_LUNCH_ID,
                            'setup' => 0,
                            'service' => 1800,
                            'waiting_time' => 0,
                            'load' => [
                                2,
                            ],
                            'arrival' => 1687016396,
                            'duration' => $travelDuration,
                            'violations' => [
                            ],
                            'distance' => $travelDistance,
                        ],
                        [
                            'type' => StepType::JOB->value,
                            'id' => 29109922,
                            'arrival' => 1687017100,
                            'service' => 1451,
                            'location' => [
                                self::ROUTE_3_END_LOCATION_LONGITUDE,
                                self::ROUTE_3_END_LOCATION_LATITUDE,
                            ],
                            'description' => self::APPOINTMENT_BASIC_LABEL,
                            'priority' => 20,
                            'delivery' => [
                                1,
                            ],
                            'setup' => self::TIMESTAMP_3_MINUTES,
                            'duration' => $travelDuration += self::TRAVEL_DURATION,
                            'distance' => $travelDistance += self::TRAVEL_DISTANCE,
                        ],
                        [
                            'type' => StepType::END->value,
                            'location' => [
                                self::ROUTE_3_END_LOCATION_LONGITUDE,
                                self::ROUTE_3_END_LOCATION_LATITUDE,
                            ],
                            'setup' => 0,
                            'service' => 0,
                            'waiting_time' => 0,
                            'load' => [
                                7,
                            ],
                            'arrival' => self::ROUTE_3_END_OF_DAY_TIMESTAMP,
                            'violations' => [
                            ],
                            'duration' => $travelDuration + self::TRAVEL_DURATION,
                            'distance' => $travelDistance + self::TRAVEL_DISTANCE,
                        ],
                    ],
                    'violations' => [],
                    'geometry' => 'TODO',
                ],
            ],
        ];
    }

    // TODO: Make this more high level to only validate general array structure rather than specific values in the array to avoid overlap with unit tests
    private function vroomRequest(): array
    {
        return [
            'vehicles' => [
                [
                    'id' => self::SERVICE_PRO_1_ID,
                    'description' => self::SERVICE_PRO_1_NAME,
                    'end' => [
                        self::ROUTE_1_END_LOCATION_LONGITUDE,
                        self::ROUTE_1_END_LOCATION_LATITUDE,
                    ],
                    'skills' => [
                        self::SERVICE_PRO_1_ID * self::PERSONAL_SKILL_MULTIPLIER,
                        Skill::INITIAL_SERVICE,
                        Skill::NY,
                    ],
                    'start' => [
                        self::ROUTE_1_START_LOCATION_LONGITUDE,
                        self::ROUTE_1_START_LOCATION_LATITUDE,
                    ],
                    'time_window' => [self::ROUTE_1_START_OF_DAY_TIMESTAMP, self::ROUTE_1_END_OF_DAY_TIMESTAMP],
                    'breaks' => [
                        [
                            'id' => 1,
                            'description' => '15 Min Break',
                            'service' => 900,
                            'time_windows' => [[1687007100, 1687008000]],
                        ],
                        [
                            'id' => 2,
                            'description' => 'Lunch',
                            'service' => 1800,
                            'time_windows' => [[1687015350, 1687017150]],
                        ],
                    ],
                    'capacity' => [16],
                    'speed_factor' => 1.0,
                ],
                [
                    'id' => self::SERVICE_PRO_2_ID,
                    'description' => self::SERVICE_PRO_2_NAME,
                    'end' => [
                        self::ROUTE_2_END_LOCATION_LONGITUDE,
                        self::ROUTE_2_END_LOCATION_LATITUDE,
                    ],
                    'skills' => [
                        self::SERVICE_PRO_2_ID * self::PERSONAL_SKILL_MULTIPLIER,
                        Skill::NY,
                    ],
                    'start' => [
                        self::ROUTE_2_START_LOCATION_LONGITUDE,
                        self::ROUTE_2_START_LOCATION_LATITUDE,
                    ],
                    'time_window' => [self::ROUTE_2_START_OF_DAY_TIMESTAMP, self::ROUTE_2_END_OF_DAY_TIMESTAMP],
                    'breaks' => [
                        [
                            'id' => 1,
                            'description' => '15 Min Break',
                            'service' => 900,
                            'time_windows' => [[1687007759, 1687008659]],
                        ],
                        [
                            'id' => 2,
                            'description' => 'Lunch',
                            'service' => 1800,
                            'time_windows' => [[1687016396, 1687018196]],
                        ],
                    ],
                    'capacity' => [16],
                    'speed_factor' => 1.0,
                ],
                [
                    'id' => self::SERVICE_PRO_3_ID,
                    'description' => self::SERVICE_PRO_3_NAME,
                    'end' => [
                        self::ROUTE_3_END_LOCATION_LONGITUDE,
                        self::ROUTE_3_END_LOCATION_LATITUDE,
                    ],
                    'skills' => [
                        self::SERVICE_PRO_3_ID * self::PERSONAL_SKILL_MULTIPLIER,
                        Skill::INITIAL_SERVICE,
                        Skill::NY,
                    ],
                    'start' => [
                        self::ROUTE_3_START_LOCATION_LONGITUDE,
                        self::ROUTE_3_START_LOCATION_LATITUDE,
                    ],
                    'time_window' => [self::ROUTE_3_START_OF_DAY_TIMESTAMP, self::ROUTE_3_END_OF_DAY_TIMESTAMP],
                    'breaks' => [
                        [
                            'id' => 1,
                            'description' => '15 Min Break',
                            'service' => 900,
                            'time_windows' => [[1687009262, 1687010162]],
                        ],
                        [
                            'id' => 2,
                            'description' => 'Lunch',
                            'service' => 1800,
                            'time_windows' => [[1687016396, 1687018196]],
                        ],
                    ],
                    'capacity' => [16],
                    'speed_factor' => 1.0,
                ],
            ],
            'jobs' => [
                [
                    'id' => 28924002,
                    'time_windows' => [
                        [
                            self::EXPECTED_ARRIVAL_START_TIMESTAMP,
                            self::EXPECTED_ARRIVAL_END_TIMESTAMP,
                        ],
                    ],
                    'skills' => [
                        Skill::NY,
                    ],
                    'service' => self::TIMESTAMP_25_MINUTES,
                    'location' => [
                        -73.069832,
                        40.840076,
                    ],
                    'description' => self::APPOINTMENT_BASIC_LABEL,
                    'priority' => 20,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
                [
                    'id' => 28960887,
                    'time_windows' => [
                        [
                            self::EXPECTED_ARRIVAL_START_TIMESTAMP,
                            self::EXPECTED_ARRIVAL_END_TIMESTAMP,
                        ],
                    ],
                    'skills' => [
                        Skill::NY,
                    ],
                    'service' => 1642,
                    'location' => [
                        -73.011078,
                        40.959431,
                    ],
                    'description' => self::APPOINTMENT_PRO_PLUS_LABEL,
                    'priority' => 20,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
                [
                    'id' => 28983100,
                    'time_windows' => [
                        [
                            self::EXPECTED_ARRIVAL_START_TIMESTAMP,
                            self::EXPECTED_ARRIVAL_END_TIMESTAMP,
                        ],
                    ],
                    'skills' => [
                        Skill::NY,
                    ],
                    'service' => 1327,
                    'location' => [
                        -73.451286,
                        40.676319,
                    ],
                    'description' => self::APPOINTMENT_PRO_PLUS_LABEL,
                    'priority' => 20,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
                [
                    'id' => 28985269,
                    'time_windows' => [
                        [
                            self::EXPECTED_ARRIVAL_START_TIMESTAMP,
                            self::EXPECTED_ARRIVAL_END_TIMESTAMP,
                        ],
                    ],
                    'skills' => [
                        Skill::NY,
                    ],
                    'service' => self::TIMESTAMP_25_MINUTES,
                    'location' => [
                        self::ROUTE_1_END_LOCATION_LONGITUDE,
                        self::ROUTE_1_END_LOCATION_LATITUDE,
                    ],
                    'description' => self::APPOINTMENT_BASIC_LABEL,
                    'priority' => 20,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
                [
                    'id' => 28766936,
                    'time_windows' => [
                        [
                            self::EXPECTED_ARRIVAL_START_TIMESTAMP,
                            self::EXPECTED_ARRIVAL_END_TIMESTAMP,
                        ],
                    ],
                    'skills' => [
                        Skill::NY,
                    ],
                    'service' => 1132,
                    'location' => [
                        -72.976273,
                        40.814091,
                    ],
                    'description' => self::APPOINTMENT_BASIC_LABEL,
                    'priority' => 20,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
                [
                    'id' => 29034431,
                    'time_windows' => [
                        [
                            self::EXPECTED_ARRIVAL_START_TIMESTAMP,
                            self::EXPECTED_ARRIVAL_END_TIMESTAMP,
                        ],
                    ],
                    'skills' => [
                        Skill::NY,
                    ],
                    'service' => 1484,
                    'location' => [
                        -73.388763,
                        40.716633,
                    ],
                    'description' => self::APPOINTMENT_BASIC_LABEL,
                    'priority' => 20,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
                [
                    'id' => 29051826,
                    'time_windows' => [
                        [
                            self::EXPECTED_ARRIVAL_START_TIMESTAMP,
                            self::EXPECTED_ARRIVAL_END_TIMESTAMP,
                        ],
                    ],
                    'skills' => [
                        Skill::NY,
                    ],
                    'service' => 1197,
                    'location' => [
                        -73.542686,
                        40.700161,
                    ],
                    'description' => self::APPOINTMENT_QUARTERLY_SERVICE_LABEL,
                    'priority' => 20,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
                [
                    'id' => 29074527,
                    'time_windows' => [
                        [
                            self::EXPECTED_ARRIVAL_START_TIMESTAMP,
                            self::EXPECTED_ARRIVAL_END_TIMESTAMP,
                        ],
                    ],
                    'skills' => [
                        Skill::NY,
                    ],
                    'service' => 1907,
                    'location' => [
                        self::ROUTE_2_END_LOCATION_LONGITUDE,
                        self::ROUTE_2_END_LOCATION_LATITUDE,
                    ],
                    'description' => self::APPOINTMENT_BASIC_LABEL,
                    'priority' => 20,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
                [
                    'id' => 29095727,
                    'time_windows' => [
                        [
                            self::EXPECTED_ARRIVAL_START_TIMESTAMP,
                            self::EXPECTED_ARRIVAL_END_TIMESTAMP,
                        ],
                    ],
                    'skills' => [
                        Skill::NY,
                    ],
                    'service' => 1530,
                    'location' => [
                        -73.102203,
                        40.823002,
                    ],
                    'description' => self::APPOINTMENT_BASIC_LABEL,
                    'priority' => 20,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
                [
                    'id' => 29096259,
                    'time_windows' => [
                        [
                            self::EXPECTED_ARRIVAL_START_TIMESTAMP,
                            self::EXPECTED_ARRIVAL_END_TIMESTAMP,
                        ],
                    ],
                    'skills' => [
                        Skill::NY,
                    ],
                    'service' => 595,
                    'location' => [
                        -72.999313,
                        40.776199,
                    ],
                    'description' => self::APPOINTMENT_PRO_PLUS_LABEL,
                    'priority' => 20,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
                [
                    'id' => 29109922,
                    'time_windows' => [
                        [
                            self::EXPECTED_ARRIVAL_START_TIMESTAMP,
                            self::EXPECTED_ARRIVAL_END_TIMESTAMP,
                        ],
                    ],
                    'skills' => [
                        Skill::NY,
                    ],
                    'service' => 1451,
                    'location' => [
                        self::ROUTE_3_END_LOCATION_LONGITUDE,
                        self::ROUTE_3_END_LOCATION_LATITUDE,
                    ],
                    'description' => self::APPOINTMENT_BASIC_LABEL,
                    'priority' => 20,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
                [
                    'id' => 29111145,
                    'time_windows' => [
                        [
                            Carbon::tomorrow()->hour(self::EXPECTED_ARRIVAL_WINDOW_START)->timestamp,
                            Carbon::tomorrow()->hour(self::EXPECTED_ARRIVAL_WINDOW_END)->timestamp,
                        ],
                    ],
                    'skills' => [
                        Skill::AA,
                    ],
                    'service' => self::DURATION_IN_SECONDS,
                    'location' => [
                        -73.686768,
                        40.645454,
                    ],
                    'description' => self::APPOINTMENT_BASIC_LABEL,
                    'priority' => 60,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
                [
                    'id' => 29113515,
                    'time_windows' => [
                        [
                            Carbon::tomorrow()->hour(self::EXPECTED_ARRIVAL_WINDOW_START)->timestamp,
                            Carbon::tomorrow()->hour(self::EXPECTED_ARRIVAL_WINDOW_END)->timestamp,
                        ],
                    ],
                    'skills' => [
                        Skill::AA,
                    ],
                    'service' => self::DURATION_IN_SECONDS,
                    'location' => [
                        -73.510155,
                        40.782555,
                    ],
                    'description' => self::APPOINTMENT_PRO_PLUS_LABEL,
                    'priority' => 60,
                    'delivery' => [
                        1,
                    ],
                    'setup' => self::TIMESTAMP_3_MINUTES,
                ],
            ],
            'options' => [
                'g' => true,
            ],
        ];
    }

    private function getIncomingOptimizationState(): OptimizationState
    {
        return TestOptimizationStateFactory::make([
            'id' => self::OPTIMIZATION_STATE_ID,
            'status' => OptimizationStatus::POST,
            'officeId' => $this->office->getId(),
            'routes' => [
                RouteFactory::make([
                    'id' => self::ROUTE_1_ID,
                    'officeId' => $this->office->getId(),
                    'servicePro' => $this->getServicePro1(),
                    'workEvents' => $this->getRoute1WorkEvents(),
                ]),
                RouteFactory::make([
                    'id' => self::ROUTE_2_ID,
                    'officeId' => $this->office->getId(),
                    'servicePro' => $this->getServicePro2(),
                    'workEvents' => $this->getRoute2WorkEvents(),
                ]),
                RouteFactory::make([
                    'id' => self::ROUTE_3_ID,
                    'officeId' => $this->office->getId(),
                    'servicePro' => $this->getServicePro3(),
                    'workEvents' => $this->getRoute3WorkEvents(),
                ]),
            ],
            'unassignedAppointments' => $this->getUnassignedAppointments(),
        ]);
    }

    private function getServicePro1(): ServicePro
    {
        $startLocation = new Coordinate(
            self::ROUTE_1_START_LOCATION_LATITUDE,
            self::ROUTE_1_START_LOCATION_LONGITUDE,
        );
        $endLocation = new Coordinate(
            self::ROUTE_1_END_LOCATION_LATITUDE,
            self::ROUTE_1_END_LOCATION_LONGITUDE,
        );
        $skills = [
            new Skill(Skill::INITIAL_SERVICE),
            new Skill(Skill::NY),
        ];
        $expectedArrival = new TimeWindow(
            new Carbon(self::START_OF_WORKDAY_DATETIME, $this->office->getTimeZone()),
            new Carbon(self::END_OF_WORKDAY_DATETIME, $this->office->getTimeZone()),
        );

        return (new ServicePro(
            self::SERVICE_PRO_1_ID,
            self::SERVICE_PRO_1_NAME,
            $startLocation,
            $endLocation,
            $expectedArrival,
            self::SERVICE_PRO_1_LINK,
            'avatar_base64'
        ))->addSkills($skills);
    }

    private function getServicePro2(): ServicePro
    {
        $startLocation = new Coordinate(
            self::ROUTE_2_START_LOCATION_LATITUDE,
            self::ROUTE_2_START_LOCATION_LONGITUDE,
        );
        $endLocation = new Coordinate(
            self::ROUTE_2_END_LOCATION_LATITUDE,
            self::ROUTE_2_END_LOCATION_LONGITUDE,
        );
        $skills = [
            new Skill(Skill::NY),
        ];
        $expectedArrival = new TimeWindow(
            new Carbon(self::START_OF_WORKDAY_DATETIME),
            new Carbon(self::END_OF_WORKDAY_DATETIME),
        );

        return (new ServicePro(
            self::SERVICE_PRO_2_ID,
            self::SERVICE_PRO_2_NAME,
            $startLocation,
            $endLocation,
            $expectedArrival,
            self::SERVICE_PRO_2_LINK,
            'avatar_base64',
        ))->addSkills($skills);
    }

    private function getServicePro3(): ServicePro
    {
        $startLocation = new Coordinate(
            self::ROUTE_3_START_LOCATION_LATITUDE,
            self::ROUTE_3_START_LOCATION_LONGITUDE,
        );
        $endLocation = new Coordinate(
            self::ROUTE_3_END_LOCATION_LATITUDE,
            self::ROUTE_3_END_LOCATION_LONGITUDE,
        );
        $skills = [
            new Skill(Skill::INITIAL_SERVICE),
            new Skill(Skill::NY),
        ];
        $expectedArrival = new TimeWindow(
            new Carbon(self::START_OF_WORKDAY_DATETIME),
            new Carbon(self::END_OF_WORKDAY_DATETIME),
        );

        return (new ServicePro(
            self::SERVICE_PRO_3_ID,
            self::SERVICE_PRO_3_NAME,
            $startLocation,
            $endLocation,
            $expectedArrival,
            self::SERVICE_PRO_3_LINK,
            'avatar_base64',
        ))->addSkills($skills);
    }
}
