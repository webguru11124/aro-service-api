<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Workday\Factories;

use Carbon\Carbon;

class WorkdayJWTTokenFactory
{
    private const ACCESS_TOKEN_EXPIRES_IN_SECONDS = 300;
    private const WORKDAY_AUD_VALUE = 'wd';
    private const JWT_SEPARATOR = '.';
    private const URL_SAFE_BASE64_REPLACEMENTS = [
        '+' => '-',
        '/' => '_',
        '\r' => '',
        '\n' => '',
        '=' => '',
    ];
    private const JWT_TOKEN_HEADER_PARAMETERS = [
        'typ' => 'JWT',
        'alg' => 'RS256',
    ];

    /**
     * Returns signed JWT token for workday REST API authorization
     *
     * @param string $workdayClientId
     * @param string $workdayUsername
     * @param string $jwtTokenPrivateKey
     *
     * @return string
     */
    public function make(string $workdayClientId, string $workdayUsername, string $jwtTokenPrivateKey): string
    {
        $claims = $this->getJWTClaims($workdayClientId, $workdayUsername);
        $token = $this->getJWTToken($claims);

        openssl_sign(
            $token,
            $signature,
            $jwtTokenPrivateKey,
            OPENSSL_ALGO_SHA256,
        );

        return $token . self::JWT_SEPARATOR . $this->urlSafeBase64Encode($signature);
    }

    private function getJWTClaims(string $workdayClientId, string $workdayUsername): string
    {
        $currentTimestamp = Carbon::now()->timestamp;

        return json_encode([
            'iss' => $workdayClientId,
            'sub' => $workdayUsername,
            'aud' => self::WORKDAY_AUD_VALUE,
            'exp' => (string) ($currentTimestamp + self::ACCESS_TOKEN_EXPIRES_IN_SECONDS),
        ]);
    }

    private function getJWTToken(string $jwtClaims): string
    {
        $header = json_encode(self::JWT_TOKEN_HEADER_PARAMETERS);

        return $this->urlSafeBase64Encode($header)
            . self::JWT_SEPARATOR
            . $this->urlSafeBase64Encode($jwtClaims);
    }

    private function urlSafeBase64Encode(string $data): string
    {
        return strtr(
            base64_encode($data),
            self::URL_SAFE_BASE64_REPLACEMENTS,
        );
    }
}
