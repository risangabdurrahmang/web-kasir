<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $payments = [
            ['name' => 'Cash', 'is_active' => true],
            ['name' => 'QRIS', 'is_active' => true],
            ['name' => 'Bank Transfer', 'is_active' => true],
        ];

        foreach ($payments as $payment) {
            Payment::updateOrCreate(['name' => $payment['name']], $payment);
        }
    }
}
