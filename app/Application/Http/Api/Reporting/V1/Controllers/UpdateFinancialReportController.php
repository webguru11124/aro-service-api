<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Reporting\V1\Controllers;

use App\Application\Http\Api\Reporting\V1\Requests\UpdateFinancialReportRequest;
use App\Application\Http\Api\Reporting\V1\Responses\UpdateFinancialReportResponse;
use App\Application\Jobs\FinancialReportJob;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class UpdateFinancialReportController extends Controller
{
    /**
     * POST /api/v1/reporting/financial-report-jobs
     *
     * @param UpdateFinancialReportRequest $request
     *
     * @return JsonResponse
     */
    public function __invoke(UpdateFinancialReportRequest $request): JsonResponse
    {
        $date = Carbon::today();

        FinancialReportJob::dispatch(
            $request->input('year', $date->year),
            $request->input('month', $date->shortMonthName)
        );

        return new UpdateFinancialReportResponse();
    }
}
