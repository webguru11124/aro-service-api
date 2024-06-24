<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Tracking\V1\Controllers;

use App\Application\Http\Api\Tracking\V1\Responses\RegionsResponse;
use App\Application\Http\Responses\AbstractResponse;
use App\Application\Http\Responses\ErrorResponse;
use App\Infrastructure\Queries\Static\Office\StaticGetAllRegionsQuery;
use Illuminate\Routing\Controller;

class RegionsController extends Controller
{
    /**
     * GET /api/v1/tracking/regions
     * Returns all regions
     *
     * @param StaticGetAllRegionsQuery $officesRegionsQuery
     *
     * @return AbstractResponse
     */
    public function __invoke(StaticGetAllRegionsQuery $officesRegionsQuery): AbstractResponse
    {
        try {
            return new RegionsResponse($officesRegionsQuery->get());
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), $e->getCode());
        }
    }
}
