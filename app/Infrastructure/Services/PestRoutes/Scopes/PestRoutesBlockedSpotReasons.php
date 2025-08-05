<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\PestRoutes\Scopes;

class PestRoutesBlockedSpotReasons
{
    public const string BREAK_MARKER = 'break';
    public const string LUNCH_MARKER = 'lunch';
    public const string SUMMARY_MARKER = 'summary';
    public const string CALENDAR_EVENT_MARKER = 'event';

    private const string INSIDE_SALES_1 = 'inside sale';
    private const string INSIDE_SALES_2 = 'reserved i.s.';

    public const array PROCESSABLE_ON_OPTIMIZATION = [
        self::BREAK_MARKER,
        self::LUNCH_MARKER,
        self::SUMMARY_MARKER,
        'aro',
        'blocked by ara',
        self::INSIDE_SALES_1,
        self::INSIDE_SALES_2,
        'event',
    ];

    public const array PROCESSABLE_ON_SCHEDULING = [
        self::BREAK_MARKER,
        self::LUNCH_MARKER,
        self::SUMMARY_MARKER,
        self::INSIDE_SALES_1,
        self::INSIDE_SALES_2,
    ];

    public const array INSIDE_SALES_MARKERS = [
        self::INSIDE_SALES_1,
        self::INSIDE_SALES_2,
    ];
}
