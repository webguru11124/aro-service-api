<?php

declare(strict_types=1);

namespace App\Domain\Notification\Actions;

interface AddRecipientAction
{
    /**
     * Subscribe recipient to a notification channel
     *
     * @param string $name
     * @param string|null $email
     * @param string|null $phone
     *
     * @return void
     */
    public function execute(string $name, string|null $email, string|null $phone): void;
}
