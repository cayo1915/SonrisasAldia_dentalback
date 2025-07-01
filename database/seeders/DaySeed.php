<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DaySeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->runDataDefault();
        if (env('PROJECT_MODE', 'prod') === 'dev') {
            $this->runDataFake();
        }
    }

    public function runDataDefault()
    {
        $now = Carbon::now();

        DB::table('tbl_day')->insert([
            ['name' => 'Lunes', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Martes', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Miércoles', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jueves', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Viernes', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sábado', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Domingo', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function runDataFake() {}
}
