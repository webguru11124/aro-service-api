<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Workday\Factories;

use Mockery;
use Tests\TestCase;
use Tests\Tools\TestValue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Infrastructure\Services\Workday\Factories\WorkdayJWTTokenFactory;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayErrorException;
use App\Infrastructure\Services\Workday\Factories\WorkdayAccessTokenFactory;

class WorkdayAccessTokenFactoryTest extends TestCase
{
    private const ACCESS_TOKEN_CACHE_KEY = 'workday_access_token';
    private const VALID_CACHE_PERIOD = 1234;
    private const VALID_ACCESS_TOKEN_URL = 'valid_access_token_url';

    private WorkdayAccessTokenFactory $tokenFactory;
    private WorkdayJWTTokenFactory $mockWorkdayJWTTokenFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockWorkdayJWTTokenFactory = Mockery::mock(WorkdayJWTTokenFactory::class);
        $this->tokenFactory = new WorkdayAccessTokenFactory($this->mockWorkdayJWTTokenFactory);

        Config::set('workday.auth.client_id', TestValue::WORKDAY_VALID_CLIENT_KEY);
        Config::set('workday.auth.isu_username', TestValue::WORKDAY_VALID_ISU_USERNAME);
        Config::set('workday.auth.private_key', TestValue::WORKDAY_VALID_PRIVATE_KEY);
        Config::set('workday.auth.access_token_cached_for_seconds', self::VALID_CACHE_PERIOD);
        Config::set('workday.auth.access_token_url', self::VALID_ACCESS_TOKEN_URL);
    }

    /**
     * @test
     */
    public function it_gets_access_token_from_cache(): void
    {
        Cache::shouldReceive('get')
            ->withArgs([self::ACCESS_TOKEN_CACHE_KEY])
            ->andReturn(TestValue::WORKDAY_ACCESS_TOKEN);

        $accessToken = $this->tokenFactory->make(
            TestValue::WORKDAY_VALID_CLIENT_KEY,
            TestValue::WORKDAY_VALID_ISU_USERNAME,
            TestValue::WORKDAY_VALID_PRIVATE_KEY,
        );

        $this->assertSame(TestValue::WORKDAY_ACCESS_TOKEN, $accessToken);
    }

    /**
     * @test
     */
    public function it_gets_access_token_if_no_token_is_cached(): void
    {
        Cache::shouldReceive('get')
            ->withArgs([self::ACCESS_TOKEN_CACHE_KEY])
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->withArgs([
                self::ACCESS_TOKEN_CACHE_KEY,
                TestValue::WORKDAY_ACCESS_TOKEN,
                self::VALID_CACHE_PERIOD,
            ]);

        $this->mockWorkdayJWTTokenFactory->shouldReceive('make')
            ->once()
            ->with(
                TestValue::WORKDAY_VALID_CLIENT_KEY,
                TestValue::WORKDAY_VALID_ISU_USERNAME,
                TestValue::WORKDAY_VALID_PRIVATE_KEY,
            )
            ->andReturn('mocked_jwt_token');

        Http::fake([
            '*' => Http::sequence()
                ->push([
                    'access_token' => TestValue::WORKDAY_ACCESS_TOKEN,
                    'token_type' => 'Bearer',
                ]),
        ]);

        $accessToken = $this->tokenFactory->make(
            TestValue::WORKDAY_VALID_CLIENT_KEY,
            TestValue::WORKDAY_VALID_ISU_USERNAME,
            TestValue::WORKDAY_VALID_PRIVATE_KEY,
        );

        $this->assertSame(TestValue::WORKDAY_ACCESS_TOKEN, $accessToken);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_access_token_is_not_returned(): void
    {
        Cache::shouldReceive('get')
            ->withArgs([self::ACCESS_TOKEN_CACHE_KEY])
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->never();

        $this->mockWorkdayJWTTokenFactory->shouldReceive('make')
            ->once()
            ->with(
                TestValue::WORKDAY_VALID_CLIENT_KEY,
                TestValue::WORKDAY_VALID_ISU_USERNAME,
                TestValue::WORKDAY_VALID_PRIVATE_KEY,
            )
            ->andReturn('mocked_jwt_token');

        Http::fake(['*' => Http::sequence()->push(['token_type' => 'Bearer'])]);

        $this->expectException(WorkdayErrorException::class);
        $this->tokenFactory->make(
            TestValue::WORKDAY_VALID_CLIENT_KEY,
            TestValue::WORKDAY_VALID_ISU_USERNAME,
            TestValue::WORKDAY_VALID_PRIVATE_KEY,
        );
    }

    /**
     * @test
     */
    public function it_throws_exception_if_request_was_unsuccessful(): void
    {
        Cache::shouldReceive('get')
            ->withArgs([self::ACCESS_TOKEN_CACHE_KEY])
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->never();

        $this->mockWorkdayJWTTokenFactory->shouldReceive('make')
            ->once()
            ->with(
                TestValue::WORKDAY_VALID_CLIENT_KEY,
                TestValue::WORKDAY_VALID_ISU_USERNAME,
                TestValue::WORKDAY_VALID_PRIVATE_KEY,
            )
            ->andReturn('mocked_jwt_token');

        Http::fake(['*' => Http::sequence()->push(['error' => 'Some error'])]);

        $this->expectException(WorkdayErrorException::class);
        $this->tokenFactory->make(
            TestValue::WORKDAY_VALID_CLIENT_KEY,
            TestValue::WORKDAY_VALID_ISU_USERNAME,
            TestValue::WORKDAY_VALID_PRIVATE_KEY,
        );
    }
}
