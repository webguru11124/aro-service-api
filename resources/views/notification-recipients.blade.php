@extends('layouts.master-template')
@section('content')
    <x-overview-navigation></x-overview-navigation>
    <h1 class="mb-4">{{ $title }}</h1>
    <div class="row">
        <div class="col-8">
            <table class="table table-sm">
                <thead>
                <tr>
                    <th scope="col">Recipient</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    @foreach($notificationTypes as $type)
                        <th scope="col">{{ $type->getName() }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach($recipients as $recipient)
                    <tr data-recipient-id="{{ $recipient->getId() }}">
                        <td>{{ $recipient->getName() }}</td>
                        <td>{{ $recipient->getEmail() }}</td>
                        <td>{{ $recipient->getPhone() }}</td>

                        @foreach($notificationTypes as $type)
                            <td data-notification-type-id="{{ $type->getId() }}">
                                @php($emailSubscription = $recipient->hasSubscription($type, \App\Domain\Notification\Enums\NotificationChannel::EMAIL))
                                <input type="checkbox" name="emailNotification" onclick="updateNotification(this)"
                                    {{ $emailSubscription ? 'checked' : '' }}
                                    {{ empty($recipient->getEmail()) ? 'disabled' : '' }}>
                                <label for="emailNotification">email</label>
                                <br>
                                @php($smsSubscription = $recipient->hasSubscription($type, \App\Domain\Notification\Enums\NotificationChannel::SMS))
                                <input type="checkbox" name="smsNotification" onclick="updateNotification(this)"
                                    {{ $smsSubscription ? 'checked' : '' }}
                                    {{ empty($recipient->getPhone()) ? 'disabled' : '' }}>
                                <label for="smsNotification">sms</label>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add_recipient_modal">Add Recipient</button>
        </div>
    </div>
    <div class="modal fade" id="add_recipient_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="legendModalLabel">Add Recipient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row">
                            <div class="col">
                                <div class="mb-3">
                                    <label for="recipientName" class="form-label">Name:</label>
                                    <input type="text" class="form-control" id="recipientName">
                                </div>
                                <div class="mb-3">
                                    <label for="recipientPhone" class="form-label">Phone:</label>
                                    <input type="text" class="form-control" id="recipientPhone">
                                </div>
                                <div class="mb-3">
                                    <label for="recipientEmail" class="form-label">Email:</label>
                                    <input type="text" class="form-control" id="recipientEmail">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="addRecipient()">Save</button>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('scripts-body')
    <script>
        function updateNotification(emailCheckbox) {
            const recipientId = emailCheckbox.closest('tr').getAttribute('data-recipient-id');
            const notificationTypeId = emailCheckbox.closest('td').getAttribute('data-notification-type-id');
            const channel = emailCheckbox.name.replace('Notification', '');

            const path = `/notifications/recipients/${recipientId}/notification-types/${notificationTypeId}/${channel}`;
            const method = emailCheckbox.checked ? 'POST' : 'DELETE';

            fetch(path, { method: method })
                .then(response => {
                    if (!response.ok) {
                        emailCheckbox.checked = !emailCheckbox.checked;
                        alert('Failed to update notification subscription');
                    }
                });
        }

        function addRecipient() {
            const recipientName = document.getElementById('recipientName').value;
            const recipientPhone = document.getElementById('recipientPhone').value;
            const recipientEmail = document.getElementById('recipientEmail').value;

            if (recipientName === '' || (recipientPhone === '' && recipientEmail === '')) {
                alert('Name and at least one contact method are required');
                return;
            }

            const path = '/notifications/recipients';
            const method = 'POST';
            const body = JSON.stringify({
                name: recipientName,
                phone: recipientPhone,
                email: recipientEmail
            });

            fetch(path, { method: method, body: body, headers: { 'Content-Type': 'application/json' }})
                .then(response => {
                    if (response.ok) {
                        location.reload();
                    } else {
                        alert('Failed to add recipient');
                    }
                });
        }
    </script>
@endsection
