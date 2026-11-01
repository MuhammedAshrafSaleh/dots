<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builds the live product import template (.xlsx).
 *
 * Row 1 = Arabic display headers (required = yellow + *)
 * Row 2 = hidden system keys (importer maps by these, never by position)
 * Row 3+ = user data
 */
final class ProductTemplateBuilder
{
    // Cell comment text for each column key on المنتجات sheet.
    /** @var array<string, string> */
    private const PRODUCT_COMMENTS = [
        'sku' => 'كود فريد للمنتج. اتركه فارغاً للمنتجات الجديدة أو ضع الكود الحالي للتحديث.',
        'name_ar' => 'اسم المنتج بالعربي.',
        'name_en' => 'اسم المنتج بالإنجليزي.',
        'brand' => 'اختر العلامة التجارية من القائمة.',
        'category' => 'اختر القسم (مستوى 3) من القائمة.',
        'status' => 'منشور / مسودة / مؤرشف. تُعيَّن على "مسودة" للمنتجات الجديدة إن تُركت فارغة.',
        'price' => 'سعر المنتج (جنيه مصري). يُترك فارغاً إن كان السعر عند الطلب.',
        'compare_at_price' => 'سعر المقارنة قبل الخصم. يجب أن يكون أعلى من السعر.',
        'stock_qty' => 'الكمية في المخزن (عدد صحيح ≥ 0).',
        'price_on_request' => 'نعم إن كان السعر يُحدَّد عند الطلب.',
        'overview_ar' => 'نص عام عن المنتج بالعربي (نص فقط، بدون HTML).',
        'overview_en' => 'نص عام عن المنتج بالإنجليزي.',
        'warranty_ar' => 'معلومات الضمان بالعربي.',
        'warranty_en' => 'معلومات الضمان بالإنجليزي.',
        'is_new' => 'نعم لإظهار شارة "جديد".',
        'is_best_seller' => 'نعم لإظهار شارة "الأكثر مبيعاً".',
        'is_smart_pick' => 'نعم لإظهار شارة "منزل ذكي".',
        'is_dimmable' => 'نعم إن كان المنتج قابلاً للتعتيم.',
        'ships_24h' => 'نعم إن كان يُشحن خلال 24 ساعة.',
        'images' => 'أسماء ملفات الصور من ملف ZIP المرفق، مفصولة بـ |. مثال: img1.jpg|img2.jpg',
        'meta_title_ar' => 'عنوان الصفحة لمحركات البحث بالعربي.',
        'meta_title_en' => 'عنوان الصفحة لمحركات البحث بالإنجليزي.',
        'meta_description_ar' => 'وصف محركات البحث بالعربي.',
        'meta_description_en' => 'وصف محركات البحث بالإنجليزي.',
    ];

    /** @var array<string, string> */
    private const VARIANT_COMMENTS = [
        'product_sku' => 'كود المنتج الأب (من ورقة المنتجات).',
        'variant_sku' => 'كود فريد للاختيار.',
        'price' => 'سعر هذا الاختيار (جنيه مصري).',
        'compare_at_price' => 'سعر المقارنة قبل الخصم.',
        'stock_qty' => 'الكمية في المخزن (عدد صحيح ≥ 0).',
        'is_default' => 'نعم للاختيار الافتراضي. اختيار واحد فقط لكل منتج.',
    ];

    /** @var array<string, string> */
    private const SPEC_COMMENTS = [
        'product_sku' => 'كود المنتج الأب (من ورقة المنتجات).',
        'name_ar' => 'اسم المواصفة بالعربي.',
        'name_en' => 'اسم المواصفة بالإنجليزي.',
        'value_ar' => 'قيمة المواصفة بالعربي.',
        'value_en' => 'قيمة المواصفة بالإنجليزي.',
    ];

