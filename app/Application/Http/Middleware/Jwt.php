<?php

declare(strict_types=1);

namespace App\Application\Http\Middleware;

use App\Application\Exceptions\TokenDoesNotHaveValidPermissionsException;
use App\Application\Http\Responses\UnauthorizedResponse;
use App\Domain\Contracts\FeatureFlagService;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;

class Jwt
{
    public const string JWT_AUTH_ENABLED_FEATURE_FLAG = 'isJwtAuthEnabled';

    // JWT auth is temporary middleware until ARO service migrate to new cluster.
    // So we are not concerned about more complex validation rules of user permissions.
    public const array ALLOWED_GROUP_IDS = [
        1, // super admin
        16, // routing manager
    ];

    public function __construct(
        private FeatureFlagService $featureFlagService,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            if ($this->isJwtValidationDisabled()) {
                return $next($request);
            }

            $jwt = JWTAuth::parseToken(); /** @phpstan-ignore-line */
            $jwtPayload = $jwt->getPayload()->getClaims()->all();

            $userId = !empty($jwtPayload['id']) ? (int) $jwtPayload['id']->getValue() : 0;
            $groupId = !empty($jwtPayload['group_id']) ? (int) $jwtPayload['group_id']->getValue() : 0;

            if (!in_array($groupId, self::ALLOWED_GROUP_IDS)) {
                throw TokenDoesNotHaveValidPermissionsException::instance($userId, $groupId);
            }

            Log::info(__('jwt_auth.token_has_been_accepted'), [
                'user_id' => $userId,
                'group_id' => $groupId,
            ]);
        } catch (Exception $exception) {
            if ($exception instanceof TokenInvalidException) {
                $message = __('jwt_auth.token_is_invalid');
            } elseif ($exception instanceof TokenExpiredException) {
                $message = __('jwt_auth.token_is_expired');
            } elseif ($exception instanceof TokenDoesNotHaveValidPermissionsException) {
                $message = $exception->getMessage();
            } else {
                $message = __('jwt_auth.token_not_found_or_invalid');
            }

            return new UnauthorizedResponse($message);
        }

        return $next($request);
    }

    private function isJwtValidationDisabled(): bool
    {
        try {
            return !$this->featureFlagService->isFeatureEnabled(self::JWT_AUTH_ENABLED_FEATURE_FLAG);
        } catch (\Throwable $exception) {
            Log::warning(__('jwt_auth.failed_to_get_feature_flag'), [
                'error' => $exception->getMessage(),
            ]);

            return true;
        }
    }
}
