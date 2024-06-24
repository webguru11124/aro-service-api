<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Workday\Queries;

use App\Domain\SharedKernel\ValueObjects\FinancialReportEntry;
use App\Domain\Contracts\Queries\Params\FinancialReportQueryParams;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayErrorException;
use App\Infrastructure\Services\Workday\Queries\WorkdayFinancialReportQuery;
use App\Infrastructure\Services\Workday\WorkdayAPIClient;
use Illuminate\Support\Facades\Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Workday\StubFinancialReportResponse;

class WorkdayFinancialReportQueryTest extends TestCase
{
    private const int YEAR = 2024;
    private const string MONTH = 'Jan';
    private const string WORKDAY_URL = 'valid-test-url';

    private WorkdayFinancialReportQuery $query;
    private WorkdayAPIClient|MockInterface $mockWorkdayAPIClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockWorkdayAPIClient = Mockery::mock(WorkdayAPIClient::class);

        $this->query = new WorkdayFinancialReportQuery($this->mockWorkdayAPIClient);

        Config::set('workday.services.financial_report_url', self::WORKDAY_URL);
    }

    /**
     * @test
     */
    public function it_queries_financial_data(): void
    {
        $this->mockWorkdayAPIClient
            ->shouldReceive('get')
            ->withSomeOfArgs(self::WORKDAY_URL)
            ->once()
            ->andReturn(StubFinancialReportResponse::responseWithSuccess());

        $params = new FinancialReportQueryParams(self::YEAR, self::MONTH);
        $result = $this->query->get($params);

        $this->assertTrue($result->isNotEmpty());
        /** @var FinancialReportEntry $data */
        $data = $result->first();
        $this->assertEquals(self::MONTH, $data->month);
        $this->assertEquals(self::YEAR, $data->year);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_selected_year_is_not_supported(): void
    {
        $this->mockWorkdayAPIClient
            ->shouldReceive('get')
            ->never();

        $this->expectException(WorkdayErrorException::class);

        $params = new FinancialReportQueryParams(2000, self::MONTH);
        $this->query->get($params);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_unable_to_parse_caml_response_from_workday(): void
    {
        $this->mockWorkdayAPIClient
            ->shouldReceive('get')
            ->withSomeOfArgs(self::WORKDAY_URL)
            ->once()
            ->andReturn(['invalid xml response']);

        $this->expectException(WorkdayErrorException::class);

        $params = new FinancialReportQueryParams(self::YEAR, self::MONTH);
        $this->query->get($params);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockWorkdayAPIClient);
        unset($this->query);
    }
}
