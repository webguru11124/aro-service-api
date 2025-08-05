<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\Static\Office;

use App\Domain\SharedKernel\Entities\Office;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use App\Infrastructure\Queries\Static\Office\StaticGetAllOfficesQuery;
use App\Infrastructure\Queries\Static\Office\StaticGetOfficesByIdsQuery;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;

class StaticGetOfficesByIdsQueryTest extends TestCase
{
    private StaticGetOfficesByIdsQuery $officesByIdsQuery;
    private StaticGetAllOfficesQuery|MockInterface $getAllOfficesQueryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getAllOfficesQueryMock = Mockery::mock(StaticGetAllOfficesQuery::class);
        $this->officesByIdsQuery = new StaticGetOfficesByIdsQuery($this->getAllOfficesQueryMock);
    }

    /**
     * @test
     */
    public function it_gets_offices_by_their_ids(): void
    {
        $existingOfficesIds = [1, 11, 13];
        $existingOffices = $this->generateOfficesFromIds($existingOfficesIds);
        $requestedOfficeIds = [1, 11];

        $this->getAllOfficesQueryMock
            ->shouldReceive('get')
            ->once()
            ->andReturn(collect($existingOffices));

        $offices = $this->officesByIdsQuery->get($requestedOfficeIds);

        $this->assertInstanceOf(Collection::class, $offices);
        $this->assertCount(count($requestedOfficeIds), $offices);
        $officeIds = $offices->map(fn (Office $office) => $office->getId())->toArray();
        $this->assertEquals($requestedOfficeIds, $officeIds);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_office_not_found(): void
    {
        $existingOfficesIds = [1, 11, 13];
        $existingOffices = $this->generateOfficesFromIds($existingOfficesIds);
        $nonExistingOfficeIds = [1000, 1001];

        $this->getAllOfficesQueryMock
            ->shouldReceive('get')
            ->once()
            ->andReturn(collect($existingOffices));

        $this->expectException(OfficeNotFoundException::class);
        $this->expectExceptionMessageMatches('/' . implode('|', $nonExistingOfficeIds) . '/');

        $this->officesByIdsQuery->get($nonExistingOfficeIds);
    }

    private function generateOfficesFromIds(array $ids): array
    {
        return array_map(fn ($id) => OfficeFactory::make(['id' => $id]), $ids);
    }
}
