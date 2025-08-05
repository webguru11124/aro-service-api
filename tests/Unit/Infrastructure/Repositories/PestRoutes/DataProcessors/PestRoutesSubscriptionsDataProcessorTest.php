<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repositories\PestRoutes\DataProcessors;

use App\Infrastructure\Repositories\PestRoutes\DataProcessors\Contracts\OfficesDataProcessor;
use App\Infrastructure\Repositories\PestRoutes\DataProcessors\PestRoutesSubscriptionsDataProcessor;
use Aptive\PestRoutesSDK\Client as PestRoutesClient;
use Aptive\PestRoutesSDK\Collection as PestRoutesCollection;
use Aptive\PestRoutesSDK\Resources\Offices\OfficesResource;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\SearchSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\Params\UpdateSubscriptionsParams;
use Aptive\PestRoutesSDK\Resources\Subscriptions\SubscriptionsResource;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\PestRoutesData\SubscriptionData;
use Tests\Tools\TestValue;
use Tests\Traits\PestRoutesClientMockBuilderAware;

class PestRoutesSubscriptionsDataProcessorTest extends TestCase
{
    use PestRoutesClientMockBuilderAware;

    private OfficesDataProcessor $officesDataProcessorMock;
    private PestRoutesClient|MockInterface $pestRoutesClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->officesDataProcessorMock = Mockery::mock(OfficesDataProcessor::class);
        $this->pestRoutesClientMock = Mockery::mock(PestRoutesClient::class);
    }

    /**
     * @test
     *
     * ::extract
     */
    public function it_extracts_subscriptions(): void
    {
        $searchSubscriptionParamsMock = \Mockery::mock(SearchSubscriptionsParams::class);
        $subscriptions = SubscriptionData::getTestData(2);
        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SubscriptionsResource::class)
            ->callSequence('subscriptions', 'includeData', 'search', 'all')
            ->methodExpectsArgs('search', [$searchSubscriptionParamsMock])
            ->willReturn(new PestRoutesCollection($subscriptions->all()))
            ->mock();

        $subject = new PestRoutesSubscriptionsDataProcessor($pestRoutesClientMock);

        $result = $subject->extract(TestValue::OFFICE_ID, $searchSubscriptionParamsMock);

        $this->assertEquals($subscriptions, $result);
    }

    /**
     * @test
     *
     * ::extractIds
     */
    public function it_extracts_subscription_ids(): void
    {
        $searchSubscriptionParams = new SearchSubscriptionsParams();

        $expectedIds = [
            $this->faker->randomNumber(4),
            $this->faker->randomNumber(4),
        ];

        $subscriptionResourceMock = \Mockery::mock(SubscriptionsResource::class);
        $subscriptionResourceMock
            ->shouldReceive('ids')
            ->once()
            ->andReturn($expectedIds);

        $pestRoutesClientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SubscriptionsResource::class)
            ->callSequence('subscriptions', 'search')
            ->methodExpectsArgs('search', function (
                SearchSubscriptionsParams $params
            ) use ($searchSubscriptionParams) {
                return $params === $searchSubscriptionParams;
            })
            ->willReturn($subscriptionResourceMock)
            ->mock();

        $pestRoutesSubscriptionsDataProcessor = new PestRoutesSubscriptionsDataProcessor($pestRoutesClientMock);
        $ids = $pestRoutesSubscriptionsDataProcessor->extractIds(TestValue::OFFICE_ID, $searchSubscriptionParams);

        $this->assertEquals($expectedIds, $ids);
    }

    /**
     * @test
     *
     * ::update
     */
    public function it_can_update_subscription(): void
    {
        $updateSubscriptionParams = Mockery::mock(UpdateSubscriptionsParams::class);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SubscriptionsResource::class)
            ->callSequence('subscriptions', 'update')
            ->methodExpectsArgs('update', [$updateSubscriptionParams])
            ->willReturn(true)
            ->mock();

        $pestRoutesSubscriptionsDataProcessor = new PestRoutesSubscriptionsDataProcessor($clientMock);

        $result = $pestRoutesSubscriptionsDataProcessor->update(TestValue::OFFICE_ID, $updateSubscriptionParams);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_resets_preferred_tech(): void
    {
        $customerId = random_int(1, 100);
        $subscriptionId = random_int(1, 100);

        $clientMock = $this->getPestRoutesClientMockBuilder()
            ->office(TestValue::OFFICE_ID)
            ->resource(SubscriptionsResource::class)
            ->callSequence('subscriptions', 'update')
            ->willReturn(true)
            ->mock();

        $pestRoutesSubscriptionsDataProcessor = new PestRoutesSubscriptionsDataProcessor($clientMock);

        $result = $pestRoutesSubscriptionsDataProcessor->resetPreferredTech(TestValue::OFFICE_ID, $subscriptionId, $customerId);

        $this->assertTrue($result);
    }

    /**
     * @test
     *
     * ::extractByIds
     */
    public function it_extracts_subscription_by_ids(): void
    {
        $officeId = 123;
        $subscriptionIds = [1, 2, 3];
        $subscriptionsData = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];

        $mockSubscriptionResource = Mockery::mock(SubscriptionsResource::class);
        $mockOfficesResource = Mockery::mock(OfficesResource::class);
        $mockOfficesResource->shouldReceive('subscriptions')
            ->andReturn($mockSubscriptionResource);
        $mockSubscriptionResource->shouldReceive('getByIds')
            ->andReturn(new PestRoutesCollection(['items' => $subscriptionsData]));

        $this->pestRoutesClientMock
            ->shouldReceive('office')
            ->with($officeId)
            ->andReturn($mockOfficesResource);

        $pestRoutesSubscriptionsDataProcessor = new PestRoutesSubscriptionsDataProcessor($this->pestRoutesClientMock);

        $result = $pestRoutesSubscriptionsDataProcessor->extractByIds($officeId, $subscriptionIds);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals($subscriptionsData, $result->get('items'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->officesDataProcessorMock);
    }
}
