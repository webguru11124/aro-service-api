<?php

declare(strict_types=1);

namespace Tests\Integration\Application;

use Tests\TestCase;

/**
 * @coversDefaultClass ConfigCatClientProvider
 */
class ConfigCatClientTest extends TestCase
{
    use FakeConfigCatClient;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function it_resolves_configcat_client_as_a_singleton()
    {
        $client = $this->getFakeConfigCatClient();
        $client2 = $this->getFakeConfigCatClient();

        $this->assertSame($client, $client2);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
