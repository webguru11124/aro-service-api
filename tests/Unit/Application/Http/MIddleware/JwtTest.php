<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Middleware;

use App\Application\Http\Middleware\Jwt;
use App\Domain\Contracts\FeatureFlagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuthFacade;

class JwtTest extends TestCase
{
    private const JWT_SECRET = 'test-jwt-secret-string-1234567890';

    private Jwt $jwtMiddleware;

    private MockInterface|Response $mockResponse;
    private MockInterface|Request $mockRequest;
    private MockInterface|FeatureFlagService $mockFeatureFlagService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('jwt.secret', self::JWT_SECRET);

        $this->mockRequest = Mockery::mock(Request::class);
        $this->mockResponse = Mockery::mock(JsonResponse::class);
        $this->mockFeatureFlagService = Mockery::mock(FeatureFlagService::class);

        $this->jwtMiddleware = new Jwt($this->mockFeatureFlagService);
    }

    /**
     * @test
     *
     *  ::handle
     */
    public function it_validates_jwt_token_and_checks_permissions(): void
    {
        $this->setFeatureFlagServiceExpectations();
        $this->setJWTAuthExpectation();

        Log::shouldReceive('info');

        $response = $this->jwtMiddleware->handle($this->mockRequest, function ($request) {
            return $this->mockResponse;
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @test
     *
     *  ::handle
     */
    public function it_passes_to_next_middleware_when_invalid_jwt_token_provided(): void
    {
        $this->setFeatureFlagServiceExpectations();
        JWTAuthFacade::shouldReceive('parseToken->authenticate')
            ->andThrow(new TokenInvalidException());

        $response = $this->jwtMiddleware->handle($this->mockRequest, function ($request) {
            return $this->mockResponse;
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @test
     *
     *  ::handle
     */
    public function it_passes_to_next_middleware_when_jwt_is_disabled(): void
    {
        $this->setFeatureFlagServiceExpectations(false);

        $response = $this->jwtMiddleware->handle($this->mockRequest, function ($request) {
            return $this->mockResponse;
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    /**
     * @test
     *
     *  ::handle
     */
    public function it_returns_401_unauthorised_when_jwt_token_is_expired(): void
    {
        $this->setFeatureFlagServiceExpectations();
        JWTAuthFacade::shouldReceive('parseToken->authenticate')
            ->andThrow(new TokenExpiredException());

        $response = $this->jwtMiddleware->handle($this->mockRequest, function ($request) {
            return $this->mockResponse;
        });

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * @test
     *
     *  ::handle
     */
    public function it_returns_401_unauthorised_when_jwt_token_has_invalid_permissions(): void
    {
        $this->setFeatureFlagServiceExpectations();
        $this->setJWTAuthExpectation();

        $response = $this->jwtMiddleware->handle($this->mockRequest, function ($request) {
            return $this->mockResponse;
        });

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    private function setJWTAuthExpectation(): void
    {
        JWTAuthFacade::shouldReceive('parseToken->authenticate')
            ->andReturn(true);
        JWTAuthFacade::shouldReceive('parseToken->getToken->get')
            ->andReturn('token');
        JWTAuthFacade::shouldReceive('parseToken->getPayload->getClaims->all')
            ->andReturn([
                'sub' => null,
                'email' => null,
            ]);
    }

    private function setFeatureFlagServiceExpectations(bool $isEnabled = true): void
    {
        $this->mockFeatureFlagService
            ->shouldReceive('isFeatureEnabled')
            ->once()
            ->withArgs(function (string $featureFlag) {
                return $featureFlag === Jwt::JWT_AUTH_ENABLED_FEATURE_FLAG;
            })
            ->andReturn($isEnabled);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->jwtMiddleware,
            $this->mockRequest,
            $this->mockRequestAttributesBag,
            $this->mockResponse,
            $this->mockFeatureFlagService,
        );
    }
}
