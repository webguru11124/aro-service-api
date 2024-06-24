<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Scheduling\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;
use App\Infrastructure\Services\PestRoutes\Enums\AppointmentType;
use App\Infrastructure\Services\PestRoutes\Enums\RequestingSource;
use App\Infrastructure\Services\PestRoutes\Enums\Window;
use Illuminate\Validation\Rules\Enum;

class CreateAppointmentRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'office_id' => 'required|int',
            'customer_id' => 'required|int',
            'spot_id' => 'required|int',
            'subscription_id' => 'required|int',
            'appointment_type' => ['required', new Enum(AppointmentType::class)],
            'is_aro_spot' => 'required|boolean',
            'window' => ['required', new Enum(Window::class)],
            'requesting_source' => ['required', new Enum(RequestingSource::class)],
            'execution_sid' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }
}
