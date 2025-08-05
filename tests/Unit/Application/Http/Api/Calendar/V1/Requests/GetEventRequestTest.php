<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Api\Calendar\V1\Requests\GetEventRequest;
use Illuminate\Http\Request;
use Tests\Tools\AbstractRequestTest;
use Tests\Tools\TestValue;

class GetEventRequestTest extends AbstractRequestTest
{
    public function getTestedRequest(): Request
    {
        return new GetEventRequest();
    }

    public static function getInvalidData(): array
    {
        return [
            'office_id_must_be_an_integer' => [
                [
                    'office_id' => 'test',
                ],
            ],
            'office_id_must_greater_than_0' => [
                [
                    'office_id' => 0,
                ],
            ],
            'start_date_must_have_date_format' => [
                [
                    'start_date' => 'non date',
                    'office_id' => 1,
                ],
            ],
            'end_date_must_have_date_format' => [
                [
                    'end_date' => 'non date',
                    'office_id' => 1,
                ],
            ],
        ];
    }

    public static function getValidData(): array
    {
        return [
            'valid_request_data' => [
                [
                    'office_id' => 82,
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_trims_search_text(): void
    {
        $rawText = '   search text   ';
        $handledText = 'search text';

        $params = [
            'office_id' => TestValue::OFFICE_ID,
            'search_text' => $rawText,
        ];

        $request = new GetEventRequest($params);
        $request->setValidator($this->makeValidator($params, $this->rules));
        $request->validateResolved();

        $this->assertEquals($handledText, $request->search_text);
    }
}
