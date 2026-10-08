<?php

namespace Database\Seeders;

use App\Enums\PaymentMethodCode;
use App\Models\DeliveryMethod;
use App\Models\Governorate;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPaymentMethods();
        $this->seedDeliveryMethods();
    }

    private function seedPaymentMethods(): void
    {
        $methods = [
            [PaymentMethodCode::Cod, ['en' => 'Cash on delivery', 'ar' => 'الدفع عند الاستلام'],
                ['en' => 'Pay the courier in cash when your order arrives.', 'ar' => 'ادفع للمندوب نقداً عند وصول الطلب.'], null, true],
            [PaymentMethodCode::InstapayWhatsapp, ['en' => 'InstaPay via WhatsApp', 'ar' => 'إنستاباي عبر واتساب'],
                ['en' => 'Transfer and send the receipt via WhatsApp.', 'ar' => 'التحويل وإرسال الإيصال عبر واتساب'],
                ['en' => 'We hold your order for 24 hours while you transfer and send the receipt on WhatsApp.', 'ar' => 'نحجز طلبك 24 ساعة حتى تحوّل وترسل الإيصال على واتساب.'], true],
            // Card hidden at runtime while Paymob keys are empty (PRD §8/§11).
            [PaymentMethodCode::Card, ['en' => 'Credit / debit card', 'ar' => 'بطاقة ائتمان / خصم'],
                ['en' => 'Pay securely by card.', 'ar' => 'ادفع بأمان بالبطاقة.'],
                ['en' => 'You will be redirected to a secure payment page.', 'ar' => 'سيتم تحويلك لصفحة دفع آمنة.'], true],
        ];

        foreach ($methods as $i => [$code, $name, $description, $instructions, $active]) {
            PaymentMethod::updateOrCreate(['code' => $code], [
                'name' => $name,
                'description' => $description,
                'instructions' => $instructions,
                'sort_order' => $i,
                'is_active' => $active,
            ]);
        }
    }

    private function seedDeliveryMethods(): void
    {
        $standard = DeliveryMethod::updateOrCreate(['name->en' => 'Standard delivery'], [
            'name' => ['en' => 'Standard delivery', 'ar' => 'توصيل عادي'],
            'description' => ['en' => 'Delivered by courier across Egypt.', 'ar' => 'توصيل بالمندوب في كل مصر.'],
            'icon' => 'truck', 'price' => 150, 'free_over_amount' => 5000, 'requires_address' => true,
            'sort_order' => 0, 'is_active' => true,
        ]);

        $express = DeliveryMethod::updateOrCreate(['name->en' => 'Express (same day)'], [
            'name' => ['en' => 'Express (same day)', 'ar' => 'توصيل سريع (نفس اليوم)'],
            'description' => ['en' => 'Same-day delivery in Cairo & Giza for orders before 12:00.', 'ar' => 'توصيل في نفس اليوم بالقاهرة والجيزة للطلبات قبل 12 ظهراً.'],
            'icon' => 'zap', 'price' => 300, 'requires_address' => true,
            'sort_order' => 1, 'is_active' => true,
        ]);

        DeliveryMethod::updateOrCreate(['name->en' => 'Showroom pickup'], [
            'name' => ['en' => 'Showroom pickup', 'ar' => 'استلام من المعرض'],
            'description' => ['en' => 'Collect from our Sheikh Zayed showroom.', 'ar' => 'استلم من معرض الشيخ زايد.'],
            'icon' => 'store', 'price' => 0, 'requires_address' => false,
            'sort_order' => 2, 'is_active' => true,
        ]);

        DeliveryMethod::updateOrCreate(['name->en' => 'Project / site delivery'], [
            'name' => ['en' => 'Project / site delivery', 'ar' => 'توصيل للمشروع / الموقع'],
            'description' => ['en' => 'Scheduled with our project team.', 'ar' => 'يُجدول مع فريق المشروعات.'],
            'note' => ['en' => 'Price agreed per project.', 'ar' => 'السعر حسب كل مشروع.'],
            'icon' => 'hard-hat', 'price' => 0, 'price_is_from' => true, 'requires_address' => true,
            'sort_order' => 3, 'is_active' => true,
        ]);

        // Express is limited to Cairo & Giza; the others serve all governorates (empty pivot).
        $cairoGiza = Governorate::whereIn('name->en', ['Cairo', 'Giza'])->pluck('id');
        $express->governorates()->sync($cairoGiza);
    }
}
