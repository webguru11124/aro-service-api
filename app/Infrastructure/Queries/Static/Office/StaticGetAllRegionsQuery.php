<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\Static\Office;

use App\Domain\SharedKernel\Entities\Office;
use App\Domain\SharedKernel\Entities\Region;
use App\Domain\Tracking\Factories\RegionFactory;
use App\Infrastructure\Exceptions\OfficesDataFileMissingException;
use App\Infrastructure\Exceptions\OfficesDataWrongFormat;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class StaticGetAllRegionsQuery
{
    public function __construct(
        private readonly StaticGetAllOfficesQuery $officesQuery,
        private readonly RegionFactory $regionFactory,
    ) {
    }

    /**
     * Get all regions.
     *
     * @return Collection<string, Region>
     * @throws OfficesDataFileMissingException|OfficesDataWrongFormat
     */
    public function get(): Collection
    {
        return $this->mapRegionsAndOffices(
            $this->fetchRegions(),
            $this->officesQuery->get(),
        );
    }

    private function mapRegionsAndOffices(Collection $regions, Collection $officesGroupedByRegion): Collection
    {
        $officesGroupedByRegion = $officesGroupedByRegion->groupBy(fn (Office $office) => $office->getRegion());

        return $regions->each(function (Region $region) use ($officesGroupedByRegion) {
            $officesInRegion = $officesGroupedByRegion->get($region->getName());

            foreach ($officesInRegion as $item) {
                $region->addOffice($item);
            }
        });
    }

    /**
     * @return Collection<Region>
     * @throws OfficesDataFileMissingException
     * @throws OfficesDataWrongFormat
     */
    private function fetchRegions(): Collection
    {
        $dataFilePath = base_path(Config::get('static-data.regions-data-file'));

        try {
            $data = file_get_contents($dataFilePath);
        } catch (Exception $e) {
            throw new OfficesDataFileMissingException();
        }

        $decodedData = json_decode($data, true);

        if (!$decodedData) {
            throw new OfficesDataWrongFormat();
        }

        return $this->mapRegions($decodedData);
    }

    /**
     * @param array<string, mixed> $decodedData
     *
     * @return Collection<string, Region>
     */
    private function mapRegions(array $decodedData): Collection
    {
        return collect($decodedData)->map(
            fn (array $data) => $this->regionFactory->create($data)
        );
    }
}
