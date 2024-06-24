<?php

declare(strict_types=1);

namespace Tests\Tools\Factories;

use Faker\Generator as Faker;
use Faker\Factory as FakerFactory;

// This class acts as a facade with the static methods contained in it
abstract class AbstractFactory
{
    protected Faker $faker;

    public function __construct() // No need for DI here
    {
        $this->faker = FakerFactory::create();
    }

    /**
     * Create a single domain entity
     *
     * @param array $overrides - An array of key value pairs of attributes to override
     *
     * @return mixed - An entity domain object
     */
    abstract protected function single($overrides = []): mixed;

    // ------- Facade Methods -----------

    /**
     * The Facade method to make a domain entity with fake data
     *
     * @param array $overrides - An array of key value pairs of attributes to override
     *
     * @return mixed - An entity domain object
     */
    public static function make(array $overrides = []): mixed
    {
        $factory = self::createFactory();

        return $factory->single($overrides);
    }

    /**
     * The Facade method to make many domain entities with fake data
     *
     * @param int $number - The number of domain entities to make
     * @param array $overrides - An array of key value pairs of attributes to override
     *
     * @return array - An array of domain entities
     */
    public static function many(int $number, array $overrides = []): array
    {
        $factory = self::createFactory();

        $entities = [];
        for($i = 0 ; $i < $number; $i++) {
            $entities[] = $factory->single($overrides);
        }

        return $entities;
    }

    private static function createFactory(): AbstractFactory
    {
        return new static();
    }
}
