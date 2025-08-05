<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\SpotsDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\PestRoutesClientAware;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\PestRoutesSDK\Client as PestRoutesClient;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Offices\Office as PestRoutesOffice;
use Aptive\PestRoutesSDK\Resources\Offices\Params\SearchOfficesParams;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Illuminate\Support\Collection;

class PestRoutesSpotsDataProcessor implements SpotsDataProcessor
{
    use PestRoutesClientAware;

    public function __construct(
        private PestRoutesClient $client,
        private OfficesDataProcessor $officesDataProcessor
    ) {
    }

    /**
     * Extract spots based on the office ID and search parameters.
     *
     * @param int $officeId
     * @param AbstractHttpParams|SearchSpotsParams $params
     *
     * @return Collection<PestRoutesSpot>
     * @throws InternalServerErrorHttpException
     */
    public function extract(int $officeId, AbstractHttpParams|SearchSpotsParams $params): Collection
    {
        $pestRoutesOffice = $this->getOffice($officeId);

        $pestRoutesSpots = $this->getClient()->office($officeId)
            ->spots()
            ->includeData()
            ->search($params, new PestRoutesCollection([$pestRoutesOffice]))
            ->all();

        /** @var Collection<PestRoutesSpot> $pestRoutesSpots */
        $pestRoutesSpots = new Collection($pestRoutesSpots->items);

        return $pestRoutesSpots->values();
    }

    /**
     * Extract spots based on the office ID and search parameters, by making async requests
     * Can be useful when expected amount of spots is larger than 1000.
     *
     * @param int $officeId
     * @param AbstractHttpParams|SearchSpotsParams $params
     *
     * @return Collection<PestRoutesSpot>
     * @throws InternalServerErrorHttpException
     */
    public function extractAsync(int $officeId, AbstractHttpParams|SearchSpotsParams $params): Collection
    {
        $pestRoutesOffice = $this->getOffice($officeId);

        $pestRoutesSpots = $this->getClient()->office($officeId)
            ->spots()
            ->searchAsync($params, new PestRoutesCollection([$pestRoutesOffice]));

        /** @var Collection<PestRoutesSpot> $pestRoutesSpots */
        $pestRoutesSpots = new Collection($pestRoutesSpots->items);

        return $pestRoutesSpots->values();
    }

    /**
     * Extract spots IDs based on the office ID and search parameters.
     *
     * @param int $officeId
     * @param AbstractHttpParams|SearchSpotsParams $params
     *
     * @return Collection<int>
     * @throws InternalServerErrorHttpException
     */
    public function extractIds(int $officeId, AbstractHttpParams|SearchSpotsParams $params): Collection
    {
        $ids = $this->getClient()->office($officeId)
            ->spots()
            ->search($params)
            ->ids();

        return collect($ids);
    }

    /**
    * Blocks a specified spot for a given reason.
    *
    * @param int $officeId
    * @param string $blockReason
    *
    * @return void
    * @throws InternalServerErrorHttpException
    */
    public function block(int $officeId, int $spotId, string $blockReason): void
    {
        $this->getClient()
            ->office($officeId)
            ->spots()
            ->block($spotId, [], $blockReason);
    }

    /**
     * @param int $officeId
     * @param int $spotId
     *
     * @return void
     * @throws InternalServerErrorHttpException
     */
    public function unblock(int $officeId, int $spotId): void
    {
        $this->getClient()
            ->office($officeId)
            ->spots()
            ->unblock($spotId);
    }

    /**
     * Blocks multiple spots for a given reason.
     *
     * @param int $officeId
     * @param Collection<PestRoutesSpot> $spotsCollection
     * @param string $blockReason
     *
     * @return void
     * @throws InternalServerErrorHttpException
     */
    public function blockMultiple(int $officeId, Collection $spotsCollection, string $blockReason): void
    {
        if ($spotsCollection->isEmpty()) {
            return;
        }

        $spotIds = $spotsCollection->map(fn (PestRoutesSpot $spot) => $spot->id)->toArray();

        $this->getClient()
            ->office($officeId)
            ->spots()
            ->block(null, $spotIds, $blockReason);
    }

    /**
     * Unblocks multiple spots.
     *
     * @param int $officeId
     * @param Collection<PestRoutesSpot> $spotsCollection The collection of spots to be unblocked.
     *
     * @return void
     * @throws InternalServerErrorHttpException
     */
    public function unblockMultiple(int $officeId, Collection $spotsCollection): void
    {
        if ($spotsCollection->isEmpty()) {
            return;
        }

        $spotIds = $spotsCollection->map(fn (PestRoutesSpot $spot) => $spot->id)->toArray();

        $this->getClient()
            ->office($officeId)
            ->spots()
            ->unblock(null, $spotIds);
    }

    private function getOffice(int $officeId): PestRoutesOffice
    {
        return $this->officesDataProcessor->extract($officeId, new SearchOfficesParams(
            officeId: $officeId
        ))->first();
    }
}
