<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\TestCase;

class EnvironmentVariableIntegrityTest extends TestCase
{
    private const REQUIRED_ENV_VARIABLES = [
        'APP_NAME',
        'APP_ENV',
        'APP_KEY',
        'APP_DEBUG',
        'APP_URL',

        'VROOM_URL',

        'LOG_CHANNEL',

        'PESTROUTES_URL',
        'DYNAMO_DB_OFFICE_CREDENTIALS_TABLENAME',

        'QUEUE_CONNECTION',
        'SQS_PREFIX',

        'DB_CONNECTION',
        'DB_HOST',
        'DB_PORT',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',

        'INFLUXDB_HOST',
        'INFLUXDB_ORGANIZATION',
        'INFLUXDB_BUCKET',
        'INFLUXDB_TOKEN',

        'CONFIGCAT_SDK_KEY',

        'NOTIFICATION_SERVICE_API_URL',
        'NOTIFICATION_SERVICE_API_BEARER_TOKEN',
        'MOTIVE_API_KEY',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(config('app.env'), ['local', 'testing'])) {
            $this->markTestSkipped('It only makes sense to run this test as part of deployment into a real environment');
        }
    }

    /**
     * @test
     */
    public function it_should_fail_if_required_environment_variable_is_missing(): void
    {
        foreach (self::REQUIRED_ENV_VARIABLES as $variable) {
            $this->assertNotNull(env($variable), sprintf('Environment variable %s is missing', $variable));
            $this->assertNotEquals('', env($variable), sprintf('Value of environment variable %s is not set', $variable));
        }
    }
}
