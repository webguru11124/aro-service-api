<?php

declare(strict_types=1);

namespace App\Infrastructure\Formatters;

use Exception;
use Carbon\CarbonInterface;

class NotificationMessageFormatter implements NotificationFormatter
{
    private const DATE_FORMAT = 'm/d/Y';
    private const EMAIL_NEWLINE = '<br>';
    private const SLACK_NEWLINE = PHP_EOL;
    private CarbonInterface $date;
    /** @var array<int, array<string, string|float>> */
    private array $officeScores;

    /**
     * @param CarbonInterface $date
     * @param array<int, array<string, string|float>> $officeScores
     * @param string $type
     *
     * @return string
     * @throws Exception
     *
     * TODO: Consider to use a different approach to format the message body
     * The better approach is to create an entity of office score with all the necessary properties and methods to
     *     format the message body This will make the code more readable and maintainable. In addition, we can separate
     *     the the formatters of email and slack into different classes.
     */
    public function format(CarbonInterface $date, array $officeScores, string $type): string
    {
        $this->date = $date;
        $this->officeScores = $officeScores;

        switch ($type) {
            case 'email':
                return $this->wrapMessageBodyInPTag($this->formatMessageBody(self::EMAIL_NEWLINE));
            case 'slack':
                return $this->formatMessageBody(self::SLACK_NEWLINE);
            default:
                throw new Exception('Invalid message type');
        }
    }

    /**
     * @param string $newlineCharacter
     *
     * @return string
     */
    private function formatMessageBody(string $newlineCharacter): string
    {
        $date = $this->date->format(self::DATE_FORMAT);
        $officeScores = $this->officeScores;
        $companyAverage = $this->calculateCompanyAverage($officeScores);

        $messageBody = 'Date: '
            . $date
            . $newlineCharacter
            . 'Company Average = '
            . $companyAverage
            . '%'
            . $newlineCharacter;

        uasort($officeScores, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        foreach ($officeScores as $officeId => $officeData) {
            $messageBody .= $officeData['name'] . ' = ' . $officeData['score'] * 100 . '%' . $newlineCharacter;
        }

        return rtrim($messageBody);
    }

    private function wrapMessageBodyInPTag(string $message): string
    {
        return '<p>' . $message . '</p>';
    }

    /**
     * @param array<int, array<string, string|float>> $officeScores
     *
     * @return int
     */
    private function calculateCompanyAverage(array $officeScores): int
    {
        $totalScore = array_sum(array_column($officeScores, 'score'));
        $count = count($officeScores);

        $average = $count > 0 ? round($totalScore / $count, 2) : 0;

        return (int) ($average * 100);
    }
}
