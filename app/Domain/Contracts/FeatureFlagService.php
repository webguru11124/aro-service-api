<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

interface FeatureFlagService
{
    /**
     * Returns true if feature is enabled
     *
     * @param string $featureName
     *
     * @return bool
     */
    public function isFeatureEnabled(string $featureName): bool;

    /**
     * Returns true if feature is enabled for office
     *
     * @param int $officeId
     * @param string $featureName
     *
     * @return bool
     */
    public function isFeatureEnabledForOffice(int $officeId, string $featureName): bool;

    /**
     * Returns value of string feature flag for office
     *
     * @param int $officeId
     * @param string $featureName
     *
     * @return string
     */
    public function getFeatureFlagStringValueForOffice(int $officeId, string $featureName): string;
}
