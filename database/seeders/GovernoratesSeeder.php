<?php

namespace Database\Seeders;

use App\Models\Governorate;
use Illuminate\Database\Seeder;

class GovernoratesSeeder extends Seeder
{
    public function run(): void
    {
        $governorates = [
            ['Cairo', 'القاهرة'],
            ['Giza', 'الجيزة'],
            ['Alexandria', 'الإسكندرية'],
            ['Qalyubia', 'القليوبية'],
            ['Port Said', 'بورسعيد'],
            ['Suez', 'السويس'],
            ['Dakahlia', 'الدقهلية'],
            ['Sharqia', 'الشرقية'],
            ['Gharbia', 'الغربية'],
            ['Monufia', 'المنوفية'],
            ['Beheira', 'البحيرة'],
            ['Kafr El Sheikh', 'كفر الشيخ'],
            ['Damietta', 'دمياط'],
            ['Ismailia', 'الإسماعيلية'],
            ['Faiyum', 'الفيوم'],
            ['Beni Suef', 'بني سويف'],
            ['Minya', 'المنيا'],
            ['Asyut', 'أسيوط'],
            ['Sohag', 'سوهاج'],
            ['Qena', 'قنا'],
            ['Luxor', 'الأقصر'],
            ['Aswan', 'أسوان'],
            ['Red Sea', 'البحر الأحمر'],
            ['New Valley', 'الوادي الجديد'],
            ['Matrouh', 'مطروح'],
            ['North Sinai', 'شمال سيناء'],
            ['South Sinai', 'جنوب سيناء'],
        ];

        foreach ($governorates as $i => [$en, $ar]) {
            Governorate::updateOrCreate(
                ['name->en' => $en],
                ['name' => ['en' => $en, 'ar' => $ar], 'sort_order' => $i, 'is_active' => true],
            );
        }
    }
}
