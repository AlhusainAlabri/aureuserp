<?php

namespace Webkul\MyNotes\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\MyNotes\Models\Note;
use Webkul\MyNotes\Models\NoteChecklistItem;

class NoteChecklistItemFactory extends Factory
{
    protected $model = NoteChecklistItem::class;

    public function definition(): array
    {
        return [
            'note_id'    => Note::factory(),
            'content'    => fake()->sentence(),
            'is_checked' => fake()->boolean(30),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
