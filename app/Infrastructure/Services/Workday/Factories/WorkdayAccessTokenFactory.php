<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Workday\Factories;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayErrorException;

class WorkdayAccessTokenFactory
{
    public const ACCESS_TOKEN_CACHE_KEY = 'workday_access_token';
    private const GRANT_TYPE_JWT = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    public function __construct(
        private WorkdayJWTTokenFactory $jwtTokenFactory
    ) {
    }

    /**
     * Returns access token for workday REST API authorization
     *
     * @param string $workdayClientId
     * @param string $workdayUsername
     * @param string $jwtTokenPrivateKey
     *
     * @return string
     * @throws WorkdayErrorException
     */
    public function make(string $workdayClientId, string $workdayUsername, string $jwtTokenPrivateKey): string
    {
        $accessToken = Cache::get(self::ACCESS_TOKEN_CACHE_KEY);
        if (!empty($accessToken)) {
            return $accessToken;
        }

        $jwtToken = $this->jwtTokenFactory->make(
            $workdayClientId,
            $workdayUsername,
            $jwtTokenPrivateKey,
        );

        $accessToken = $this->getAccessTokenByJWT($jwtToken);

        Cache::put(self::ACCESS_TOKEN_CACHE_KEY, $accessToken, $this->getCachePeriod());

        return $accessToken;
    }

    private function getCachePeriod(): int
    {
        return config('workday.auth.access_token_cached_for_seconds');
    }

    /**
     * @throws WorkdayErrorException
     */
    private function getAccessTokenByJWT(string $jwtToken): string
    {
        $response = $this->getAccessTokenResponse($jwtToken)->body();
        $responseJson = json_decode(
            $response,
            true,
        );

        if (isset($responseJson['error'])) {
            throw new WorkdayErrorException($responseJson['error']);
        }

        if (empty($responseJson['access_token'])) {
            throw new WorkdayErrorException(__('message.workday.invalid_access_token'));
        }

        return $responseJson['access_token'];
    }

    private function getAccessTokenResponse(string $jwtToken): Response
    {
        return Http::send(
            'POST',
            config('workday.auth.access_token_url'),
            [
                'body' => http_build_query([
                    'grant_type' => self::GRANT_TYPE_JWT,
                    'assertion' => $jwtToken,
                ]),
            ]
        );
    }
}
