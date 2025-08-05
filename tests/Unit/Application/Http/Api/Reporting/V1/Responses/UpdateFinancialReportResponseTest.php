<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Reporting\V1\Responses;

use App\Application\Http\Api\Reporting\V1\Responses\UpdateFinancialReportResponse;
use Aptive\Component\Http\HttpStatus;
use Tests\TestCase;
use Tests\Traits\AssertArrayHasAllKeys;

class UpdateFinancialReportResponseTest extends TestCase
{
    use AssertArrayHasAllKeys;

    /**
     * @test
     */
    public function it_creates_correct_response(): void
    {
        $response = new UpdateFinancialReportResponse();

        $this->assertEquals(HttpStatus::ACCEPTED, $response->getStatusCode());
        $this->assertArrayHasAllKeys([
            '_metadata' => [
                'success',
            ],
            'result' => ['message'],
        ], $response->getData(true));
    }
}
