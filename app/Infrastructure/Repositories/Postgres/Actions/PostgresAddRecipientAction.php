<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Postgres\Actions;

use App\Domain\Notification\Actions\AddRecipientAction;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Support\Facades\DB;

class PostgresAddRecipientAction implements AddRecipientAction
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
    public function execute(string $name, string|null $email, string|null $phone): void
    {
        DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENTS_TABLE)->insert([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ]);
    }
}
