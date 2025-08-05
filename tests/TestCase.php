<?php

declare(strict_types=1);

namespace Tests;

use Faker\Factory as FakerFactory;
use Faker\Generator as Faker;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\CreatesApplication;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected Faker $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = FakerFactory::create();
    }

    // Run after each test class to force garbage collection and cleanup to free memory
    public static function tearDownAfterClass(): void
    {
        gc_collect_cycles();
    }
}
