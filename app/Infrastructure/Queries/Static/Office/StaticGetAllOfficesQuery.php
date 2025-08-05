<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries\Static\Office;

use App\Domain\Contracts\Queries\Office\GetAllOfficesQuery;
use App\Domain\SharedKernel\Entities\Office;
use App\Domain\Tracking\Factories\OfficeFactory;
use App\Infrastructure\Exceptions\OfficesDataFileMissingException;
use App\Infrastructure\Exceptions\OfficesDataWrongFormat;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class StaticGetAllOfficesQuery implements GetAllOfficesQuery
{
    public function __construct(
        private readonly OfficeFactory $officeFactory,
    ) {
    }

    /**
     * Get all offices.
     *
     * @return Collection<string, Office>
     * @throws OfficesDataFileMissingException|OfficesDataWrongFormat
     */
    public function get(): Collection
    {
        return $this->fetchOffices();
    }

    /**
     * @return Collection<Office>
     * @throws OfficesDataFileMissingException
     * @throws OfficesDataWrongFormat
     */
    private function fetchOffices(): Collection
    {
        $dataFilePath = base_path(Config::get('static-data.offices_data_file'));

        try {
            $data = file_get_contents($dataFilePath);
        } catch (Exception $e) {
            throw new OfficesDataFileMissingException();
        }

        $decodedData = json_decode($data, true);

        if (!$decodedData) {
            throw new OfficesDataWrongFormat();
        }

        return $this->mapOffices($decodedData);
    }

    /**
     * @param array<string, mixed> $decodedData
     *
     * @return Collection<string, Office>
     */
    private function mapOffices(array $decodedData): Collection
    {
        return collect($decodedData)->map(
            fn (array $office) => $this->officeFactory->create($office)
        );
    }
}
