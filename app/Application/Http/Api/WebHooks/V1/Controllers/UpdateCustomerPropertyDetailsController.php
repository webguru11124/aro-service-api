<?php

declare(strict_types=1);

namespace App\Application\Http\Api\WebHooks\V1\Controllers;

use App\Application\Commands\UpdatePropertyDetails\UpdatePropertyDetailsCommand;
use App\Application\Commands\UpdatePropertyDetails\UpdatePropertyDetailsHandler;
use App\Application\Http\Api\WebHooks\V1\Requests\UpdateCustomerPropertyDetailsRequest;
use App\Application\Http\Api\WebHooks\V1\Responses\WebHookTriggeredResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class UpdateCustomerPropertyDetailsController extends Controller
{
    public function __construct(
        private UpdatePropertyDetailsHandler $updatePropertyDetailsHandler,
    ) {
    }

    /**
     * PUT /api/v1/webhooks/customer-property-details
     *
     * @param UpdateCustomerPropertyDetailsRequest $request
     *
     * @return JsonResponse
     */
    public function __invoke(UpdateCustomerPropertyDetailsRequest $request): JsonResponse
    {
        $command = new UpdatePropertyDetailsCommand(
            customerId: $request->integer('customer_id'),
            landSqft: $request->float('land_sqft'),
            buildingSqft: $request->float('building_sqft'),
            livingSqft: $request->float('living_sqft'),
        );

        $this->updatePropertyDetailsHandler->handle($command);

        return new WebHookTriggeredResponse();
    }
}
