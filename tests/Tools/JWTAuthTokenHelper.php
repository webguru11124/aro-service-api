<?php

declare(strict_types=1);

namespace Tests\Tools;

use App\Domain\Contracts\FeatureFlagService;
use DateTimeImmutable;
use Illuminate\Support\Facades\Config;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Mockery;

trait JWTAuthTokenHelper
{
    private const JWT_SECRET = 'test-jwt-secret-string-1234567890';

    /**
     * It generates JWT token with default permissions
     *
     * @return string
     */
    private function generateValidJwtToken(): string
    {
        Config::set('jwt.secret', self::JWT_SECRET);

        $this->mockFeatureFlagService();

        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $signingKey = InMemory::plainText(self::JWT_SECRET);

        $now = new DateTimeImmutable();
        $token = $tokenBuilder
            // Configures the issuer (iss claim)
            ->issuedBy('http://example.com')
            // Configures the audience (aud claim)
            ->permittedFor('http://example.org')
            // Configures the subject of the token (sub claim)
            ->relatedTo('1')
            // Configures the id (jti claim)
            ->identifiedBy('4f1g23a12aa')
            // Configures the time that the token was issue (iat claim)
            ->issuedAt($now)
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('id', 1)
            ->withClaim('group_id', 1)
            // Builds a new token
            ->getToken(new Sha256(), $signingKey);

        return $token->toString();
    }

    private function mockFeatureFlagService(): void
    {
        $mockFeatureFlagService = Mockery::mock(FeatureFlagService::class);
        $mockFeatureFlagService->shouldReceive('isFeatureEnabled')
            ->once()
            ->andReturnTrue();

        $this->app->instance(FeatureFlagService::class, $mockFeatureFlagService);
    }
}
