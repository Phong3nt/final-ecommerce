<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            [
                'code'             => 'SUMMER10',
                'type'             => 'percent',
                'value'            => 10.00,
                'is_active'        => true,
                'usage_limit'      => 500,
                'min_order_amount' => 20.00,
                'expires_at'       => null,
            ],
            [
                'code'             => 'NEWUSER20',
                'type'             => 'percent',
                'value'            => 20.00,
                'is_active'        => true,
                'usage_limit'      => 1000,
                'min_order_amount' => null,
                'expires_at'       => null,
            ],
            [
                'code'             => 'FLASH15',
                'type'             => 'percent',
                'value'            => 15.00,
                'is_active'        => true,
                'usage_limit'      => 200,
                'min_order_amount' => 50.00,
                'expires_at'       => now()->addMonths(3),
            ],
            [
                'code'             => 'LOYALTY5',
                'type'             => 'fixed',
                'value'            => 5.00,
                'is_active'        => true,
                'usage_limit'      => null,
                'min_order_amount' => 30.00,
                'expires_at'       => null,
            ],
            [
                'code'             => 'BUNDLE30',
                'type'             => 'percent',
                'value'            => 30.00,
                'is_active'        => true,
                'usage_limit'      => 100,
                'min_order_amount' => 100.00,
                'expires_at'       => now()->addMonths(6),
            ],
        ];

        foreach ($coupons as $data) {
            Coupon::firstOrCreate(['code' => $data['code']], $data);
        }
    }
}
