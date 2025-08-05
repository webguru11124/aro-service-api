<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Tracking\V1\Controllers;

use App\Application\Http\Api\Tracking\V1\Responses\OfficesResponse;
use App\Application\Http\Responses\AbstractResponse;
use App\Application\Http\Responses\ErrorResponse;
use App\Infrastructure\Queries\Static\Office\StaticGetAllOfficesQuery;
use Illuminate\Routing\Controller;

class OfficesController extends Controller
{
    /**
     * GET /api/v1/tracking/offices
     * Returns all offices
     *
     * @param StaticGetAllOfficesQuery $officesQuery
     *
     * @return AbstractResponse
     */
    public function __invoke(StaticGetAllOfficesQuery $officesQuery): AbstractResponse
    {
        try {
            return new OfficesResponse($officesQuery->get());
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), $e->getCode());
        }
    }
}
