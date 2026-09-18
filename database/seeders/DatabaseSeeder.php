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
            'address' => 'BC-893006, Floor 26, Amber Gem Tower, Sheikh Khalifa Street,
Ajman',
            'emirate' => 'Dubai',
            'phone' => '+971547662736',
            'email' => 'zenauratrading@outlook.com',
            'trn' => '105479154400003',
            'quote_prefix' => 'ZN',
            'invoice_prefix' => 'ZS',
            'default_vat_percent' => 5,
            'default_validity_days' => 30,
            'default_unit' => 'pcs',
            'currency' => 'AED',
            'website' => 'https://zenaura.ae',
            'bank_details' => 'Bank Name : RAS AL KHAIMAH BANK
Company Name : ZENAURA SANITARY TRADING FZE LLC
Account No : 0573728525001
IBAN No : AE93 0400 0005 7372 8525 001
Branch : SHARJAH MUWAILEH BRANCH',
        ]);

    
        if (! app()->runningUnitTests()) {
            $this->call(Mea2026CatalogSeeder::class);
        }
    }
}
