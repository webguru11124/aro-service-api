<?php

declare(strict_types=1);

namespace App\Application\Http\Web\Notifications\Controllers;

use App\Application\Http\Web\Notifications\Requests\AddRecipientRequest;
use App\Application\Http\Web\Notifications\Requests\SubscribeRecipientRequest;
use App\Application\Http\Web\Notifications\Requests\UnsubscribeRecipientRequest;
use App\Domain\Notification\Actions\AddRecipientAction;
use App\Domain\Notification\Actions\SubscribeRecipientAction;
use App\Domain\Notification\Actions\UnsubscribeRecipientAction;
use App\Domain\Notification\Queries\AllNotificationRecipientsQuery;
use App\Domain\Notification\Queries\NotificationTypesQuery;
use Aptive\Component\Http\HttpStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class NotificationsController extends Controller
{
    public function __construct(
        private AllNotificationRecipientsQuery $recipientsQuery,
        private NotificationTypesQuery $notificationTypesQuery,
    ) {
    }

    /**
     * GET /notifications/recipients
     */
    public function recipients(): View
    {
        $types = $this->notificationTypesQuery->get();
        $recipients = $this->recipientsQuery->get();

        return view('notification-recipients', [
            'title' => 'Notification Recipients',
            'recipients' => $recipients,
            'notificationTypes' => $types,
        ]);
    }

    /**
     * POST /notifications/recipients
     */
    public function addRecipient(AddRecipientRequest $request, AddRecipientAction $action): RedirectResponse
    {
        $recipientName = $request->name;
        $recipientEmail = $request->get('email');
        $recipientPhone = $request->get('phone');

        $action->execute($recipientName, $recipientEmail, $recipientPhone);

        return back()->withInput();
    }

    /**
     * POST /notifications/recipients/{recipient_id}/notification-types/{notification_type_id}/{channel}
     */
    public function subscribe(SubscribeRecipientRequest $request, SubscribeRecipientAction $action): RedirectResponse
    {
        $recipientId = $request->integer('recipient_id');
        $notificationTypeId = $request->integer('notification_type_id');
        $channel = $request->channel;

        $action->execute($recipientId, $notificationTypeId, $channel);

        return back()->withInput();
    }

    /**
     * DELETE /notifications/recipients/{recipient_id}/notification-types/{notification_type_id}/{channel}
     */
    public function unsubscribe(UnsubscribeRecipientRequest $request, UnsubscribeRecipientAction $action): RedirectResponse
    {
        $recipientId = $request->integer('recipient_id');
        $notificationTypeId = $request->integer('notification_type_id');
        $channel = $request->channel;

        $action->execute($recipientId, $notificationTypeId, $channel);

        return back(HttpStatus::SEE_OTHER)->withInput();
    }
}
