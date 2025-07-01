<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeZoneSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->runDataDefault();
        $this->runDataFake();
    }

    public function runDataDefault()
    {
        DB::table('tbl_country_time_zone')->insert([
            'description'   => 'UTC/GMT +4 HOURS',
            'time_zone'     => 'America/Lima'
        ]);
    }

    public function runDataFake() {}
}
