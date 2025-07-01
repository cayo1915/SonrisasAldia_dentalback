<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DetailMenuItemsSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->runDataDefault();
        $this->runDataFake();
    }

    public function runDataDefault() {}

    public function runDataFake() {}
}
