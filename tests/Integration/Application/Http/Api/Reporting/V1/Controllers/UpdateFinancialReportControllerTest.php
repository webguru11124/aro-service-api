<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Reporting\V1\Controllers;

use App\Application\Http\Api\Reporting\V1\Controllers\UpdateFinancialReportController;
use App\Application\Jobs\FinancialReportJob;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @coversDefaultClass UpdateFinancialReportController
 */
class UpdateFinancialReportControllerTest extends TestCase
{
    private const YEAR = 2023;
    private const MONTH = 'Mar';
    private const ROUTE_NAME = 'reporting.financial-report-jobs.create';
    private const QUEUE_NAME = 'test-queue-name';

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Config::set('queue.queues.build-reports', self::QUEUE_NAME);
    }

    /**
     * @test
     */
    public function it_returns_expected_202(): void
    {
        $response = $this->postJson(
            route(self::ROUTE_NAME),
            [
                'year' => self::YEAR,
                'month' => self::MONTH,
            ],
        );

        $response->assertAccepted();
        $response->assertJsonPath('_metadata.success', true);
        Queue::assertPushed(FinancialReportJob::class);
        Queue::assertPushedOn(self::QUEUE_NAME, FinancialReportJob::class);
    }

    /**
     * @test
     */
    public function it_returns_400_when_invalid_parameters_passed(): void
    {
        $response = $this->postJson(
            route(self::ROUTE_NAME),
            [
                'year' => 'invalid',
                'month' => 'invalid',
            ],
        );

        $response->assertBadRequest();
        Queue::assertNotPushed(FinancialReportJob::class);
    }
}
