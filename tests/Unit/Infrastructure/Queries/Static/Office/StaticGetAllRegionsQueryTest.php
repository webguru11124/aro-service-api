<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Queries\Static\Office;

use App\Domain\SharedKernel\Entities\Region;
use App\Infrastructure\Exceptions\OfficesDataFileMissingException;
use App\Infrastructure\Exceptions\OfficesDataWrongFormat;
use Illuminate\Support\Facades\Config;
use App\Infrastructure\Queries\Static\Office\StaticGetAllRegionsQuery;
use Tests\TestCase;

class StaticGetAllRegionsQueryTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_all_regions_with_offices(): void
    {
        Config::set('static-data.regions-data-file', 'tests/resources/Unit/Infrastructure/Repositories/Static/regions_valid_data.json');
        Config::set('static-data.offices_data_file', 'tests/resources/Unit/Infrastructure/Repositories/Static/offices_valid_data.json');

        /** @var StaticGetAllRegionsQuery $officesRegionsQuery */
        $officesRegionsQuery = ($this->app->make(StaticGetAllRegionsQuery::class));

        $regions = $officesRegionsQuery->get();

        $this->assertNotEmpty($regions);
        $this->assertContainsOnlyInstancesOf(Region::class, $regions);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_regions_data_file_is_missing(): void
    {
        Config::set('static-data.regions-data-file', 'invalid_data_file_path.json');
        /** @var StaticGetAllRegionsQuery $officesRegionsQuery */
        $officesRegionsQuery = ($this->app->make(StaticGetAllRegionsQuery::class));

        $this->expectException(OfficesDataFileMissingException::class);
        $officesRegionsQuery->get();
    }

    /**
     * @test
     */
    public function it_throws_exception_when_regions_data_file_format_is_wrong(): void
    {
        Config::set('static-data.regions-data-file', 'tests/resources/Unit/Infrastructure/Repositories/Static/invalid_data_format.json');
        /** @var StaticGetAllRegionsQuery $officesRegionsQuery */
        $officesRegionsQuery = ($this->app->make(StaticGetAllRegionsQuery::class));

        $this->expectException(OfficesDataWrongFormat::class);
        $officesRegionsQuery->get();
    }
}
