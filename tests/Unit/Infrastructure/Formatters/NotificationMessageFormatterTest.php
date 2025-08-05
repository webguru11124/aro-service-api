<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Formatters;

use Carbon\Carbon;
use Tests\TestCase;
use App\Infrastructure\Formatters\NotificationMessageFormatter;

class NotificationMessageFormatterTest extends TestCase
{
    private NotificationMessageFormatter $formatter;
    private Carbon $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->date = Carbon::parse('2024-01-01');
        $this->formatter = new NotificationMessageFormatter();
    }

    /**
     * @test
     */
    public function it_format_email_message_correctly(): void
    {
        $officeScores = [
            [
                'name' => 'Office 1',
                'score' => 0.8,
            ],
            [
                'name' => 'Office 2',
                'score' => 0.6,
            ],
            [
                'name' => 'Office 3',
                'score' => 0.9,
            ],
        ];

        $expected = '<p>Date: 01/01/2024<br>Company Average = 77%<br>Office 3 = 90%<br>Office 1 = 80%<br>Office 2 = 60%<br></p>';

        $this->assertEquals($expected, $this->formatter->format($this->date, $officeScores, 'email'));
    }

    /**
     * @test
     */
    public function it_format_slack_message_correctly(): void
    {
        $officeScores = [
            [
                'name' => 'Office 1',
                'score' => 0.8,
            ],
            [
                'name' => 'Office 2',
                'score' => 0.6,
            ],
            [
                'name' => 'Office 3',
                'score' => 0.9,
            ],
        ];

        $expected = 'Date: 01/01/2024' . PHP_EOL
            . 'Company Average = 77%' . PHP_EOL
            . 'Office 3 = 90%' . PHP_EOL
            . 'Office 1 = 80%' . PHP_EOL
            . 'Office 2 = 60%';

        $this->assertEquals($expected, $this->formatter->format($this->date, $officeScores, 'slack'));
    }
}
