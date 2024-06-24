<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Scheduling\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;
use App\Infrastructure\Services\PestRoutes\Enums\RequestingSource;
use App\Infrastructure\Services\PestRoutes\Enums\ServiceType;
use App\Infrastructure\Services\PestRoutes\Enums\Window;
use Illuminate\Validation\Rules\Enum;

class RescheduleAppointmentRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'spot_id' => 'required|int',
            'customer_id' => 'required|int',
            'office_id' => 'required|int',
            'current_appt_type' => ['required', new Enum(ServiceType::class)],
            'subscription_id' => 'required|int',
            'is_aro_spot' => 'required|boolean',
            'window' => ['required', new Enum(Window::class)],
            'requesting_source' => ['required', new Enum(RequestingSource::class)],
            'execution_sid' => 'nullable|string',
        ];
    }
}
