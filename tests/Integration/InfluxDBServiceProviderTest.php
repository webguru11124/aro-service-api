<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application\Providers\InfluxDBClientProvider;
use Illuminate\Support\Facades\App;
use InfluxDB2\Client as InfluxClient;
use Tests\TestCase;

/**
 * @coversDefaultClass InfluxDBClientProvider
 */
class InfluxDBServiceProviderTest extends TestCase
{
    /**
     * @test
     */
    public function it_resolves_configcat_client_as_a_singleton()
    {
        $client1 = App::make(InfluxClient::class);
        $client2 = App::make(InfluxClient::class);

        $this->assertSame($client1, $client2);
    }
}
