<?php

declare(strict_types=1);

namespace App\Domain\Notification\Queries;

use App\Domain\Notification\Entities\Recipient;
use Illuminate\Support\Collection;

interface RecipientsQueryInterface
{
    /**
     * @return Collection<Recipient>
     */
    public function get(): Collection;
}
