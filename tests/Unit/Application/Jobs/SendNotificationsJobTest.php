<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Jobs;

use App\Application\Jobs\SendNotificationsJob;
use App\Domain\Contracts\Repositories\FleetRouteStateRepository;
use App\Domain\Notification\Queries\OptimizationScoreRecipientsQuery;
use App\Domain\Tracking\Entities\FleetRouteState;
use App\Domain\Tracking\ValueObjects\OptimizationStateMetrics;
use App\Infrastructure\Formatters\NotificationFormatter;
use App\Infrastructure\Services\Notification\EmailNotification\EmailNotificationService;
use App\Infrastructure\Services\Notification\SlackNotification\SlackNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Tests\Tools\Factories\Notification\RecipientFactory;
use Tests\Tools\Factories\OfficeFactory;
use Tests\Tools\Factories\Tracking\FleetRouteStateFactory;

class SendNotificationsJobTest extends TestCase
{
    private Carbon $date;
    private Collection $offices;
    private SendNotificationsJob $job;
    private FleetRouteStateRepository $mockFleetRouteStateRepository;
    private EmailNotificationService $mockEmailNotificationService;
    private OptimizationScoreRecipientsQuery $mockOptimizationScoreRecipientsQuery;
    private SlackNotificationService $mockSlackNotificationService;
    private NotificationFormatter $mockNotificationFormatter;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->date = Carbon::today();
        $this->offices = collect(OfficeFactory::many(2));

