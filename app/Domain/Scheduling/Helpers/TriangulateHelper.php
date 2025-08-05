<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Helpers;

use App\Domain\Scheduling\ValueObjects\CirclePoint;
use App\Domain\Scheduling\ValueObjects\Point;
use App\Domain\Scheduling\ValueObjects\Triangle;
use Illuminate\Support\Collection;

class TriangulateHelper
{
    private const EPS = 1e-7;

    /**
     * @param Collection<Point> $points
     *
     * @return Collection<Triangle>
     */
    public function triangulate(Collection $points): Collection
    {
        if ($points->count() < 3) {
            return collect();
        }

        $ind = [];
        for ($i = 0; $i < $points->count(); $i++) {
            $ind[] = $i;
        }

        usort($ind, function (int $l, int $r) use ($points) {
            return $points[$r]->x - $points[$l]->x;
        });

        $n = $points->count();

        $bigTrianglePoints = $this->getBigTrianglePoints($points);
        $points = $points->merge($bigTrianglePoints);

        /** @var array<CirclePoint> $circlePoints */
        $circlePoints = [$this->circumcircleOfTriangle($points, $n, $n + 1, $n + 2)];
        $ans = [];
        $edges = [];

        for ($i = count($ind) - 1; $i >= 0; $i--) {
            for ($j = count($circlePoints) - 1; $j >= 0; $j--) {
                $dx = $points[$ind[$i]]->x - $circlePoints[$j]->x;

                if ($dx > 0 && $dx * $dx > $circlePoints[$j]->r) {
                    $ans[] = $circlePoints[$j];
                    array_splice($circlePoints, $j, 1);

                    continue;
                }

                $dy = $points[$ind[$i]]->y - $circlePoints[$j]->y;

                if ($dx * $dx + $dy * $dy - $circlePoints[$j]->r > self::EPS) {
                    continue;
                }

                array_push(
                    $edges,
                    $circlePoints[$j]->a,
                    $circlePoints[$j]->b,
                    $circlePoints[$j]->b,
                    $circlePoints[$j]->c,
                    $circlePoints[$j]->c,
                    $circlePoints[$j]->a,
                );
                array_splice($circlePoints, $j, 1);
            }

            $this->deleteMultiplesEdges($edges);

            for ($j = count($edges) - 1; $j >= 0;) {
                $b = $edges[$j];
                $j--;

                if ($j < 0) {
                    break;
                }

                $a = $edges[$j];
                $j--;
                $circlePoints[] = $this->circumcircleOfTriangle($points, $a, $b, $ind[$i]);
            }

            $edges = [];
        }

        for ($i = count($circlePoints) - 1; $i >= 0; $i--) {
            $ans[] = $circlePoints[$i];
        }

        $triangles = collect();
        for ($i = 0; $i < count($ans); $i++) {
            if ($ans[$i]->a < $n && $ans[$i]->b < $n && $ans[$i]->c < $n) {
                $triangles->add(new Triangle(
                    a: $ans[$i]->a,
                    b: $ans[$i]->b,
                    c: $ans[$i]->c
                ));
            }
        }

        return $triangles;
    }

    /**
     * @param Collection<Point> $points
     *
     * @return Collection<Point>
     */
    private function getBigTrianglePoints(Collection $points): Collection
    {
        $minX = 10000000;
        $maxX = -10000000;
        $minY = 10000000;
        $maxY = -10000000;

        foreach ($points as $point) {
            $minX = min($minX, $point->x);
            $minY = min($minY, $point->y);
            $maxX = max($maxX, $point->x);
            $maxY = max($maxY, $point->y);
        }

        $dx = $maxX - $minX;
        $dy = $maxY - $minY;
        $dxy = max($dx, $dy);
        $midX = $dx * 0.5 + $minX;
        $midY = $dy * 0.5 + $minY;

        return collect([
            new Point(x: $midX - 10 * $dxy, y: $midY - 10 * $dxy),
            new Point(x: $midX, y: $midY + 10 * $dxy),
            new Point(x: $midX + 10 * $dxy, y: $midY - 10 * $dxy),
        ]);
    }

    /**
     * @param Collection<Point> $points
     * @param int $v1
     * @param int $v2
     * @param int $v3
     *
     * @return CirclePoint
     */
    private function circumcircleOfTriangle(Collection $points, int $v1, int $v2, int $v3): CirclePoint
    {
        $x1 = $points[$v1]->x;
        $y1 = $points[$v1]->y;
        $x2 = $points[$v2]->x;
        $y2 = $points[$v2]->y;
        $x3 = $points[$v3]->x;
        $y3 = $points[$v3]->y;

        $dy12 = abs($y1 - $y2);
        $dy23 = abs($y2 - $y3);
        $dy21 = $y2 - $y1;
        $dy32 = $y3 - $y2;
        $dx21 = $x2 - $x1;
        $dx32 = $x3 - $x2;

        if ($dy12 < self::EPS) {
            $m2 = $dy32 == 0 ? 0 : -($dx32 / $dy32);
            $mx2 = ($x2 + $x3) / 2;
            $my2 = ($y2 + $y3) / 2;
            $xc = ($x1 + $x2) / 2;
            $yc = $m2 * ($xc - $mx2) + $my2;
        } elseif ($dy23 < self::EPS) {
            $m1 = $dy21 == 0 ? 0 : -($dx21 / $dy21);
            $mx1 = ($x1 + $x2) / 2;
            $my1 = ($y1 + $y2) / 2;
            $xc = ($x2 + $x3) / 2;
            $yc = $m1 * ($xc - $mx1) + $my1;
        } else {
            $m1 = $dy21 == 0 ? 0 : -($dx21 / $dy21);
            $m2 = $dy32 == 0 ? 0 : -($dx32 / $dy32);
            $mx1 = ($x1 + $x2) / 2;
            $my1 = ($y1 + $y2) / 2;
            $mx2 = ($x2 + $x3) / 2;
            $my2 = ($y2 + $y3) / 2;

            $xc = ($m1 - $m2 == 0) ? 0 : ($m1 * $mx1 - $m2 * $mx2 + $my2 - $my1) / ($m1 - $m2);

            if ($dy12 > $dy23) {
                $yc = $m1 * ($xc - $mx1) + $my1;
            } else {
                $yc = $m2 * ($xc - $mx2) + $my2;
            }
        }

        $dx = $x2 - $xc;
        $dy = $y2 - $yc;
        $r = $dx * $dx + $dy * $dy;

        return new CirclePoint(a: $v1, b: $v2, c: $v3, x: $xc, y: $yc, r: $r);
    }

    /**
     * @param int[] $edges
     *
     * @return void
     */
    private function deleteMultiplesEdges(array &$edges): void
    {
        for ($j = count($edges) - 1; $j >= 0;) {
            $b = $edges[$j] ?? null;
            $j--;

            if ($b === null) {
                continue;
            }

            $a = $edges[$j] ?? null;
            $j--;

            if ($a === null) {
                continue;
            }

            for ($i = $j; $i >= 0;) {
                $n = $edges[$i];
                $i--;
                $m = $edges[$i];
                $i--;

                if ($a === $m && $b === $n) {
                    array_splice($edges, $j + 1, 2);
                    array_splice($edges, $i + 1, 2);

                    break;
                }

                if ($a === $n && $b === $m) {
                    array_splice($edges, $j + 1, 2);
                    array_splice($edges, $i + 1, 2);

                    break;
                }
            }
        }
    }
}
