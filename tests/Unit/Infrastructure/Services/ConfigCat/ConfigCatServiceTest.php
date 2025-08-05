<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\ConfigCat;

use App\Infrastructure\Services\ConfigCat\ConfigCatService;
use App\Infrastructure\Services\ConfigCat\Exceptions\InvalidConfigCatValueType;
use App\Infrastructure\Services\ConfigCat\Exceptions\UnknownConfigCatError;
use ConfigCat\ClientInterface;
use ConfigCat\ConfigCatClient;
use ConfigCat\User;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ConfigCatServiceTest extends TestCase
{
    public const TEST_OFFICE_ID = 5;
    public const TEST_FEATURE_NAME = 'isTestFeatureEnabled';
    public const TEST_STRING_VALUE_FEATURE_NAME = 'whichStringFeatureValueIsSelected';

    private ConfigCatService $service;
    private MockInterface|ConfigCatClient $mockConfigCatClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConfigCatClient = Mockery::mock(ClientInterface::class);

        $this->service = new ConfigCatService(
            $this->mockConfigCatClient
        );
    }

    /**
     * @test
     *
     * ::isFeatureEnabled
     */
    public function it_returns_feature_flag_status(): void
    {
        $this->mockConfigCatClient
            ->shouldReceive('getValue')
            ->withArgs(function (string $feature, null $defaultValue, User $user) {
                return $feature === self::TEST_FEATURE_NAME;
            })
            ->andReturn(true);

        $result = $this->service->isFeatureEnabled(self::TEST_FEATURE_NAME);

        $this->assertTrue($result);
    }

    /**
     * @test
     *
     * ::isFeatureEnabled
     */
    public function it_throws_exception_when_wrong_type_of_boolean_flag_returned_for_feature_flag(): void
    {
        $this->mockConfigCatClient
            ->shouldReceive('getValue')
            ->andReturn('wrongType');

        $this->expectException(InvalidConfigCatValueType::class);

        $this->service->isFeatureEnabled(self::TEST_FEATURE_NAME);
    }

    /**
     * @test
     *
     * ::isFeatureEnabledForOffice
     */
    public function it_returns_feature_flag_status_for_office(): void
    {
        $this->mockConfigCatClient
            ->shouldReceive('getValue')
            ->withArgs(function (string $feature, null $defaultValue, User $user) {
                return $feature === self::TEST_FEATURE_NAME
                    && $user->getAttribute('office_id') == self::TEST_OFFICE_ID;
            })
            ->andReturn(true);

        $result = $this->service->isFeatureEnabledForOffice(self::TEST_OFFICE_ID, self::TEST_FEATURE_NAME);

        $this->assertTrue($result);
    }

    /**
     * @test
     *
     * ::getFeatureFlagStringValueForOffice
     */
    public function it_runs_and_returns_string_value(): void
    {
        $expectedValue = 'testStringValue';
        $this->mockConfigCatClient
            ->shouldReceive('getValue')
            ->withArgs(function (string $feature, null $defaultValue, User $user) {
                return $feature === self::TEST_STRING_VALUE_FEATURE_NAME
                    && $user->getAttribute('office_id') == self::TEST_OFFICE_ID;
            })
            ->andReturn($expectedValue);

        $result = $this->service->getFeatureFlagStringValueForOffice(self::TEST_OFFICE_ID, self::TEST_STRING_VALUE_FEATURE_NAME);

        $this->assertEquals($expectedValue, $result);
    }

    /**
     * @test
     *
     * ::isFeatureEnabledForOffice
     */
    public function it_throws_exception_when_unknown_error_occurred(): void
    {
        $this->mockConfigCatClient
            ->shouldReceive('getValue')
            ->withAnyArgs()
            ->andReturn(null);

        $this->expectException(UnknownConfigCatError::class);

        $this->service->isFeatureEnabledForOffice(self::TEST_OFFICE_ID, self::TEST_FEATURE_NAME);
    }

    /**
     * @test
     *
     * ::isFeatureEnabledForOffice
     */
    public function it_throws_exception_when_wrong_type_of_boolean_flag_returned(): void
    {
        $this->mockConfigCatClient
            ->shouldReceive('getValue')
            ->withArgs(function (string $feature, null $defaultValue, User $user) {
                return $feature === self::TEST_FEATURE_NAME
                    && $user->getAttribute('office_id') == self::TEST_OFFICE_ID;
            })
            ->andReturn('wrongType');

        $this->expectException(InvalidConfigCatValueType::class);

        $this->service->isFeatureEnabledForOffice(self::TEST_OFFICE_ID, self::TEST_FEATURE_NAME);
    }

    /**
     * @test
     *
     * ::getFeatureFlagStringValueForOffice
     */
    public function it_throws_exception_when_wrong_type_of_string_flag_returned(): void
    {
        $this->mockConfigCatClient
            ->shouldReceive('getValue')
            ->withArgs(function (string $feature, null $defaultValue, User $user) {
                return $feature === self::TEST_STRING_VALUE_FEATURE_NAME
                    && $user->getAttribute('office_id') == self::TEST_OFFICE_ID;
            })
            ->andReturn(true);

        $this->expectException(InvalidConfigCatValueType::class);

        $this->service->getFeatureFlagStringValueForOffice(self::TEST_OFFICE_ID, self::TEST_STRING_VALUE_FEATURE_NAME);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->service);
        unset($this->mockConfigCatClient);
    }
}
