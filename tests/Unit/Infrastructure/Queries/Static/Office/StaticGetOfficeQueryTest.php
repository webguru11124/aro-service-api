<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\Static\Office;

use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use App\Infrastructure\Queries\Static\Office\StaticGetAllOfficesQuery;
use App\Infrastructure\Queries\Static\Office\StaticGetOfficeQuery;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;

class StaticGetOfficeQueryTest extends TestCase
{
    private StaticGetOfficeQuery $officeQuery;
    private StaticGetAllOfficesQuery|MockInterface $getAllOfficesQueryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getAllOfficesQueryMock = Mockery::mock(StaticGetAllOfficesQuery::class);
        $this->officeQuery = new StaticGetOfficeQuery($this->getAllOfficesQueryMock);
    }

    /**
     * @test
     */
    public function it_gets_office_by_id(): void
    {
        $existingOfficesIds = [1, 11, 13];
        $existingOffices = $this->generateOfficesFromIds($existingOfficesIds);
        $officeIdTest = $existingOfficesIds[0];

        $this->getAllOfficesQueryMock
            ->shouldReceive('get')
            ->once()
            ->andReturn(collect($existingOffices));

        $office = $this->officeQuery->get($officeIdTest);

        $this->assertInstanceOf(Office::class, $office);
        $this->assertEquals($existingOffices[0], $office);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_office_not_found(): void
    {
        $existingOfficesIds = [1, 11, 13];
        $existingOffices = $this->generateOfficesFromIds($existingOfficesIds);
        $nonExistingOfficeId = 1000;

        $this->getAllOfficesQueryMock
            ->shouldReceive('get')
            ->once()
            ->andReturn(collect($existingOffices));

        $this->expectException(OfficeNotFoundException::class);
        $this->expectExceptionMessageMatches("/$nonExistingOfficeId/");

        $this->officeQuery->get($nonExistingOfficeId);
    }

    private function generateOfficesFromIds(array $ids): array
    {
        return array_map(fn ($id) => OfficeFactory::make(['id' => $id]), $ids);
    }
}
