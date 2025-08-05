<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources;

interface PaginationParams extends HttpParams
{
    public function setPage(int $page): static;

    public function setPerPage(int $perPage): static;

    public function isPaginationSet(): bool;

    public function getPage(): int|null;

    public function getPerPage(): int|null;
}
