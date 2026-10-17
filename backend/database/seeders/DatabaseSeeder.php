<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'me@afif.dev'],
            [
                'name' => 'Afif',
                'password' => Hash::make('password'),
            ]
        );

        if (Item::count() === 0) {
            Item::create([
                'title' => 'Espresso Beans 1kg',
                'description' => 'Premium dark roast whole beans',
                'amount' => 50,
            ]);

            Item::create([
                'title' => 'Paper Cups 8oz',
                'description' => 'Eco-friendly biodegradable cups (pack of 50)',
                'amount' => 120,
            ]);

            Item::create([
                'title' => 'Full Cream Milk 1L',
                'description' => 'Fresh whole milk for lattes and cappuccinos',
                'amount' => 30,
            ]);
        }
    }
}
