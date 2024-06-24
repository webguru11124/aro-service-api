<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Tracking\V1\Controllers;

use App\Infrastructure\Queries\Static\Office\StaticGetAllOfficesQuery;
use Exception;
use Tests\TestCase;
use Tests\Tools\JWTAuthTokenHelper;

class OfficesControllerTest extends TestCase
{
    use JWTAuthTokenHelper;

    private const ROUTE_NAME = 'tracking.offices.index';

    /** @var string[] */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->generateValidJwtToken(),
        ];
    }

    /**
     * @test
     */
    public function it_returns_200_on_success(): void
    {
        $response = $this
            ->withHeaders($this->getHeaders())
            ->getJson(route(self::ROUTE_NAME));

        $response->assertOk();
        $response->assertJsonStructure([
            '_metadata' => ['success'],
        ]);
    }

    /**
     * @test
     */
    public function it_returns_500_on_get_offices_query_exception(): void
    {
        $officesQueryMock = $this->createMock(StaticGetAllOfficesQuery::class);
        $officesQueryMock->method('get')->willThrowException(new Exception('No offices found'));

        $this->instance(StaticGetAllOfficesQuery::class, $officesQueryMock);

        $response = $this
            ->withHeaders($this->getHeaders())
            ->getJson(route(self::ROUTE_NAME));

        $response->assertInternalServerError();
        $response->assertJsonStructure([
            '_metadata' => ['success'],
        ]);
    }

    private function getHeaders(): array
    {
        return $this->headers;
    }
}
