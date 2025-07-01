<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ToothModel;
use App\Models\ToothModelTooth;

class ToothModelSeed extends Seeder
{
    public function run()
    {
        $this->runDataDefault();
    }

    public function runDataDefault()
    {
        $model = ToothModel::create([
            'name' => 'Tabla base de dientes adultos'
        ]);

        $teeth = [
            // Upper right (1x y 5x)
            ["tooth_number" => 18, "quadrant" => "upper-right"],
            ["tooth_number" => 17, "quadrant" => "upper-right"],
            ["tooth_number" => 16, "quadrant" => "upper-right"],
            ["tooth_number" => 15, "quadrant" => "upper-right"],
            ["tooth_number" => 14, "quadrant" => "upper-right"],
            ["tooth_number" => 13, "quadrant" => "upper-right"],
            ["tooth_number" => 12, "quadrant" => "upper-right"],
            ["tooth_number" => 11, "quadrant" => "upper-right"],

            ["tooth_number" => null, "quadrant" => "upper-right"],
            ["tooth_number" => null, "quadrant" => "upper-right"],
            ["tooth_number" => null, "quadrant" => "upper-right"],
            ["tooth_number" => 55, "quadrant" => "upper-right"],
            ["tooth_number" => 54, "quadrant" => "upper-right"],
            ["tooth_number" => 53, "quadrant" => "upper-right"],
            ["tooth_number" => 52, "quadrant" => "upper-right"],
            ["tooth_number" => 51, "quadrant" => "upper-right"],

            // Upper left (2x y 6x)
            ["tooth_number" => 21, "quadrant" => "upper-left"],
            ["tooth_number" => 22, "quadrant" => "upper-left"],
            ["tooth_number" => 23, "quadrant" => "upper-left"],
            ["tooth_number" => 24, "quadrant" => "upper-left"],
            ["tooth_number" => 25, "quadrant" => "upper-left"],
            ["tooth_number" => 26, "quadrant" => "upper-left"],
            ["tooth_number" => 27, "quadrant" => "upper-left"],
            ["tooth_number" => 28, "quadrant" => "upper-left"],

            ["tooth_number" => 61, "quadrant" => "upper-left"],
            ["tooth_number" => 62, "quadrant" => "upper-left"],
            ["tooth_number" => 63, "quadrant" => "upper-left"],
            ["tooth_number" => 64, "quadrant" => "upper-left"],
            ["tooth_number" => 65, "quadrant" => "upper-left"],
            ["tooth_number" => null, "quadrant" => "upper-left"],
            ["tooth_number" => null, "quadrant" => "upper-left"],
            ["tooth_number" => null, "quadrant" => "upper-left"],

            // Lower right (4x y 8x)
            ["tooth_number" => null, "quadrant" => "lower-right"],
            ["tooth_number" => null, "quadrant" => "lower-right"],
            ["tooth_number" => null, "quadrant" => "lower-right"],
            ["tooth_number" => 85, "quadrant" => "lower-right"],
            ["tooth_number" => 84, "quadrant" => "lower-right"],
            ["tooth_number" => 83, "quadrant" => "lower-right"],
            ["tooth_number" => 82, "quadrant" => "lower-right"],
            ["tooth_number" => 81, "quadrant" => "lower-right"],

            ["tooth_number" => 48, "quadrant" => "lower-right"],
            ["tooth_number" => 47, "quadrant" => "lower-right"],
            ["tooth_number" => 46, "quadrant" => "lower-right"],
            ["tooth_number" => 45, "quadrant" => "lower-right"],
            ["tooth_number" => 44, "quadrant" => "lower-right"],
            ["tooth_number" => 43, "quadrant" => "lower-right"],
            ["tooth_number" => 42, "quadrant" => "lower-right"],
            ["tooth_number" => 41, "quadrant" => "lower-right"],


            // Lower left (3x y 7x)
            ["tooth_number" => 71, "quadrant" => "lower-left"],
            ["tooth_number" => 72, "quadrant" => "lower-left"],
            ["tooth_number" => 73, "quadrant" => "lower-left"],
            ["tooth_number" => 74, "quadrant" => "lower-left"],
            ["tooth_number" => 75, "quadrant" => "lower-left"],
            ["tooth_number" => null, "quadrant" => "lower-left"],
            ["tooth_number" => null, "quadrant" => "lower-left"],
            ["tooth_number" => null, "quadrant" => "lower-left"],

            ["tooth_number" => 31, "quadrant" => "lower-left"],
            ["tooth_number" => 32, "quadrant" => "lower-left"],
            ["tooth_number" => 33, "quadrant" => "lower-left"],
            ["tooth_number" => 34, "quadrant" => "lower-left"],
            ["tooth_number" => 35, "quadrant" => "lower-left"],
            ["tooth_number" => 36, "quadrant" => "lower-left"],
            ["tooth_number" => 37, "quadrant" => "lower-left"],
            ["tooth_number" => 38, "quadrant" => "lower-left"],
        ];


        foreach ($teeth as $tooth) {
            ToothModelTooth::create([
                'tooth_model_id' => $model->id,
                'tooth_number' => $tooth['tooth_number'],
                'quadrant' => $tooth['quadrant'],
            ]);
        }
    }
}
