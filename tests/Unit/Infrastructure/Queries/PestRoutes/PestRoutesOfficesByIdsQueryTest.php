<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\PestRoutes;

use App\Domain\SharedKernel\Entities\Office as EntitiesOffice;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use App\Infrastructure\Queries\PestRoutes\PestRoutesOfficesByIdsQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesOfficeTranslator;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;

class PestRoutesOfficesByIdsQueryTest extends TestCase
{
    private PestRoutesOfficesByIdsQuery $query;
    private OfficesDataProcessor|MockInterface $officesDataProcessorMock;
    private PestRoutesOfficeTranslator|MockInterface $translatorMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->officesDataProcessorMock = Mockery::mock(OfficesDataProcessor::class);
        $this->translatorMock = Mockery::mock(PestRoutesOfficeTranslator::class);
        $this->query = new PestRoutesOfficesByIdsQuery($this->officesDataProcessorMock, $this->translatorMock);
    }

    /**
     * @test
     */
    public function it_gets_offices(): void
    {
        $existingOfficeId = 1;
        $officeIds = [$existingOfficeId];
        $pestRoutesOffice = Mockery::mock(PestRoutesOffice::class);
        $pestRoutesOffice->id = $existingOfficeId;
        $translatedOffices = collect([OfficeFactory::make()]);

        $this->officesDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->with(PestRoutesOfficesByIdsQuery::GLOBAL_OFFICE_ID, Mockery::type(SearchOfficesParams::class))
            ->andReturn(collect([$pestRoutesOffice]));

        $this->translatorMock
            ->shouldReceive('toDomain')
            ->times(count($officeIds))
            ->andReturn(...$translatedOffices);

        $result = $this->query->get(...$officeIds);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(count($officeIds), $result);
        $this->assertInstanceOf(EntitiesOffice::class, $result->first());
    }

    /**
     * @test
     */
    public function it_throws_exception_when_office_not_found(): void
    {
        $existingOfficeId = 1;
        $nonExistingOfficeId = 1000;
        $officeIds = [$existingOfficeId, $nonExistingOfficeId];

        $pestRoutesOffice = Mockery::mock(PestRoutesOffice::class);
        $pestRoutesOffice->id = $existingOfficeId;

        $this->officesDataProcessorMock
        ->shouldReceive('extract')
        ->once()
        ->with(PestRoutesOfficesByIdsQuery::GLOBAL_OFFICE_ID, Mockery::type(SearchOfficesParams::class))
        ->andReturn(collect([$pestRoutesOffice]));

        $this->translatorMock
        ->shouldReceive('toDomain')
        ->never();

        $this->expectException(OfficeNotFoundException::class);
        $this->query->get(...$officeIds);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->query, $this->officesDataProcessorMock, $this->translatorMock);
    }
}
