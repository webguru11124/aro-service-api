<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Workday;

use Mockery;
use Tests\TestCase;
use Tests\Tools\TestValue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Infrastructure\Services\Workday\WorkdayService;
use App\Infrastructure\Services\Workday\WorkdayAPIClient;
use App\Infrastructure\Services\Workday\Helpers\WorkdayXmlHelper;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayErrorException;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayBadQueryException;

class WorkdayServiceTest extends TestCase
{
    private const CACHE_EXPIRY_SECONDS = 60 * 60 * 24;
    private const CACHE_KEY_PREFIX = 'employee_photo_';

    private WorkdayAPIClient $mockWorkdayAPIClient;
    private WorkdayService $workdayService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockWorkdayAPIClient = Mockery::mock(WorkdayAPIClient::class);
        $this->workdayService = new WorkdayService($this->mockWorkdayAPIClient);

        Config::set('workday.services.human_resources_url', TestValue::WORKDAY_VALID_HUMAN_RESOURCES_URL);
    }

    /**
     * @test
     */
    public function it_gets_employee_photo_without_cache(): void
    {
        $workdayId = 'test-id';
        $expectedBase64 = 'test-base64';

        $mockWorkdayXmlHelper = Mockery::mock('alias:' . WorkdayXmlHelper::class);
        $mockWorkdayXmlHelper
            ->shouldReceive('getWorkerPhotoXMLRequest')
            ->withArgs([$workdayId])
            ->andReturn('test-xml-request');
        $mockWorkdayXmlHelper
            ->shouldReceive('getWorkerPhotoBase64FromXmlResponse')
            ->withArgs(['test-xml-response'])
            ->andReturn($expectedBase64);

        $this->mockWorkdayAPIClient
            ->shouldReceive('post')
            ->withArgs([
                TestValue::WORKDAY_VALID_HUMAN_RESOURCES_URL,
                [
                    'body' => 'test-xml-request',
                ],
            ])
            ->andReturn('test-xml-response');

        Cache::shouldReceive('has')
            ->withArgs([self::CACHE_KEY_PREFIX . $workdayId])
            ->andReturn(false);

        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $minutes) use ($workdayId, $expectedBase64) {
                return $key === self::CACHE_KEY_PREFIX . $workdayId
                    && $value === $expectedBase64
                    && $minutes === self::CACHE_EXPIRY_SECONDS;
            });

        $employeePhoto = $this->workdayService->getEmployeePhotoBase64($workdayId);
        $this->assertSame($expectedBase64, $employeePhoto);
    }

    /**
     * @test
     */
    public function it_gets_employee_photo_with_cache(): void
    {
        //test when the cache is already set
        $workdayId = 'test-id';
        $expectedBase64 = 'test-base64';

        Cache::shouldReceive('has')
            ->withArgs([self::CACHE_KEY_PREFIX . $workdayId])
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->withArgs([self::CACHE_KEY_PREFIX . $workdayId])
            ->andReturn($expectedBase64);

        $employeePhoto = $this->workdayService->getEmployeePhotoBase64($workdayId);
        $this->assertSame($expectedBase64, $employeePhoto);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_empty_workday_id(): void
    {
        $this->expectException(WorkdayBadQueryException::class);
        $this->workdayService->getEmployeePhotoBase64('');
    }

    /**
     * @test
     */
    public function it_logs_error_and_returns_empty_string_when_workday_error_occurs(): void
    {
        $workdayId = 'test-id';

        $mockWorkdayXmlHelper = Mockery::mock('alias:' . WorkdayXmlHelper::class);
        $mockWorkdayXmlHelper
            ->shouldReceive('getWorkerPhotoXMLRequest')
            ->withArgs([$workdayId])
            ->andReturn('test-xml-request');

        $this->mockWorkdayAPIClient
            ->shouldReceive('post')
            ->withArgs([
                TestValue::WORKDAY_VALID_HUMAN_RESOURCES_URL,
                [
                    'body' => 'test-xml-request',
                ],
            ])
            ->andThrow(new WorkdayErrorException('test-error'));

        Cache::shouldReceive('has')
            ->withArgs([self::CACHE_KEY_PREFIX . $workdayId])
            ->andReturn(false);

        Cache::shouldReceive('put')
            ->never();

        Log::shouldReceive('warning')->once();

        $employeePhoto = $this->workdayService->getEmployeePhotoBase64($workdayId);
        $this->assertSame('', $employeePhoto);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->mockWorkdayAPIClient);
        unset($this->workdayService);
    }
}