        $this->job = new SendNotificationsJob($this->date, $this->offices);
        $this->mockFleetRouteStateRepository = Mockery::mock(FleetRouteStateRepository::class);
        $this->mockEmailNotificationService = Mockery::mock(EmailNotificationService::class);
        $this->mockOptimizationScoreRecipientsQuery = Mockery::mock(OptimizationScoreRecipientsQuery::class);
        $this->mockSlackNotificationService = Mockery::mock(SlackNotificationService::class);
        $this->mockNotificationFormatter = Mockery::mock(NotificationFormatter::class);
    }

    /**
     * @test
     */
    public function it_sends_notifications_to_recipients(): void
    {
        $recipients = collect([RecipientFactory::make(['email' => 'test@test.test'])]);

        $optimizationStateMetrics = new OptimizationStateMetrics(
            optimizationScore: 100,
        );

        $mockFleetRouteState = Mockery::mock(FleetRouteState::class);
        $mockFleetRouteState->shouldReceive('getFleetRoutes')
            ->andReturn(collect(FleetRouteStateFactory::many(2)));
        $mockFleetRouteState->shouldReceive('getMetrics')
            ->andReturn($optimizationStateMetrics);

        $this->mockFleetRouteStateRepository
            ->shouldReceive('findByOfficeIdAndDate')
            ->andReturn($mockFleetRouteState);

        $this->mockOptimizationScoreRecipientsQuery
            ->shouldReceive('get')
            ->once()
            ->andReturn($recipients);

        $this->mockEmailNotificationService
            ->shouldReceive('send')
            ->once();

        $this->mockSlackNotificationService
            ->shouldReceive('send')
            ->once();

        $this->mockNotificationFormatterResponses();

        Log::shouldReceive('info')->twice();

        $this->job->handle(
            $this->mockFleetRouteStateRepository,
            $this->mockEmailNotificationService,
            $this->mockOptimizationScoreRecipientsQuery,
            $this->mockSlackNotificationService,
            $this->mockNotificationFormatter,
        );
    }

    /**
     * @test
     */
    public function it_does_not_send_notifications_when_no_offices(): void
    {
        $this->job = new SendNotificationsJob($this->date, collect());

        $this->mockFleetRouteStateRepository
            ->shouldNotReceive('findByOfficeIdAndDate');

        $this->mockOptimizationScoreRecipientsQuery
            ->shouldNotReceive('get');

        $this->mockEmailNotificationService
            ->shouldNotReceive('send');

        $this->mockSlackNotificationService
            ->shouldNotReceive('send');

        Log::shouldReceive('notice')->once();

        $this->job->handle(
            $this->mockFleetRouteStateRepository,
            $this->mockEmailNotificationService,
            $this->mockOptimizationScoreRecipientsQuery,
            $this->mockSlackNotificationService,
            $this->mockNotificationFormatter,
        );
    }

    /**
     * @test
     */
    public function it_does_not_send_email_notifications_when_no_recipients(): void
    {
        $optimizationStateMetrics = new OptimizationStateMetrics(
            optimizationScore: 100,
        );

        $mockFleetRouteState = Mockery::mock(FleetRouteState::class);
        $mockFleetRouteState->shouldReceive('getFleetRoutes')
            ->andReturn(collect(FleetRouteStateFactory::many(2)));
        $mockFleetRouteState->shouldReceive('getMetrics')
            ->andReturn($optimizationStateMetrics);

        $this->mockFleetRouteStateRepository
            ->shouldReceive('findByOfficeIdAndDate')
            ->andReturn($mockFleetRouteState);

        $this->mockOptimizationScoreRecipientsQuery
            ->shouldReceive('get')
            ->once()
            ->andReturn(new Collection());

        $this->mockEmailNotificationService
            ->shouldNotReceive('send');

        $this->mockSlackNotificationService
            ->shouldReceive('send')
            ->once();

        $this->mockNotificationFormatterResponses();

        Log::shouldReceive('notice')->once();
        Log::shouldReceive('info')->once();

        $this->job->handle(
            $this->mockFleetRouteStateRepository,
            $this->mockEmailNotificationService,
            $this->mockOptimizationScoreRecipientsQuery,
            $this->mockSlackNotificationService,
            $this->mockNotificationFormatter,
        );
    }

    /**
     * @test
     */
    public function it_logs_error_when_from_email_not_set(): void
    {
        $recipients = collect([RecipientFactory::make(['email' => 'test@test.test'])]);

        $optimizationStateMetrics = new OptimizationStateMetrics(
            optimizationScore: 100,
        );

        $mockFleetRouteState = Mockery::mock(FleetRouteState::class);
        $mockFleetRouteState->shouldReceive('getFleetRoutes')
            ->andReturn(collect(FleetRouteStateFactory::many(2)));
        $mockFleetRouteState->shouldReceive('getMetrics')
            ->andReturn($optimizationStateMetrics);

        $this->mockFleetRouteStateRepository
            ->shouldReceive('findByOfficeIdAndDate')
            ->andReturn($mockFleetRouteState);

        $this->mockOptimizationScoreRecipientsQuery
            ->shouldReceive('get')
            ->once()
            ->andReturn($recipients);

        config(['notification-service.recipients.from_email' => null]);

        $this->mockEmailNotificationService
            ->shouldNotReceive('send');

        $this->mockSlackNotificationService
            ->shouldReceive('send')
            ->once();

        $this->mockNotificationFormatterResponses();

        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->once();

        $this->job->handle(
            $this->mockFleetRouteStateRepository,
            $this->mockEmailNotificationService,
            $this->mockOptimizationScoreRecipientsQuery,
            $this->mockSlackNotificationService,
            $this->mockNotificationFormatter,
        );
    }

    /**
     * @test
     */
    public function it_logs_error_when_send_slack_notification_failed(): void
    {
        $recipients = collect([RecipientFactory::make(['email' => 'test@test.test'])]);

        $optimizationStateMetrics = new OptimizationStateMetrics(
            optimizationScore: 100,
        );

        $mockFleetRouteState = Mockery::mock(FleetRouteState::class);
        $mockFleetRouteState->shouldReceive('getFleetRoutes')
            ->andReturn(collect(FleetRouteStateFactory::many(2)));
        $mockFleetRouteState->shouldReceive('getMetrics')
            ->andReturn($optimizationStateMetrics);

        $this->mockFleetRouteStateRepository
            ->shouldReceive('findByOfficeIdAndDate')
            ->andReturn($mockFleetRouteState);

        $this->mockOptimizationScoreRecipientsQuery
            ->shouldReceive('get')
            ->once()
            ->andReturn($recipients);

        $this->mockEmailNotificationService
            ->shouldReceive('send')
            ->once();

        $exception = new \Exception('Slack notification failed');
        $this->mockSlackNotificationService
            ->shouldReceive('send')
            ->andThrowExceptions([$exception]);

        $this->mockNotificationFormatterResponses();

        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->once();

        $this->job->handle(
            $this->mockFleetRouteStateRepository,
            $this->mockEmailNotificationService,
            $this->mockOptimizationScoreRecipientsQuery,
            $this->mockSlackNotificationService,
            $this->mockNotificationFormatter,
        );
    }

    private function mockNotificationFormatterResponses(): void
    {
        $this->mockNotificationFormatter
            ->shouldReceive('format')
            ->twice()
            ->andReturn('Notification content');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->date,
            $this->offices,
            $this->job,
            $this->mockFleetRouteStateRepository,
            $this->mockEmailNotificationService,
            $this->mockOptimizationScoreRecipientsQuery,
            $this->mockSlackNotificationService,
            $this->mockNotificationFormatter,
        );
    }
}
