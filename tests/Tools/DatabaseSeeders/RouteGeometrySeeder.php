<?php

declare(strict_types=1);

namespace Tests\Tools\DatabaseSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RouteGeometrySeeder extends Seeder
{
    public const ROUTE_GEOMETRY = [
        'optimization_state_id' => [10000],
        'route_id' => [4497004],
        'geometry' => ['geometry_testing_data'],
    ];

    /**
     * @return void
     */
    public function run(): void
    {
        DB::table('field_operations.route_geometries')->insert([
            'route_id' => self::ROUTE_GEOMETRY['route_id'][0],
            'geometry' => self::ROUTE_GEOMETRY['geometry'][0],
            'optimization_state_id' => self::ROUTE_GEOMETRY['optimization_state_id'][0],
        ]);
    }
}
