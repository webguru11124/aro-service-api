<?php

declare(strict_types=1);

namespace App\Application\Http\Api\Calendar\V1\Requests;

use App\Application\Http\Requests\AbstractFormRequest;
use App\Domain\Calendar\Enums\EndAfter;
use App\Domain\Calendar\Enums\EventType;
use App\Domain\Calendar\Enums\ScheduleInterval;
use App\Domain\Calendar\Enums\WeekDay;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Application\Http\Responses\ValidationErrorResponse;

/**
 * @property string $office_id,
 * @property string $title,
 * @property string|null $description
 * @property string $start_date
 * @property string $event_type
 * @property string|null $end_date
 * @property string $start_at
 * @property string $end_at
 * @property string $interval
 * @property string|null $week_days
 * @property int|null $week_num
 * @property string $location_lat
 * @property string $location_lng
 * @property string|null $meeting_link
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string $end_after
 * @property int|null $occurrences
 * @property int|null $repeat_every
 */
class CreateEventRequest extends AbstractFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'office_id' => 'required|int|gt:0',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'event_type' => ['required', new Enum(EventType::class)],
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => [
                'nullable',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                Rule::requiredIf($this->end_after === EndAfter::DATE->value),
            ],
            'start_at' => 'required|date_format:H:i:s',
            'end_at' => 'required|date_format:H:i:s|after:start_at',
            'interval' => ['required', new Enum(ScheduleInterval::class)],
            'week_days' => [
                Rule::requiredIf($this->interval === ScheduleInterval::WEEKLY->value),
            ],
            'week_days.*' => [
                'distinct:ignore_case',
                new Enum(WeekDay::class),
            ],
            'location_lat' => 'nullable|numeric|required_with:location_lng',
            'location_lng' => 'nullable|numeric|required_with:location_lat',
            'meeting_link' => 'nullable|string|max:200|regex:/^(https?:\/\/)?meet\.google\.com\/[a-z0-9-]+$/',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string|size:2',
            'zip' => 'nullable|string|size:5',
            'end_after' => ['required', new Enum(EndAfter::class)],
            'occurrences' => [
                'nullable',
                'int',
                'gt:0',
                Rule::requiredIf($this->end_after === EndAfter::OCCURRENCES->value),
            ],
            'repeat_every' => 'sometimes|int|gt:0',
            'week_num' => [
                Rule::requiredIf($this->interval === ScheduleInterval::MONTHLY->value),
                'nullable',
                'int',
                Rule::in([-1, 1, 2, 3, 4, 5, 6]),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('title') && $this->get('title') !== null) {
            $this->merge(['title' => trim($this->title)]);
        }
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            new ValidationErrorResponse(__('messages.validator.invalid_request'), $validator->errors()->messages())
        );
    }
}
