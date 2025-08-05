<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\ConfigCat;

use App\Domain\Contracts\FeatureFlagService;
use App\Infrastructure\Services\ConfigCat\Exceptions\InvalidConfigCatValueType;
use App\Infrastructure\Services\ConfigCat\Exceptions\UnknownConfigCatError;
use ConfigCat\ClientInterface;
use ConfigCat\User;

class ConfigCatService implements FeatureFlagService
{
    private const ARO_USER_ID = 'ARO';
    private const ARO_USER_EMAIL = 'none';
    private const ARO_COUNTRY = 'USA';

    public function __construct(
        private ClientInterface $configCatClient,
    ) {
    }

    /**
     * Returns true if feature is enabled
     *
     * @param string $featureName
     *
     * @return bool
     * @throws UnknownConfigCatError
     * @throws InvalidConfigCatValueType
     */
    public function isFeatureEnabled(string $featureName): bool
    {
        $flagValue = $this->doConfigCatRequest($featureName);

        if (!is_bool($flagValue)) {
            throw InvalidConfigCatValueType::instance($featureName, gettype($flagValue), 'boolean');
        }

        return $flagValue;
    }

    /**
     * Returns true if feature is enabled for office
     *
     * @param int $officeId
     * @param string $featureName
     *
     * @return bool
     * @throws UnknownConfigCatError
     */
    public function isFeatureEnabledForOffice(int $officeId, string $featureName): bool
    {
        $flagValue = $this->getFeatureFlagByOfficeId($featureName, $officeId);

        if (!is_bool($flagValue)) {
            throw InvalidConfigCatValueType::instance($featureName, gettype($flagValue), 'boolean');
        }

        return $flagValue;
    }

    /**
     * Returns value of string-typed feature flag for office
     *
     * @param int $officeId
     * @param string $featureName
     *
     * @return string
     * @throws UnknownConfigCatError
     */
    public function getFeatureFlagStringValueForOffice(int $officeId, string $featureName): string
    {
        $flagValue = $this->getFeatureFlagByOfficeId($featureName, $officeId);
        if (!is_string($flagValue)) {
            throw InvalidConfigCatValueType::instance($featureName, gettype($flagValue), 'string');
        }

        return $flagValue;
    }

    /**
     * Returns value of feature flag for office
     *
     * @param string $featureName
     * @param int $officeId
     *
     * @return mixed
     * @throws UnknownConfigCatError
     */
    private function getFeatureFlagByOfficeId(string $featureName, int $officeId): mixed
    {
        return $this->doConfigCatRequest($featureName, ['office_id' => $officeId]);
    }

    /**
     * Makes a request to ConfigCat
     *
     * @param string $featureName
     * @param array<mixed> $custom
     *
     * @return mixed
     * @throws UnknownConfigCatError
     */
    private function doConfigCatRequest(string $featureName, array $custom = []): mixed
    {
        $user = new User(
            self::ARO_USER_ID,
            self::ARO_USER_EMAIL,
            self::ARO_COUNTRY,
            $custom
        );

        $flagValue = $this->configCatClient->getValue($featureName, null, $user);

        if ($flagValue === null) {
            throw UnknownConfigCatError::instance($featureName);
        }

        return $flagValue;
    }
}
