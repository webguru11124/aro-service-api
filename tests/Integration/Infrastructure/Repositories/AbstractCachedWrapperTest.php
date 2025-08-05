<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Repositories;

use App\Infrastructure\CacheWrapper\AbstractCachedWrapper;
use App\Infrastructure\Exceptions\CachedWrapperException;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Tests\TestCase;

class AbstractCachedWrapperTest extends TestCase
{
    /**
     * @test
     */
    public function it_caches_method_result(): void
    {
        $wrappee = new class () implements Countable {
            private int $calls = 0;

            public function count(): int
            {
                return ++$this->calls;
            }
        };

        $wrapper = $this->getWrapper($wrappee);

        $iterations = random_int(2, 5);

        for ($i = 1; $i <= $iterations; $i++) {
            $result = $wrapper->count();

            $this->assertSame(1, $result);
        }
    }

    /**
     * @test
     */
    public function it_throws_exception_if_wrapper_and_wrappee_implements_different_interfaces(): void
    {
        $wrappee = new class () implements Arrayable {
            public function toArray()
            {
                return [];
            }
        };

        $wrapper = $this->getWrapper($wrappee);

        $this->expectException(CachedWrapperException::class);

        $wrapper->count();
    }

    private function getWrapper(mixed $wrappee): AbstractCachedWrapper|Countable
    {
        return new class ($wrappee) extends AbstractCachedWrapper implements Countable {
            public function __construct(mixed $wrapped)
            {
                $this->wrapped = $wrapped;
            }

            protected function getCacheTtl(string $methodName): int
            {
                return 100;
            }

            protected function getPrefix(): string
            {
                return 'testPrefix_';
            }

            public function count(): int
            {
                return $this->cached(__FUNCTION__);
            }
        };
    }
}