    // -----------------------------------------------------------------------

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0); // remove the default sheet

        $brands = Brand::orderBy('name')->pluck('name')->all();
        $categoryPaths = $this->categoryPaths();
        $axisAttrs = Attribute::where('is_variant_axis', true)->orderBy('sort_order')->with('values')->get();

        $this->buildInstructions($spreadsheet);
        $this->buildProducts($spreadsheet, $axisAttrs);
        $this->buildVariants($spreadsheet, $axisAttrs);
        $this->buildSpecs($spreadsheet);
        $this->buildBrands($spreadsheet, $brands);
        $this->buildCategories($spreadsheet, $categoryPaths);
        $this->buildAttrValues($spreadsheet, $axisAttrs);
        $this->buildLists($spreadsheet);

        $spreadsheet->setActiveSheetIndex(1); // المنتجات

        return $spreadsheet;
    }

    // ------------------------------------------------------------------
    // Sheets
    // ------------------------------------------------------------------

    private function buildInstructions(Spreadsheet $spreadsheet): void
    {
        $sheet = new Worksheet($spreadsheet, 'تعليمات');
        $spreadsheet->addSheet($sheet);

        $instructions = [
            'ملف استيراد منتجات DOTS',
            '',
            '1. نزّل هذا القالب من لوحة التحكم في كل مرة لضمان حصولك على أحدث البيانات.',
            '2. أدخل بيانات المنتجات في ورقة "المنتجات"، والاختيارات في "الاختيارات"، والمواصفات في "المواصفات".',
            '3. استخدم القوائم المنسدلة للعلامة التجارية والقسم وقيم الخصائص — لا تكتب قيماً غير موجودة في القوائم.',
            '4. ارفع ملف Excel مع ملف ZIP يحتوي على صور المنتجات (اختياري).',
            '5. أسماء ملفات الصور بالإنجليزي تُقلل مشاكل الترميز.',
            '6. الأعمدة المحجوبة في الصف الثاني هي مفاتيح النظام — لا تعدّلها.',
            '7. احذف أي صفوف مثالية (EXAMPLE-) قبل الرفع.',
        ];

        foreach ($instructions as $i => $line) {
            $sheet->setCellValue('B'.($i + 1), $line);
        }

        $sheet->getColumnDimension('B')->setWidth(80);
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
    }

    /** @param Collection<int, Attribute> $axisAttrs */
    private function buildProducts(Spreadsheet $spreadsheet, Collection $axisAttrs): void
    {
        $sheet = new Worksheet($spreadsheet, 'المنتجات');
        $spreadsheet->addSheet($sheet);

        $required = ['sku', 'name_ar', 'name_en', 'brand', 'category'];

        $columns = [
            'sku', 'name_ar', 'name_en', 'brand', 'category',
            'status', 'price', 'compare_at_price', 'stock_qty',
            'price_on_request', 'overview_ar', 'overview_en',
            'warranty_ar', 'warranty_en',
            'is_new', 'is_best_seller', 'is_smart_pick', 'is_dimmable', 'ships_24h',
            'images',
            'meta_title_ar', 'meta_title_en',
            'meta_description_ar', 'meta_description_en',
        ];

        $headers = [
            'sku' => 'كود المنتج',
            'name_ar' => 'اسم المنتج بالعربي',
            'name_en' => 'اسم المنتج بالإنجليزي',
            'brand' => 'البراند',
            'category' => 'القسم',
            'status' => 'الحالة',
            'price' => 'السعر (ج.م)',
            'compare_at_price' => 'السعر قبل الخصم (ج.م)',
            'stock_qty' => 'الكمية في المخزن',
            'price_on_request' => 'السعر عند الطلب',
            'overview_ar' => 'نظرة عامة بالعربي',
            'overview_en' => 'نظرة عامة بالإنجليزي',
            'warranty_ar' => 'الضمان بالعربي',
            'warranty_en' => 'الضمان بالإنجليزي',
            'is_new' => 'جديد',
            'is_best_seller' => 'الأكثر مبيعاً',
            'is_smart_pick' => 'منزل ذكي',
            'is_dimmable' => 'قابل للتعتيم',
            'ships_24h' => 'شحن خلال 24 ساعة',
            'images' => 'الصور (أسماء الملفات)',
            'meta_title_ar' => 'عنوان SEO بالعربي',
            'meta_title_en' => 'عنوان SEO بالإنجليزي',
            'meta_description_ar' => 'وصف SEO بالعربي',
            'meta_description_en' => 'وصف SEO بالإنجليزي',
        ];

        $this->writeHeaderRow($sheet, $columns, $headers, $required, self::PRODUCT_COMMENTS);
        $this->hideKeyRow($sheet);
        $this->applyProductValidations($sheet, $columns, count($columns));
    }

    /** @param Collection<int, Attribute> $axisAttrs */
    private function buildVariants(Spreadsheet $spreadsheet, Collection $axisAttrs): void
    {
        $sheet = new Worksheet($spreadsheet, 'الاختيارات');
        $spreadsheet->addSheet($sheet);

        $staticCols = ['product_sku', 'variant_sku'];
        $attrCols = $axisAttrs->map(fn (Attribute $a) => 'attr:'.$a->code)->all();
        $trailCols = ['price', 'compare_at_price', 'stock_qty', 'is_default'];

        $columns = [...$staticCols, ...$attrCols, ...$trailCols];
        $required = ['product_sku', 'variant_sku', 'price', 'stock_qty'];

        $staticHeaders = [
            'product_sku' => 'كود المنتج',
            'variant_sku' => 'كود الاختيار',
            'price' => 'السعر (ج.م)',
            'compare_at_price' => 'السعر قبل الخصم (ج.م)',
            'stock_qty' => 'الكمية في المخزن',
            'is_default' => 'الاختيار الافتراضي',
        ];

        $headers = $staticHeaders;
        foreach ($axisAttrs as $attr) {
            $headers['attr:'.$attr->code] = $attr->getTranslation('name', 'ar');
        }

        $this->writeHeaderRow($sheet, $columns, $headers, $required, self::VARIANT_COMMENTS);
        $this->hideKeyRow($sheet);
        $this->applyVariantValidations($sheet, $columns, $axisAttrs);
    }

    private function buildSpecs(Spreadsheet $spreadsheet): void
    {
        $sheet = new Worksheet($spreadsheet, 'المواصفات');
        $spreadsheet->addSheet($sheet);

        $columns = ['product_sku', 'name_ar', 'name_en', 'value_ar', 'value_en'];
        $required = ['product_sku', 'name_ar', 'name_en', 'value_ar', 'value_en'];

        $headers = [
            'product_sku' => 'كود المنتج',
            'name_ar' => 'اسم المواصفة بالعربي',
            'name_en' => 'اسم المواصفة بالإنجليزي',
            'value_ar' => 'القيمة بالعربي',
            'value_en' => 'القيمة بالإنجليزي',
        ];

        $this->writeHeaderRow($sheet, $columns, $headers, $required, self::SPEC_COMMENTS);
        $this->hideKeyRow($sheet);
    }

    /** @param array<int, string> $brands */
    private function buildBrands(Spreadsheet $spreadsheet, array $brands): void
    {
        $sheet = new Worksheet($spreadsheet, 'البراندات');
        $spreadsheet->addSheet($sheet);

        $sheet->setCellValue('A1', 'العلامة التجارية');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        foreach ($brands as $i => $name) {
            $sheet->setCellValue('A'.($i + 2), $name);
        }
        $sheet->getColumnDimension('A')->setAutoSize(true);
    }

    /** @param array<int, string> $paths */
    private function buildCategories(Spreadsheet $spreadsheet, array $paths): void
    {
        $sheet = new Worksheet($spreadsheet, 'الأقسام');
        $spreadsheet->addSheet($sheet);

        $sheet->setCellValue('A1', 'القسم (المسار الكامل)');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        foreach ($paths as $i => $path) {
            $sheet->setCellValue('A'.($i + 2), $path);
        }
        $sheet->getColumnDimension('A')->setAutoSize(true);
    }

    /** @param Collection<int, Attribute> $axisAttrs */
    private function buildAttrValues(Spreadsheet $spreadsheet, Collection $axisAttrs): void
    {
        $sheet = new Worksheet($spreadsheet, 'قيم الاختيارات');
        $spreadsheet->addSheet($sheet);

        $col = 'A';
        foreach ($axisAttrs as $attr) {
            // Row 1 = Arabic attribute name
            $sheet->setCellValue($col.'1', $attr->getTranslation('name', 'ar'));
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            // Row 2 = attribute code
            $sheet->setCellValue($col.'2', $attr->code);

            // Row 3+ = unique values (label.ar if present, else value)
            $seen = [];
            $row = 3;
            foreach ($attr->values as $val) {
                $label = $val->getTranslation('label', 'ar') ?: $val->value;
                if (in_array($label, $seen, true)) {
                    continue;
                }
                $seen[] = $label;
                $sheet->setCellValue($col.$row, $label);
                $row++;
            }

            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col = str_increment($col);
        }
    }

    private function buildLists(Spreadsheet $spreadsheet): void
    {
        $sheet = new Worksheet($spreadsheet, 'lists');
        $spreadsheet->addSheet($sheet);

        // A = yes/no, B = status
        $sheet->setCellValue('A1', 'نعم');
        $sheet->setCellValue('A2', 'لا');
        $sheet->setCellValue('B1', 'منشور');
        $sheet->setCellValue('B2', 'مسودة');
        $sheet->setCellValue('B3', 'مؤرشف');

        $sheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Write row 1 (headers) and row 2 (keys) for a data sheet.
     *
     * @param  array<int, string>  $columns
     * @param  array<string, string>  $headers
     * @param  array<int, string>  $required
     * @param  array<string, string>  $comments
     */
    private function writeHeaderRow(
        Worksheet $sheet,
        array $columns,
        array $headers,
        array $required,
        array $comments,
    ): void {
        $col = 'A';
        foreach ($columns as $key) {
            $label = $headers[$key] ?? $key;
            $isReq = in_array($key, $required, true);
            $cell = $col.'1';
            $keyCell = $col.'2';

            $sheet->setCellValue($cell, $isReq ? $label.' *' : $label);
            $sheet->setCellValue($keyCell, $key);

            // Style required columns yellow
            if ($isReq) {
                $sheet->getStyle($cell)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFFFCC');
                $sheet->getStyle($cell)->getFont()->setBold(true);
            } else {
                $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FF888888');
            }

            // Cell comment
            if (isset($comments[$key])) {
                $comment = $sheet->getComment($cell);
                $comment->getText()->createTextRun($comments[$key]);
                $comment->setWidth('200pt');
                $comment->setHeight('80pt');
                $comment->getFillColor()->setARGB('FFFFFFEE');
                $comment->getText()->getRichTextElements()[0]->getFont()?->setSize(9);
            }

            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getColumnDimension($col)->setWidth(20);
            $col = str_increment($col);
        }
    }

    private function hideKeyRow(Worksheet $sheet): void
    {
        $sheet->getRowDimension(2)->setVisible(false)->setRowHeight(0);
    }

    /**
     * Apply data validations to the Products sheet using range-based rules
     * (one DataValidation object covers the entire data range, not one per row).
     *
     * @param  array<int, string>  $columns
     */
    private function applyProductValidations(Worksheet $sheet, array $columns, int $colCount): void
    {
        $colIndex = array_flip($columns);

        $brandCol = $this->colLetter(($colIndex['brand'] ?? 0) + 1);
        $catCol = $this->colLetter(($colIndex['category'] ?? 0) + 1);
        $statusCol = $this->colLetter(($colIndex['status'] ?? 0) + 1);

        $yesNoCols = ['price_on_request', 'is_new', 'is_best_seller', 'is_smart_pick', 'is_dimmable', 'ships_24h'];
        $numericCols = ['price', 'compare_at_price'];
        $intCols = ['stock_qty'];

        $this->addListValidation($sheet, $brandCol.'3:'.$brandCol.'1002', "'البراندات'!\$A\$2:\$A\$1001");
        $this->addListValidation($sheet, $catCol.'3:'.$catCol.'1002', "'الأقسام'!\$A\$2:\$A\$1001");
        $this->addListValidation($sheet, $statusCol.'3:'.$statusCol.'1002', 'lists!$B$1:$B$3');

        foreach ($yesNoCols as $key) {
            if (! isset($colIndex[$key])) {
                continue;
            }
            $c = $this->colLetter($colIndex[$key] + 1);
            $this->addListValidation($sheet, $c.'3:'.$c.'1002', 'lists!$A$1:$A$2');
        }

        foreach ($numericCols as $key) {
            if (! isset($colIndex[$key])) {
                continue;
            }
            $c = $this->colLetter($colIndex[$key] + 1);
            $this->addNumericValidation($sheet, $c.'3:'.$c.'1002', DataValidation::TYPE_DECIMAL);
        }

        foreach ($intCols as $key) {
            if (! isset($colIndex[$key])) {
                continue;
            }
            $c = $this->colLetter($colIndex[$key] + 1);
            $this->addNumericValidation($sheet, $c.'3:'.$c.'1002', DataValidation::TYPE_WHOLE);
        }
    }

    /**
     * Apply data validations to the Variants sheet.
     *
     * @param  array<int, string>  $columns
     * @param  Collection<int, Attribute>  $axisAttrs
     */
    private function applyVariantValidations(Worksheet $sheet, array $columns, Collection $axisAttrs): void
    {
        $colIndex = array_flip($columns);
        $yesNoCols = ['is_default'];
        $numericCols = ['price', 'compare_at_price'];
        $intCols = ['stock_qty'];

        foreach ($axisAttrs as $idx => $attr) {
            $keyName = 'attr:'.$attr->code;
            if (! isset($colIndex[$keyName])) {
                continue;
            }
            $c = $this->colLetter($colIndex[$keyName] + 1);
            $attrExcelCol = $this->colLetter($idx + 1);
            $this->addListValidation($sheet, $c.'3:'.$c.'1002', "'قيم الاختيارات'!\${$attrExcelCol}\$3:\${$attrExcelCol}\$200");
        }

        foreach ($yesNoCols as $key) {
            if (! isset($colIndex[$key])) {
                continue;
            }
            $c = $this->colLetter($colIndex[$key] + 1);
            $this->addListValidation($sheet, $c.'3:'.$c.'1002', 'lists!$A$1:$A$2');
        }

        foreach ($numericCols as $key) {
            if (! isset($colIndex[$key])) {
                continue;
            }
            $c = $this->colLetter($colIndex[$key] + 1);
            $this->addNumericValidation($sheet, $c.'3:'.$c.'1002', DataValidation::TYPE_DECIMAL);
        }

        foreach ($intCols as $key) {
            if (! isset($colIndex[$key])) {
                continue;
            }
            $c = $this->colLetter($colIndex[$key] + 1);
            $this->addNumericValidation($sheet, $c.'3:'.$c.'1002', DataValidation::TYPE_WHOLE);
        }
    }

    private function addListValidation(Worksheet $sheet, string $sqref, string $formula1): void
    {
        // getDataValidation() requires a single-cell anchor; set sqref for the range.
        $anchor = explode(':', $sqref)[0];
        $v = $sheet->getDataValidation($anchor);
        $v->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(false)
            ->setFormula1($formula1)
            ->setSqref($sqref);
    }

    private function addNumericValidation(Worksheet $sheet, string $sqref, string $type): void
    {
        $anchor = explode(':', $sqref)[0];
        $v = $sheet->getDataValidation($anchor);
        $v->setType($type)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
            ->setFormula1('0')
            ->setSqref($sqref);
    }

    /** Convert 1-based column number to Excel letter(s). */
    private function colLetter(int $n): string
    {
        $name = '';
        while ($n > 0) {
            $n--;
            $name = chr(65 + ($n % 26)).$name;
            $n = (int) ($n / 26);
        }

        return $name;
    }

    /** @return array<int, string> */
    private function categoryPaths(): array
    {
        $categories = Category::where('level', 3)
            ->where('is_active', true)
            ->with(['parent.parent'])
            ->orderBy('sort_order')
            ->get();

        $paths = [];
        foreach ($categories as $cat) {
            $l3 = $cat->getTranslation('name', 'ar');
            $l2 = $cat->parent?->getTranslation('name', 'ar') ?? '';
            $l1 = $cat->parent?->parent?->getTranslation('name', 'ar') ?? '';

            $parts = array_filter([$l1, $l2, $l3], fn (string $p) => $p !== '');
            $paths[] = implode(' > ', $parts);
        }

        return $paths;
    }
}
