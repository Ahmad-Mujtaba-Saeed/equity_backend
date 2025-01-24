<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Business Management',
                'description' => 'Topics related to business strategy, management, and leadership'
            ],
            [
                'name' => 'Fitness',
                'description' => 'Health, workout, and wellness related content'
            ],
            [
                'name' => 'Crypto',
                'description' => 'Cryptocurrency, blockchain, and digital assets'
            ],
            [
                'name' => 'Technology',
                'description' => 'Latest in tech, programming, and digital innovation'
            ],
            [
                'name' => 'Personal Development',
                'description' => 'Self-improvement, skills development, and career growth'
            ]
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description']
            ]);
        }
    }
}
