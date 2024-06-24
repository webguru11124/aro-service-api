<?php

declare(strict_types=1);

namespace Tests\Integration\Application;

use ConfigCat\ConfigCatClient;
use ConfigCat\ClientOptions;
use ConfigCat\Log\LogLevel;
use ConfigCat\Override\FlagOverrides;
use ConfigCat\Override\OverrideDataSource;
use ConfigCat\Override\OverrideBehaviour;

use Psr\Log\LoggerInterface;

trait FakeConfigCatClient
{
    private static ConfigCatClient|null $localClient = null;

    protected function getFakeConfigCatClient(bool $refreshInstance = false): ConfigCatClient|null
    {
        if (self::$localClient === null || $refreshInstance === true) {
            self::$localClient = new ConfigCatClient(
                'fake_sdk_key',
                [
                    ClientOptions::LOG_LEVEL => LogLevel::INFO,
                    ClientOptions::LOGGER => app(LoggerInterface::class),
                    ClientOptions::FLAG_OVERRIDES => new FlagOverrides(
                        OverrideDataSource::localFile('tests/Tools/Factories/ConfigCat/local_flags.json'),
                        OverrideBehaviour::LOCAL_ONLY,
                    ),
                ]
            );
        }

        return self::$localClient;
    }
}
