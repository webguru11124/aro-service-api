<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Workday\Factories;

use Carbon\Carbon;
use Tests\TestCase;
use Tests\Tools\TestValue;
use Illuminate\Support\Facades\Config;
use App\Infrastructure\Services\Workday\Factories\WorkdayJWTTokenFactory;

class WorkdayJWTTokenFactoryTest extends TestCase
{
    private const OPENSSL_VERIFICATION_SUCCESS = 1;
    private const JWT_SEPARATOR = '.';

    private const URL_SAFE_BASE64_DECODE_REPLACEMENTS = [
        '-' => '+',
        '_' => '/',
    ];

    private const JWT_REQUIRED_KEYS = [
        'iss',
        'sub',
        'aud',
        'exp',
    ];
    private const JWT_REQUIRED_HEADER = '{"typ":"JWT","alg":"RS256"}';

    private string $privateKeyString;
    private string $publicKeyString;
    private WorkdayJWTTokenFactory $workdayJWTTokenFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->privateKeyString = $this->generatePrivateKey();
        $this->publicKeyString = $this->extractPublicKey($this->privateKeyString);

        $this->workdayJWTTokenFactory = new WorkdayJWTTokenFactory();

        Config::set('workday.auth.client_id', TestValue::WORKDAY_VALID_CLIENT_KEY);
        Config::set('workday.auth.isu_username', TestValue::WORKDAY_VALID_ISU_USERNAME);
        Config::set('workday.auth.private_key', $this->privateKeyString);
    }

    private function generatePrivateKey(): string
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($privateKey, $privateKeyString);

        return $privateKeyString;
    }

    private function extractPublicKey(string $privateKeyString): string
    {
        $privateKeyResource = openssl_pkey_get_private($privateKeyString);
        $details = openssl_pkey_get_details($privateKeyResource);

        return $details['key'];
    }

    /**
     * @test ::make
     */
    public function it_creates_valid_signed_jwt_token(): void
    {
        $jwtToken = $this->workdayJWTTokenFactory->make(
            TestValue::WORKDAY_VALID_CLIENT_KEY,
            TestValue::WORKDAY_VALID_ISU_USERNAME,
            $this->privateKeyString,
        );

        $jwtSegments = explode(self::JWT_SEPARATOR, $jwtToken);

        $this->assertIsString($jwtToken);
        $this->assertCount(3, $jwtSegments);

        $this->verifyTokenHeader($jwtSegments[0]);
        $this->verifyTokenPayload($jwtSegments[1]);
        $this->verifyTokenSignature($jwtSegments);
    }

    private function verifyTokenHeader(string $header): void
    {
        $this->assertEquals(self::JWT_REQUIRED_HEADER, base64_decode($header));
    }

    private function verifyTokenSignature(array $jwtSegments): void
    {
        $stringToVerify = $jwtSegments[0] . '.' . $jwtSegments[1];
        $signature = $this->urlSafeBase64Decode($jwtSegments[2]);

        $this->assertSame(
            self::OPENSSL_VERIFICATION_SUCCESS,
            openssl_verify(
                $stringToVerify,
                $signature,
                $this->publicKeyString,
                OPENSSL_ALGO_SHA256,
            )
        );
    }

    private function urlSafeBase64Decode(string $data): string
    {
        return base64_decode(
            strtr(
                $data,
                self::URL_SAFE_BASE64_DECODE_REPLACEMENTS,
            )
        );
    }

    private function verifyTokenPayload(string $payload): void
    {
        $jwtClaims = json_decode(
            base64_decode($payload),
            true,
        );

        foreach (self::JWT_REQUIRED_KEYS as $key) {
            $this->assertArrayHasKey($key, $jwtClaims);
        }

        $this->assertEquals(TestValue::WORKDAY_VALID_CLIENT_KEY, $jwtClaims['iss']);
        $this->assertEquals(TestValue::WORKDAY_VALID_ISU_USERNAME, $jwtClaims['sub']);
        $this->assertEquals('wd', $jwtClaims['aud']);
        $this->assertGreaterThan(Carbon::now()->timestamp, $jwtClaims['exp']);
    }
}
