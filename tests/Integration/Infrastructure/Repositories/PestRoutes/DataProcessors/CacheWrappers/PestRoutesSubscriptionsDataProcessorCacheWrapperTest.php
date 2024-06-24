<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\CacheWrappers\PestRoutesSubscriptionsDataProcessorCacheWrapper;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesSubscriptionsDataProcessor;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\SearchSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\UpdateSubscriptionsParams;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\SubscriptionData;
use Tests\Tools\TestValue;

class PestRoutesSubscriptionsDataProcessorCacheWrapperTest extends TestCase
{
    private PestRoutesSubscriptionsDataProcessorCacheWrapper $wrapper;
    private PestRoutesSubscriptionsDataProcessor|MockInterface $mockDataProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockDataProcessor = Mockery::mock(PestRoutesSubscriptionsDataProcessor::class);
        $this->wrapper = new PestRoutesSubscriptionsDataProcessorCacheWrapper($this->mockDataProcessor);
    }

    /**
     * @test
     */
    public function it_caches_extract(): void
    {
        $officeId = TestValue::OFFICE_ID;
        $params = new SearchSubscriptionsParams(
            officeIds: [$officeId]
        );
        $subscriptions = SubscriptionData::getTestData(2);

        $this->mockDataProcessor->shouldReceive('extract')
            ->withArgs([$officeId, $params])
            ->once()
            ->andReturn($subscriptions);

        $result1 = $this->wrapper->extract($officeId, $params);
        $result2 = $this->wrapper->extract($officeId, $params);

        $this->assertSame($subscriptions, $result1);
        $this->assertSame($subscriptions, $result2);
    }

    /**
     * @test
     */
    public function it_caches_extract_ids(): void
    {
        $officeId = TestValue::OFFICE_ID;
        $params = new SearchSubscriptionsParams(
            officeIds: [$officeId]
        );

        $this->mockDataProcessor->shouldReceive('extractIds')
            ->withArgs([$officeId, $params])
            ->once()
            ->andReturn([TestValue::SUBSCRIPTION_ID]);

        $result1 = $this->wrapper->extractIds($officeId, $params);
        $result2 = $this->wrapper->extractIds($officeId, $params);

        $this->assertSame([TestValue::SUBSCRIPTION_ID], $result1);
        $this->assertSame([TestValue::SUBSCRIPTION_ID], $result2);
    }

    /**
     * @test
     */
    public function it_caches_extract_by_ids(): void
    {
        $officeId = TestValue::OFFICE_ID;
        $subscriptionIds = [
            TestValue::SUBSCRIPTION_ID,
        ];
        $subscriptions = SubscriptionData::getTestData();

        $this->mockDataProcessor->shouldReceive('extractByIds')
            ->withArgs([$officeId, $subscriptionIds])
            ->once()
            ->andReturn($subscriptions);

        $result1 = $this->wrapper->extractByIds($officeId, $subscriptionIds);
        $result2 = $this->wrapper->extractByIds($officeId, $subscriptionIds);

        $this->assertSame($subscriptions, $result1);
        $this->assertSame($subscriptions, $result2);
    }

    /**
     * @test
     */
    public function it_does_not_cache_update_subscription(): void
    {
        $officeId = TestValue::OFFICE_ID;
        $params = new UpdateSubscriptionsParams(
            subscriptionId: TestValue::SUBSCRIPTION_ID,
        );

        $this->mockDataProcessor->shouldReceive('update')
            ->with($officeId, $params)
            ->twice()
            ->andReturn(TestValue::SUBSCRIPTION_ID);

        $result1 = $this->wrapper->update($officeId, $params);
        $result2 = $this->wrapper->update($officeId, $params);

        $this->assertEquals(TestValue::SUBSCRIPTION_ID, $result1);
        $this->assertEquals(TestValue::SUBSCRIPTION_ID, $result2);

    }

    /**
     * @test
     */
    public function it_does_not_cache_reset_preferred_tech(): void
    {
        $officeId = TestValue::OFFICE_ID;

        $this->mockDataProcessor->shouldReceive('resetPreferredTech')
            ->with($officeId, TestValue::SUBSCRIPTION_ID, TestValue::CUSTOMER_ID)
            ->twice()
            ->andReturn(TestValue::SUBSCRIPTION_ID);

        $result1 = $this->wrapper->resetPreferredTech($officeId, TestValue::SUBSCRIPTION_ID, TestValue::CUSTOMER_ID);
        $result2 = $this->wrapper->resetPreferredTech($officeId, TestValue::SUBSCRIPTION_ID, TestValue::CUSTOMER_ID);

        $this->assertEquals(TestValue::SUBSCRIPTION_ID, $result1);
        $this->assertEquals(TestValue::SUBSCRIPTION_ID, $result2);

    }
}
