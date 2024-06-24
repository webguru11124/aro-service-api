<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\PestRoutes;

use App\Infrastructure\Queries\PestRoutes\PestRoutesHistoricalAppointmentsQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\CachableAppointmentsDataProcessor;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Tools\PestRoutesData\AppointmentData;
use Tests\Tools\TestValue;
use Aptive\PestRoutesSDK\Resources\Appointments\Params\SearchAppointmentsParams;
use Aptive\PestRoutesSDK\Resources\Appointments\AppointmentStatus;
use Aptive\PestRoutesSDK\Filters\DateFilter;

class PestRoutesHistoricalAppointmentsQueryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private PestRoutesHistoricalAppointmentsQuery $query;
    private $dataProcessorMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataProcessorMock = Mockery::mock(CachableAppointmentsDataProcessor::class);
        $this->query = new PestRoutesHistoricalAppointmentsQuery($this->dataProcessorMock);
    }

    /** @test */
    public function it_fetches_historical_completed_appointments_grouped_by_customer_id(): void
    {
        $customerIds = [TestValue::CUSTOMER_ID, 25673];
        $officeId = TestValue::OFFICE_ID;

        $appointmentsTestData = AppointmentData::getTestData(
            2,
            [
                'customerID' => $customerIds[0],
                'officeID' => $officeId,
            ],
            [
                'customerID' => $customerIds[1],
                'officeID' => $officeId,
            ]
        );

        $this->dataProcessorMock
            ->shouldReceive('extract')
            ->withArgs(function (int $officeId, SearchAppointmentsParams $params) use ($customerIds) {
                $array = $params->toArray();

                return $officeId === TestValue::OFFICE_ID
                    && $array['officeIDs'] === [TestValue::OFFICE_ID]
                    && $array['status'] === AppointmentStatus::Completed
                    && $array['customerIDs'] === $customerIds
                    && get_class($array['date']) === DateFilter::class;
            })
            ->once()
            ->andReturn($appointmentsTestData);

        $result = $this->query->find($customerIds, $officeId);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertTrue($result->has($customerIds[0]));
        $this->assertTrue($result->has($customerIds[1]));
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
