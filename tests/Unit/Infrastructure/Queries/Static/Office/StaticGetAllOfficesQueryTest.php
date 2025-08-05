<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\Static\Office;

use App\Domain\SharedKernel\Entities\Office;
use App\Domain\Tracking\Factories\OfficeFactory;
use App\Infrastructure\Exceptions\OfficesDataFileMissingException;
use App\Infrastructure\Exceptions\OfficesDataWrongFormat;
use App\Infrastructure\Queries\Static\Office\StaticGetAllOfficesQuery;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory as FactoriesOfficeFactory;
use Tests\Tools\TestValue;

class StaticGetAllOfficesQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_all_offices(): void
    {
        Config::set('static-data.offices_data_file', TestValue::VALID_OFFICES_DATA_FILE);

        $officeFactory = Mockery::mock(OfficeFactory::class);
        $officeFactory
            ->shouldReceive('create')
            ->atLeast()
            ->once()
            ->andReturn(FactoriesOfficeFactory::make());

        $this->instance(OfficeFactory::class, $officeFactory);

        /** @var StaticGetAllOfficesQuery $officesQuery */
        $officesQuery = ($this->app->make(StaticGetAllOfficesQuery::class));

        $offices = $officesQuery->get();

        $this->assertNotEmpty($offices);
        $this->assertContainsOnlyInstancesOf(Office::class, $offices);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_office_data_file_is_missing(): void
    {
        Config::set('static-data.offices_data_file', 'invalid_data_file_path.json');
        /** @var StaticGetAllOfficesQuery $officesQuery */
        $officesQuery = ($this->app->make(StaticGetAllOfficesQuery::class));

        $this->expectException(OfficesDataFileMissingException::class);
        $officesQuery->get();
    }

    /**
     * @test
     */
    public function it_throws_exception_when_offices_data_file_format_is_wrong(): void
    {
        Config::set('static-data.offices_data_file', 'tests/resources/Unit/Infrastructure/Repositories/Static/invalid_data_format.json');

        /** @var StaticGetAllOfficesQuery $officesQuery */
        $officesQuery = ($this->app->make(StaticGetAllOfficesQuery::class));

        $this->expectException(OfficesDataWrongFormat::class);
        $officesQuery->get();
    }
}
