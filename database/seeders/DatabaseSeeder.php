<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@zenaura.ae'],
            [
                'name' => 'Zenaura Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        CompanySetting::current()->update([
            'name' => 'Zenaura',
            'address' => 'Warehouse 12, Al Quoz Industrial Area',
            'emirate' => 'Dubai',
            'phone' => '+971 4 000 0000',
            'email' => 'zenauratrading@outlook.com',
            'trn' => '100000000000003',
            'quote_prefix' => 'ZN',
            'invoice_prefix' => 'ZS',
            'default_vat_percent' => 5,
            'default_validity_days' => 30,
            'default_unit' => 'pcs',
            'currency' => 'AED',
        ]);

        $clients = [
            [
                'name' => 'Al Noor Contracting LLC',
                'phone' => '+971 50 111 2233',
                'email' => 'procurement@alnoor.ae',
                'address' => 'Business Bay',
                'emirate' => 'Dubai',
                'trn' => '100123456700003',
            ],
            [
                'name' => 'Oasis Interiors',
                'phone' => '+971 55 222 3344',
                'email' => 'projects@oasisinteriors.ae',
                'address' => 'Al Reem Island',
                'emirate' => 'Abu Dhabi',
                'trn' => '100987654300003',
            ],
        ];

        foreach ($clients as $client) {
            Client::query()->updateOrCreate(['trn' => $client['trn']], $client);
        }

        $products = [
            ['code' => 'TAP-001', 'description' => 'Chrome basin mixer, single lever', 'actual_price' => 85, 'selling_price' => 145, 'unit' => 'pcs'],
            ['code' => 'TAP-014', 'description' => 'Wall-mounted bath mixer with hand shower', 'actual_price' => 210, 'selling_price' => 360, 'unit' => 'pcs'],
            ['code' => 'SHW-008', 'description' => 'Rain shower head 200mm stainless steel', 'actual_price' => 95, 'selling_price' => 175, 'unit' => 'pcs'],
            ['code' => 'TOW-024', 'description' => 'Towel rail 600mm brushed nickel', 'actual_price' => 42, 'selling_price' => 79, 'unit' => 'pcs'],
            ['code' => 'ACC-110', 'description' => 'Soap dispenser wall mounted, chrome', 'actual_price' => 28, 'selling_price' => 55, 'unit' => 'pcs'],
            ['code' => 'SET-03', 'description' => '4-piece bathroom accessory set', 'actual_price' => 160, 'selling_price' => 275, 'unit' => 'set'],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(['code' => $product['code']], $product + ['is_active' => true]);
        }

        if (! app()->runningUnitTests()) {
            $this->call(Mea2026CatalogSeeder::class);
        }
    }
}
