<?php

namespace Database\Seeders;

use App\Models\RubricItem;
use Illuminate\Database\Seeder;

class RubricItemSeeder extends Seeder
{
    public function run(): void
    {
        $rubric = config('rubric.caac');
        $order  = 1;

        foreach ($rubric as $dimensionKey => $dimension) {
            foreach ($dimension['items'] as $item) {
                RubricItem::updateOrCreate(
                    ['rubric_type' => 'caac', 'sub_indicator_key' => $item['key']],
                    [
                        'rubric_type'         => 'caac',
                        'dimension'           => $dimensionKey,
                        'dimension_label'     => $dimension['label'],
                        'dimension_weight'    => $dimension['weight'],
                        'sub_indicator_key'   => $item['key'],
                        'sub_indicator_label' => $item['label'],
                        'max_score'           => $item['max'],
                        'sort_order'          => $item['order'],
                    ]
                );
            }
        }

        $this->command->info('Seeded ' . RubricItem::count() . ' CAAC rubric items.');
    }
}
