<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Workday;

use App\Infrastructure\Services\Workday\Helpers\WorkdayXmlHelper;
use Illuminate\Support\Facades\Http;
use App\Infrastructure\Services\Workday\Exceptions\WorkdayErrorException;
use App\Infrastructure\Services\Workday\Factories\WorkdayAccessTokenFactory;

class WorkdayAPIClient
{
    private string $token = '';
    private const string WORKDAY_JSON_RESPONSE_FORMAT = 'json';

    public function __construct(
        private WorkdayAccessTokenFactory $tokenFactory
    ) {
    }

    /**
     * This method to call Workday API by POST method
     *
     * @param string $url
     * @param array<string, mixed> $params
     *
     * @return mixed
     * @throws WorkdayErrorException
     */
    public function post(string $url, array $params): mixed
    {
        $response = Http::withHeaders($this->getAuthorizationHeadersData())
            ->send('POST', $url, $params);

        if ($response->failed()) {
            $responseError = WorkdayXmlHelper::getErrorFromXMLResponse($response->body());

            throw new WorkdayErrorException($responseError);
        }

        $body = $response->body();

        if (empty($body)) {
            throw new WorkdayErrorException(__('messages.workday.empty_response'));
        }

        return $body;
    }

    /**
     * @return string[]
     * @throws WorkdayErrorException
     */
    private function getAuthorizationHeadersData(): array
    {
        if (empty($this->token)) {
            $this->token = $this->tokenFactory->make(
                config('workday.auth.client_id'),
                config('workday.auth.isu_username'),
                config('workday.auth.private_key'),
            );
        }

        return [
            'Authorization' => 'Bearer ' . $this->token,
        ];
    }

    /**
     * @param string $url
     * @param mixed[] $params
     *
     * @return array<string, mixed>
     * @throws WorkdayErrorException
     */
    public function get(string $url, array $params): array
    {
        $params['format'] = self::WORKDAY_JSON_RESPONSE_FORMAT;
        $requestUrl = $url . '?' . http_build_query($params);
        $response = Http::withHeaders($this->getAuthorizationHeadersData())
            ->timeout(60)
            ->send('GET', $requestUrl);

        if ($response->failed()) {
            $responseError = WorkdayXmlHelper::getErrorFromXMLResponse($response->body());

            throw new WorkdayErrorException($responseError);
        }

        $body = $response->body();

        if (empty($body)) {
            throw new WorkdayErrorException(__('messages.workday.empty_response'));
        }

        return json_decode($body, true);
    }
}
