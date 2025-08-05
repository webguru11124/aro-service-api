<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Workday;

use App\Infrastructure\Services\Workday\Helpers\WorkdayXmlHelper;
use Mockery;
use Tests\TestCase;
use Tests\Tools\TestValue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use App\Infrastructure\Services\Workday\WorkdayAPIClient;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayErrorException;
use App\Infrastructure\Services\Workday\Factories\WorkdayAccessTokenFactory;
use Tests\Tools\Workday\StubFinancialReportResponse;

class WorkdayAPIClientTest extends TestCase
{
    private const WORKDAY_XML_NAMESPACE = 'wd';

    private WorkdayAccessTokenFactory $workdayAccessTokenFactory;
    private WorkdayAPIClient $workdayAPIClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workdayAccessTokenFactory = Mockery::mock(WorkdayAccessTokenFactory::class);
        $this->workdayAPIClient = new WorkdayAPIClient($this->workdayAccessTokenFactory);

        $this->workdayAccessTokenFactory
            ->shouldReceive('make')
            ->andReturn(TestValue::WORKDAY_ACCESS_TOKEN);

        $mockWorkdayXmlHelper = Mockery::mock('alias:' . WorkdayXmlHelper::class);
        $mockWorkdayXmlHelper
            ->shouldReceive('getErrorFromXMLResponse')
            ->andReturn('test-error');

        Config::set('workday.auth.client_id', TestValue::WORKDAY_VALID_CLIENT_KEY);
        Config::set('workday.auth.isu_username', TestValue::WORKDAY_VALID_ISU_USERNAME);
        Config::set('workday.auth.private_key', TestValue::WORKDAY_VALID_PRIVATE_KEY);
    }

    /**
     * @test
     */
    public function it_post_successfully(): void
    {
        $expectedResponse = [
            'data' => [
                'some' => 'data',
            ],
        ];

        Http::fake([
            '*' => Http::sequence()->push($expectedResponse),
        ]);

        $response = $this->workdayAPIClient->post('some-url', ['param' => 'some-param']);

        $this->assertSame(json_encode($expectedResponse), $response);
        Http::assertSent(function ($request) {
            return $request->url() === 'some-url'
                && $request->method() === 'POST';
        });
    }

    /**
     * @test
     */
    public function it_post_throws_exception_when_response_is_empty(): void
    {
        Http::fake([
            '*' => Http::sequence()->push(''),
        ]);

        $this->expectException(WorkdayErrorException::class);

        $this->workdayAPIClient->post('some-url', ['param' => 'some-param']);
    }

    /**
     * @test
     */
    public function it_post_throws_exception_when_response_is_unsuccessful(): void
    {
        Http::fake([
            '*' => Http::sequence()->push('', 500),
        ]);

        $this->expectException(WorkdayErrorException::class);

        $this->workdayAPIClient->post('some-url', ['param' => 'some-param']);
    }

    /**
     * @test
     */
    public function it_makes_get_request_successfully(): void
    {
        $jsonResponse = StubFinancialReportResponse::jsonResponse();
        $expectedArray = StubFinancialReportResponse::responseWithSuccess();

        Http::fake([
            '*' => Http::sequence()->push($jsonResponse),
        ]);

        $response = $this->workdayAPIClient->get('some-url', ['param' => 'some-param']);

        $this->assertEquals($expectedArray, $response);
        Http::assertSent(function ($request) {
            return $request->url() === 'some-url?param=some-param&format=json'
                && $request->method() === 'GET';
        });
    }

    /**
     * @test
     */
    public function it_throws_exception_when_get_response_is_empty(): void
    {
        Http::fake([
            '*' => Http::sequence()->push(''),
        ]);

        $this->expectException(WorkdayErrorException::class);

        $this->workdayAPIClient->get('some-url', ['param' => 'some-param']);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_get_response_is_unsuccessful(): void
    {
        Http::fake([
            '*' => Http::sequence()->push('', 500),
        ]);

        $this->expectException(WorkdayErrorException::class);

        $this->workdayAPIClient->get('some-url', ['param' => 'some-param']);
    }
}
