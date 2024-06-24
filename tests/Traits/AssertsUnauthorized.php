<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Testing\TestResponse;

trait AssertsUnauthorized
{
    protected function assertUnauthorizedResponse(
        TestResponse $response,
        string $message = 'Authentication Failed. Api-Key was not found.',
    ): void {
        $response->assertUnauthorized();
        $response->assertJsonPath('_metadata.success', false);
        $response->assertJsonPath('result.message', $message);
    }
}
