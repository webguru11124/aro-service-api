<?php

declare(strict_types=1);

namespace Tests\Tools\DatabaseSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Tests\Tools\TestValue;

class CustomerPropertyDetailsSeeder extends Seeder
{
    public const DATA = [
        ['customer_id' => TestValue::CUSTOMER_PROPERTY_DETAILS_ID, 'land_sqft' => 1000.0, 'building_sqft' => 800.0, 'living_sqft' => 500.0],
        ['customer_id' => 2345, 'land_sqft' => 1000.0, 'building_sqft' => 800.0, 'living_sqft' => 500.0],
    ];

    /**
     * Run the database seeds for the recipients table.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('field_operations.customer_property_details')->insert(self::DATA);
    }
}
