<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Motive\Client\Resources;

trait PaginationAware
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_PER_PAGE = 100;

    protected int|null $page = null;
    protected int|null $perPage = null;

    /**
     * @param int $page
     *
     * @return $this
     */
    public function setPage(int $page): static
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @param int $perPage
     *
     * @return $this
     */
    public function setPerPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPage(): int|null
    {
        return $this->page;
    }

    /**
     * @return int|null
     */
    public function getPerPage(): int|null
    {
        return $this->perPage;
    }

    /**
     * @return bool
     */
    public function isPaginationSet(): bool
    {
        return $this->page !== null;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    protected function withPagination(array $params): array
    {
        return array_merge($params, [
            'page_no' => $this->page ?: self::DEFAULT_PAGE,
            'per_page' => $this->perPage ?: self::DEFAULT_PER_PAGE,
        ]);
    }
}
