<?php

namespace Database\Seeders;

use App\Models\TarotCard;
use App\Support\Tarot\TarotDeckCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TarotCardSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (TarotDeckCatalog::cards() as $card) {
                TarotCard::query()->updateOrCreate(
                    ['card_id' => $card['card_id']],
                    [
                        'card_name' => $card['card_name'],
                        'arcana' => $card['arcana'],
                        'suit' => $card['suit'],
                        'number' => $card['number'],
                        'upright_meaning' => $card['upright_meaning'],
                        'reversed_meaning' => $card['reversed_meaning'],
                        'upright_keywords' => $card['upright_keywords'] ?? null,
                        'reversed_keywords' => $card['reversed_keywords'] ?? null,
                        // Artwork remains optional until asset mapping is supplied.
                        'image_path' => $card['image_path'] ?? null,
                        'sort_order' => $card['sort_order'],
                    ]
                );
            }
        });
    }
}
