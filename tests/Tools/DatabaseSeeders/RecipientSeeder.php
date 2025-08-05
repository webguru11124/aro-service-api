<?php

declare(strict_types=1);

namespace Tests\Tools\DatabaseSeeders;

use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecipientSeeder extends Seeder
{
    private const DATA = [
        'name' => ['John', 'Jane', 'Jack', 'Donald'],
        'phone' => ['123456789', '987654321', '123123123', '321321321'],
        'email' => ['john@example.com', 'jane@example.com', 'jack@example.com', 'donald@example.com'],
    ];

    /**
     * Run the database seeds for the recipients table.
     *
     * @return void
     */
    public function run(): void
    {
        $recipients = [];

        foreach (self::DATA['email'] as $key => $email) {
            $recipients[] = [
                'name' => self::DATA['name'][$key],
                'phone' => self::DATA['phone'][$key],
                'email' => $email,
            ];
        }

        DB::table(PostgresDBInfo::NOTIFICATION_RECIPIENTS_TABLE)->insert($recipients);
    }
}
