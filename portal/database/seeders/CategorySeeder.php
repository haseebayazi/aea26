<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Professional Achievement',
                'slug'        => 'professional-achievement',
                'color'       => '#2563eb',
                'description' => 'Alumni who have achieved significant professional milestones, positions, and recognition in their fields.',
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Distinguished Young Alumni',
                'slug'        => 'distinguished-young-alumni',
                'color'       => '#7c3aed',
                'description' => 'Outstanding alumni who have demonstrated exceptional achievement within a short time of graduation.',
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Innovation & Entrepreneurship',
                'slug'        => 'innovation-entrepreneurship',
                'color'       => '#059669',
                'description' => 'Alumni who have founded ventures, driven innovation, or created significant intellectual and commercial value.',
                'sort_order'  => 3,
            ],
            [
                'name'        => 'Social Impact & Community Service',
                'slug'        => 'social-impact-community',
                'color'       => '#dc2626',
                'description' => 'Alumni who have made a significant positive impact on society through service, volunteerism, and community engagement.',
                'sort_order'  => 4,
            ],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
