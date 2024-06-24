<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Motive\Client\Resources\Users;

use App\Infrastructure\Services\Motive\Client\Exceptions\MotiveClientException;
use App\Infrastructure\Services\Motive\Client\HttpClient\HttpClient;
use App\Infrastructure\Services\Motive\Client\Resources\Users\Params\FindUserParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\Params\SearchUsersParams;
use App\Infrastructure\Services\Motive\Client\Resources\Users\User;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UserRole;
use App\Infrastructure\Services\Motive\Client\Resources\Users\UsersResource;
use Illuminate\Support\Collection;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\MotiveData\UserData;
use Tests\Unit\Infrastructure\Services\Motive\Client\Resources\ResourceCanBeCached;

class UsersResourceTest extends TestCase
{
    use ResourceCanBeCached;

    private const ENDPOINT_FIND = 'https://api.keeptruckin.com/v1/users/lookup';
    private const ENDPOINT_SEARCH = 'https://api.keeptruckin.com/v1/users';

    private HttpClient|MockInterface $httpClientMock;
    private UsersResource $resource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClientMock = \Mockery::mock(HttpClient::class);
        $this->resource = new UsersResource($this->httpClientMock);
    }

    /**
     * @test
     */
    public function it_finds_single_user(): void
    {
        $params = new FindUserParams(
            driverCompanyId: $this->faker->text(7)
        );

        $rawUser = (object) UserData::getRawTestData()->first();

        $this->httpClientMock
            ->shouldReceive('get')
            ->with(self::ENDPOINT_FIND, $params, [])
            ->once()
            ->andReturn((object) ['user' => $rawUser]);

        /** @var User $result */
        $result = $this->resource->find($params);

        $this->assertEquals($rawUser->id, $result->id);
    }

    /**
     * @test
     */
    public function find_method_returns_null_if_http_client_throws_an_exception(): void
    {
        $params = new FindUserParams(
            driverCompanyId: $this->faker->text(7)
        );

        $this->httpClientMock
            ->shouldReceive('get')
            ->andThrow(new MotiveClientException());

        /** @var User|null $result */
        $result = $this->resource->find($params);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_searches_multiple_users(): void
    {
        $params = new SearchUsersParams(
            role: UserRole::DRIVER
        );

        $numberOfUsers = $this->faker->randomNumber(2);
        $rawData = UserData::getRawTestData($numberOfUsers);
        $expectedIds = $rawData->map(fn (array $datum) => $datum['id'])->toArray();

        $rawData = $rawData->map(fn (array $datum) => (object) ['user' => (object) $datum]);
        $pagination = [
            'per_page' => 100,
            'page_no' => 1,
            'total' => 1,
        ];

        $this->httpClientMock
            ->shouldReceive('get')
            ->withSomeOfArgs(self::ENDPOINT_SEARCH, $params)
            ->once()
            ->andReturn((object) [
                'users' => $rawData->all(),
                'pagination' => (object) $pagination,
            ]);

        /** @var Collection<int, User> $result */
        $result = $this->resource->search($params);
        $actualIds = $result->pluck('id')->toArray();

        $this->assertEquals($numberOfUsers, $result->count());
        $this->assertEquals($expectedIds, $actualIds);
    }

    /**
     * @test
     */
    public function search_method_returns_all_data_from_multiple_pages_when_pagination_not_set(): void
    {
        $params = new SearchUsersParams(
            role: UserRole::DRIVER
        );

        $perPage = 100;
        $totalPages = 2;

        $rawData = UserData::getRawTestData($perPage);
        $rawData = $rawData->map(fn (array $datum) => (object) ['user' => (object) $datum]);
        $pagination = [
            'per_page' => $perPage,
            'page_no' => 1,
            'total' => $perPage * $totalPages,
        ];

        $this->httpClientMock
            ->shouldReceive('get')
            ->times($totalPages)
            ->andReturn((object) [
                'users' => $rawData->all(),
                'pagination' => (object) $pagination,
            ]);

        /** @var Collection<int, User> $result */
        $result = $this->resource->search($params);

        $this->assertEquals($perPage * $totalPages, $result->count());
    }

    /**
     * @test
     */
    public function search_method_returns_data_from_single_page_when_pagination_is_set(): void
    {
        $params = new SearchUsersParams(
            role: UserRole::DRIVER
        );
        $params->setPage(1);

        $perPage = 100;
        $totalPages = 2;

        $rawData = UserData::getRawTestData($perPage);
        $rawData = $rawData->map(fn (array $datum) => (object) ['user' => (object) $datum]);
        $pagination = [
            'per_page' => $perPage,
            'page_no' => 1,
            'total' => $perPage * $totalPages,
        ];

        $this->httpClientMock
            ->shouldReceive('get')
            ->once()
            ->andReturn((object) [
                'users' => $rawData->all(),
                'pagination' => (object) $pagination,
            ]);

        /** @var Collection<int, User> $result */
        $result = $this->resource->search($params);

        $this->assertEquals($perPage, $result->count());
    }

    private function getTestedResourceClass(): string
    {
        return UsersResource::class;
    }
}
