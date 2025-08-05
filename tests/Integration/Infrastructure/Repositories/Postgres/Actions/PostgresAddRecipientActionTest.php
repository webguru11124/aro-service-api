<?php

declare(strict_types=1);

namespace Integration\Infrastructure\Repositories\Postgres\Actions;

use App\Infrastructure\Repositories\Postgres\Actions\PostgresAddRecipientAction;
use App\Infrastructure\Repositories\Postgres\Scopes\PostgresDBInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostgresAddRecipientActionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function it_executes_action(): void
    {
        $name = $this->faker->name;
        $email = $this->faker->email;
        $phone = $this->faker->phoneNumber;

        $action = new PostgresAddRecipientAction();
        $action->execute($name, $email, $phone);

        $this->assertDatabaseHas(PostgresDBInfo::NOTIFICATION_RECIPIENTS_TABLE, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ]);
    }
}
