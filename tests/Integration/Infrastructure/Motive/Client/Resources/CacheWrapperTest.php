<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Motive\Client\Resources;

use App\Infrastructure\Services\Motive\Client\Resources\AbstractResource;
use App\Infrastructure\Services\Motive\Client\Resources\CacheWrapper;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

class CacheWrapperTest extends TestCase
{
    private const TTL = 3600;

    private CacheWrapper $wrapper;
    private AbstractResource|MockInterface $resourceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $cacheClient = Cache::store('array');

        $this->resourceMock = \Mockery::mock(AbstractResource::class);
        $this->wrapper = new CacheWrapper($this->resourceMock, $cacheClient, self::TTL);
    }

    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_caches_data(mixed $data): void
    {
        $method = 'testMethod';
        $argument1 = $this->faker->randomNumber(2);
        $argument2 = $this->faker->date();

        $this->resourceMock
            ->shouldReceive($method)
            ->once()
            ->with($argument1, $argument2)
            ->andReturn($data);

        $result1 = $this->wrapper->$method($argument1, $argument2);
        $result2 = $this->wrapper->$method($argument1, $argument2);

        $this->assertSame($data, $result1);
        $this->assertSame($data, $result2);
    }

    public static function dataProvider(): mixed
    {
        yield [random_int(10, 20)];
        yield [(object) [
            'prop1' => random_int(10, 20),
            'prop2' => 'testProperty',
        ]];
        yield [null];
        yield [[
            'prop1' => random_int(10, 20),
            'prop2' => 'testProperty',
        ]];
    }

    /**
     * @test
     */
    public function it_reloads_data_to_cache(): void
    {
        $cacheClient = Cache::store('array');
        $wrapper = new CacheWrapper($this->resourceMock, $cacheClient, self::TTL, true);

        $method = 'testMethod';
        $argument1 = $this->faker->randomNumber(2);
        $argument2 = $this->faker->date();
        $data = ['prop' => $this->faker->randomNumber(2)];

        $this->resourceMock
            ->shouldReceive($method)
            ->twice()
            ->with($argument1, $argument2)
            ->andReturn($data);

        $result1 = $wrapper->$method($argument1, $argument2);
        $result2 = $wrapper->$method($argument1, $argument2);

        $this->assertSame($data, $result1);
        $this->assertSame($data, $result2);
    }

    /**
     * @test
     */
    public function it_preloads_data(): void
    {
        $method = 'testMethod';
        $argument1 = $this->faker->randomNumber(2);
        $argument2 = $this->faker->date();

        $data = (object) ['prop' => $this->faker->randomNumber(2)];

        $this->resourceMock
            ->shouldReceive($method)
            ->never();

        $this->wrapper->preload($data, $method, $argument1, $argument2);

        $result = $this->wrapper->$method($argument1, $argument2);

        $this->assertSame($data, $result);
    }
}
