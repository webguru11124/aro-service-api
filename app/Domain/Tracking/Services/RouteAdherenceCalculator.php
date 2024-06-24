<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Services;

class RouteAdherenceCalculator
{
    /**
     * Calculates the adherence of a route based on the order of optimized and completed appointments.
     *
     * @param int[] $optimizedAppointmentsOrderedIds An array of optimized appointment IDs in their intended order.
     * @param int[] $completedAppointmentsOrderedIds An array of completed appointment IDs in the order they were completed.
     *
     * @return float|null The calculated route adherence percentage.
     */
    public function calculateRouteAdherence(array $optimizedAppointmentsOrderedIds, array $completedAppointmentsOrderedIds): float|null
    {
        if (empty($optimizedAppointmentsOrderedIds) || empty($completedAppointmentsOrderedIds)) {
            return null;
        }

        $optimizedAppointmentsOrderedIds = array_values($optimizedAppointmentsOrderedIds);
        $completedAppointmentsOrderedIds = array_values($completedAppointmentsOrderedIds);

        $lcsLength = $this->calculateLCSLength($optimizedAppointmentsOrderedIds, $completedAppointmentsOrderedIds);
        $totalAppointments = count($optimizedAppointmentsOrderedIds);

        return ($lcsLength / $totalAppointments) * 100;
    }

    /**
     * Calculates the length of the longest common subsequence (LCS) between two arrays.
     *
     * @param int[] $optimizedIDs The first array of integers.
     * @param int[] $completedIDs The second array of integers.
     *
     * @return int The length of the LCS.
     */
    private function calculateLCSLength(array $optimizedIDs, array $completedIDs): int
    {
        $lengthOptimized = count($optimizedIDs);
        $lengthCompleted = count($completedIDs);
        $LCSMatrix = array_fill(0, $lengthOptimized + 1, array_fill(0, $lengthCompleted + 1, 0));

        for ($i = 0; $i <= $lengthOptimized; $i++) {
            for ($j = 0; $j <= $lengthCompleted; $j++) {
                if ($i == 0 || $j == 0) {
                    $LCSMatrix[$i][$j] = 0;
                } elseif ($optimizedIDs[$i - 1] === $completedIDs[$j - 1]) {
                    $LCSMatrix[$i][$j] = $LCSMatrix[$i - 1][$j - 1] + 1;
                } else {
                    $LCSMatrix[$i][$j] = max($LCSMatrix[$i - 1][$j], $LCSMatrix[$i][$j - 1]);
                }
            }
        }

        return $LCSMatrix[$lengthOptimized][$lengthCompleted];
    }
}
