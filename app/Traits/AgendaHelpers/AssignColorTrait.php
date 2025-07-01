<?php

namespace App\Traits\AgendaHelpers;

use App\Models\Agenda;

trait AssignColorTrait
{
    protected function getNextAvailableColor(): string
    {
        $defaultColor = '#ff70a3';

        $colors = ["#ff70a3", "#ff956f", "#fdcc6e", "#e2f59f", "#c6fdde", "#1a845c", "#629c55", "#a9ac6d", "#e4cea3", "#eae1d2"];
        $colorUsageCount = [];

        foreach ($colors as $color) {
            $count = Agenda::active()
                ->where('color_agenda', $color)
                ->count();
            $colorUsageCount[] = $count;
        }

        $minCount = min($colorUsageCount);
        return $colors[array_search($minCount, $colorUsageCount)] ?? $defaultColor;
    }
}
