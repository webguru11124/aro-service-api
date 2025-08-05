<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts;

use Aptive\PestRoutesSDK\Http\AbstractHttpParams;
use Aptive\PestRoutesSDK\Resources\Spots\Params\SearchSpotsParams;
use Aptive\PestRoutesSDK\Resources\Spots\Spot as PestRoutesSpot;
use Illuminate\Support\Collection;

interface SpotsDataProcessor
{
    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchSpotsParams $params
     *
     * @return Collection<PestRoutesSpot>
     */
    public function extract(int $officeId, AbstractHttpParams|SearchSpotsParams $params): Collection;

    /**
     * It is the same as extract method, but it performs requests asynchronously.
     *
     * @param int $officeId
     * @param AbstractHttpParams|SearchSpotsParams $params
     *
     * @return Collection<PestRoutesSpot>
     */
    public function extractAsync(int $officeId, AbstractHttpParams|SearchSpotsParams $params): Collection;

    /**
     * @param int $officeId
     * @param AbstractHttpParams|SearchSpotsParams $params
     *
     * @return Collection<int>
     */
    public function extractIds(int $officeId, AbstractHttpParams|SearchSpotsParams $params): Collection;

    /**
     * @param int $officeId
     * @param int $spotId
     * @param string $blockReason
     *
     * @return void
     */
    public function block(int $officeId, int $spotId, string $blockReason): void;

    /**
     * @param int $officeId
     * @param int $spotId
     *
     * @return void
     */
    public function unblock(int $officeId, int $spotId): void;

    /**
     * @param int $officeId
     * @param Collection<PestRoutesSpot> $spotsCollection
     * @param string $blockReason
     *
     * @return void
     */
    public function blockMultiple(int $officeId, Collection $spotsCollection, string $blockReason): void;

    /**
     * @param int $officeId
     * @param Collection<PestRoutesSpot> $spotsCollection
     *
     * @return void
     */
    public function unblockMultiple(int $officeId, Collection $spotsCollection): void;
}
