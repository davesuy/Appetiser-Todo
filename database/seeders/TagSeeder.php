<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run()
    {
        $tags = Tag::factory()->count(10)->make()->pluck('name')->toArray();

        foreach ($tags as $tagName) {
            if (!Tag::where('name', $tagName)->exists()) {
                Tag::create(['name' => $tagName]);
            }
        }
    }
}
