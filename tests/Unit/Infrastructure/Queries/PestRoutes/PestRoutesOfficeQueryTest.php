<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\PestRoutes;

use App\Domain\SharedKernel\Entities\Office as EntitiesOffice;
use App\Infrastructure\Exceptions\OfficeNotFoundException;
use App\Infrastructure\Queries\PestRoutes\PestRoutesOfficeQuery;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\Translators\PestRoutesOfficeTranslator;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;

class PestRoutesOfficeQueryTest extends TestCase
{
    private PestRoutesOfficeQuery $query;
    private OfficesDataProcessor|MockInterface $officesDataProcessorMock;
    private PestRoutesOfficeTranslator|MockInterface $translatorMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->officesDataProcessorMock = Mockery::mock(OfficesDataProcessor::class);
        $this->translatorMock = Mockery::mock(PestRoutesOfficeTranslator::class);
        $this->query = new PestRoutesOfficeQuery($this->officesDataProcessorMock, $this->translatorMock);
    }

    /**
     * @test
     */
    public function it_gets_office(): void
    {
        $officeId = 1;
        $pestRoutesOfficeMock = Mockery::mock(PestRoutesOffice::class);
        $translatedOffice = OfficeFactory::make();

        $this->officesDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->with($officeId, Mockery::type(SearchOfficesParams::class))
            ->andReturn(collect([$pestRoutesOfficeMock]));

        $this->translatorMock
            ->shouldReceive('toDomain')
            ->once()
            ->with($pestRoutesOfficeMock)
            ->andReturn($translatedOffice);

        $result = $this->query->get($officeId);

        $this->assertInstanceOf(EntitiesOffice::class, $result);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_office_not_found(): void
    {
        $officeId = 1000;

        $this->officesDataProcessorMock
            ->shouldReceive('extract')
            ->once()
            ->with($officeId, Mockery::type(SearchOfficesParams::class))
            ->andReturn(collect([]));

        $this->translatorMock
            ->shouldReceive('toDomain')
            ->never();

        $this->expectException(OfficeNotFoundException::class);
        $this->query->get($officeId);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->query, $this->officesDataProcessorMock, $this->translatorMock);
    }
}
