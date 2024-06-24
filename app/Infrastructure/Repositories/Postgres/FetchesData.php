<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

trait FetchesData
{
    public function findAll(): Collection
    {
        $rawObjects = $this->getQueryBuilder()->get();

        return $this->handleRawObjects($rawObjects);
    }

    /**
     * @param Collection<\stdClass> $fetchedObjects
     *
     * @return Collection<object>
     */
    protected function mapEntities(Collection $fetchedObjects): Collection
    {
        return $fetchedObjects->map(fn (\stdClass $object) => $this->mapEntity($object));
    }

    abstract protected function mapEntity(\stdClass $databaseObject): object;

    abstract protected function getQueryBuilder(): Builder;

    /**
     * @param Collection<\stdClass> $rawObjects
     *
     * @return Collection<object>
     */
    abstract protected function handleRawObjects(Collection $rawObjects): Collection;
}
