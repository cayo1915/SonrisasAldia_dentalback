<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeed extends Seeder
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
        DB::table('tbl_role')->insert([
            'name' => 'ADMINISTRADOR',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('tbl_role')->insert([
            'name' => 'MÉDICO',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('tbl_role')->insert([
            'name' => 'PACIENTE',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('tbl_role')->insert([
            'name' => 'RECEPCIONISTA',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);


    }
    public function runDataFake() {}
}
