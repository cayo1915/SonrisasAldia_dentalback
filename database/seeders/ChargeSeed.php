<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChargeSeed extends Seeder
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
        DB::table('tbl_charge')->insert([
            'name'                  => 'Gerente General',
            'description'           => 'Gerente general',
        ]);
        DB::table('tbl_charge')->insert([
            'name'                  => 'Encargada gestión de personas',
            'description'           => 'Encargada gestión de personas',
        ]);
    }

    public function runDataFake() {}
}
