<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Workday\Queries\Cached;

use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use App\Infrastructure\Services\Workday\Queries\WorkdayEmployeeInfoQuery;
use App\Infrastructure\Services\Workday\Queries\Cached\WorkdayEmployeeInfoCachedQuery;
use App\Domain\Contracts\Queries\Params\EmployeeInfoQueryParams;
use App\Domain\SharedKernel\Entities\Employee;
use App\Domain\SharedKernel\ValueObjects\Address;
use App\Domain\SharedKernel\Entities\WorkPeriod;
use App\Domain\SharedKernel\Entities\Skill;

class WorkdayEmployeeInfoCachedQueryTest extends TestCase
{
    private const CACHE_KEY = 'WorkdayEmployeeInfoQuery_44539f0e1916ddd6fc13a1749a440ab1';

    /**
     * @test
     */
    public function it_retrieves_data_from_cache_if_available()
    {
        $params = new EmployeeInfoQueryParams('123');
        $cacheKey = self::CACHE_KEY;
        $expectedResult = $this->getExpectedResult();

        Cache::shouldReceive('remember')
            ->once()
            ->with(Mockery::on(function ($key) use ($cacheKey) {
                return $key === $cacheKey;
            }), 86400, Mockery::on(function ($closure) {
                return is_callable($closure);
            }))
            ->andReturn($expectedResult);

        $query = Mockery::mock(WorkdayEmployeeInfoQuery::class);
        $cachedQuery = new WorkdayEmployeeInfoCachedQuery($query);

        $result = $cachedQuery->get($params);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @test
     */
    public function it_fetches_data_and_caches_it_if_not_available()
    {
        $params = new EmployeeInfoQueryParams('123');
        $cacheKey = self::CACHE_KEY;
        $expectedResult = $this->getExpectedResult();

        Cache::shouldReceive('remember')
            ->once()
            ->with(Mockery::on(function ($key) use ($cacheKey) {
                return $key === $cacheKey;
            }), 86400, Mockery::on(function ($closure) use ($expectedResult) {
                return $closure() === $expectedResult;
            }))
            ->andReturn($expectedResult);

        $query = Mockery::mock(WorkdayEmployeeInfoQuery::class);
        $query->shouldReceive('get')->once()->withAnyArgs()->andReturn($expectedResult);

        $cachedQuery = new WorkdayEmployeeInfoCachedQuery($query);
        $result = $cachedQuery->get($params);

        $this->assertEquals($expectedResult, $result);
    }

    private function getExpectedResult()
    {
        $address = new Address('123 Main St', 'Springfield', 'State', '12345');
        $workPeriod = new WorkPeriod('M-F 9am-5pm');
        $skills = new Collection([
            new Skill('Project Management'),
            new Skill('Software Development'),
        ]);

        return new Collection([
            new Employee(
                'E001',
                'John',
                'Doe',
                '1980-01-01',
                '2020-01-01',
                'M001',
                'john.doe@example.com',
                '123-456-7890',
                $address,
                $workPeriod,
                $skills
            ),
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
