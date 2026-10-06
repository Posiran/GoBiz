<?php
namespace Database\Seeders;
use App\Models\Attribute;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder {
    public function run(): void {
        $tree = [
            'cnc-machines' => ['ماشین‌آلات CNC و تراش', ['cnc-milling' => 'فرز CNC', 'cnc-lathe' => 'تراش CNC', 'laser-plasma' => 'برش لیزر و پلاسما', 'press-cut' => 'پرس و برش']],
            'production-lines' => ['خطوط تولید و بسته‌بندی', ['packaging' => 'ماشین بسته‌بندی', 'filling' => 'پرکن و دربند', 'full-line' => 'خط کامل تولید']],
            'construction-mining' => ['ماشین‌آلات راه‌سازی و معدنی', ['loader' => 'لودر و بولدوزر', 'excavator' => 'بیل مکانیکی', 'crane-forklift' => 'جرثقیل و لیفتراک']],
            'food-pharma' => ['صنایع غذایی و دارویی', ['food-machines' => 'ماشین‌آلات غذایی', 'pharma-machines' => 'ماشین‌آلات دارویی']],
            'plastic-rubber' => ['پلاستیک، لاستیک و تزریق', ['injection' => 'تزریق پلاستیک', 'extrusion' => 'اکسترودر', 'blow-molding' => 'بادی و ترموفرم']],
            'power-hvac' => ['برق، تأسیسات و ژنراتور', ['generator' => 'دیزل ژنراتور', 'transformer' => 'ترانسفورماتور', 'compressor' => 'کمپرسور و چیلر']],
            'factories' => ['کارخانه و سوله صنعتی', ['factory-sale' => 'کارخانه آماده فعالیت', 'warehouse' => 'سوله و انبار', 'industrial-land' => 'زمین صنعتی']],
            'business-startup' => ['فروش کسب‌وکار و استارتاپ', ['business-sale' => 'فروش کسب‌وکار فعال', 'startup-sale' => 'فروش استارتاپ', 'investment' => 'جذب سرمایه و شریک']],
            'spare-parts' => ['قطعات و ابزار یدکی', ['bearings' => 'بلبرینگ و گیربکس', 'hydraulic' => 'هیدرولیک و پنوماتیک', 'tools' => 'ابزار صنعتی']],
        ];
        $i = 0;
        foreach ($tree as $slug => [$name, $kids]) {
            $p = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'sort_order' => $i++]);
            $j = 0;
            foreach ($kids as $ks => $kn) Category::firstOrCreate(['slug' => $ks], ['name' => $kn, 'parent_id' => $p->id, 'sort_order' => $j++]);
        }
        // نمونه فیلد فنی (فیلتر اختصاصی هر دسته)
        $attrs = [
            'cnc-milling' => [['تعداد محور', null, 'number'], ['توان اسپیندل', 'kW', 'number'], ['کنترلر', null, 'select', ['Fanuc', 'Siemens', 'Mitsubishi', 'Heidenhain', 'سایر']]],
            'generator' => [['توان', 'kVA', 'number'], ['سوخت', null, 'select', ['دیزل', 'گاز', 'بنزین']], ['سایلنت', null, 'bool']],
            'cnc-lathe' => [['تعداد محور', null, 'number'], ['حداکثر قطر تراش', 'mm', 'number'], ['کنترلر', null, 'select', ['Fanuc', 'Siemens', 'Mitsubishi', 'سایر']]],
            'press-cut' => [['تناژ', 'تن', 'number'], ['طول کار', 'mm', 'number'], ['نوع محرک', null, 'select', ['هیدرولیک', 'مکانیکی', 'CNC']]],
            'packaging' => [['ظرفیت', 'بسته در ساعت', 'number'], ['نوع بسته‌بندی', null, 'select', ['پودر', 'مایع', 'جامد', 'گرانول']]],
            'loader' => [['وزن عملیاتی', 'تن', 'number'], ['توان موتور', 'اسب بخار', 'number'], ['ساعت کارکرد', 'ساعت', 'number']],
            'excavator' => [['وزن عملیاتی', 'تن', 'number'], ['توان موتور', 'اسب بخار', 'number'], ['ساعت کارکرد', 'ساعت', 'number']],
            'crane-forklift' => [['ظرفیت بارگیری', 'کیلوگرم', 'number'], ['سوخت', null, 'select', ['دیزل', 'برقی', 'گازسوز']], ['ساعت کارکرد', 'ساعت', 'number']],
            'transformer' => [['توان', 'kVA', 'number'], ['ولتاژ ورودی', 'kV', 'number'], ['نوع', null, 'select', ['روغنی', 'خشک']]],
            'compressor' => [['دبی', 'm³/h', 'number'], ['فشار', 'bar', 'number'], ['نوع', null, 'select', ['پیستونی', 'اسکرو', 'سانتریفیوژ']]],
            'injection' => [['نیروی قفل', 'تن', 'number'], ['نوع محرک', null, 'select', ['هیدرولیک', 'سروو', 'تمام‌برقی']]],
        ];
        foreach ($attrs as $slug => $rows) {
            $cat = Category::where('slug', $slug)->first();
            foreach ($rows as $r) Attribute::firstOrCreate(['category_id' => $cat->id, 'name' => $r[0]], ['unit' => $r[1], 'type' => $r[2], 'options' => $r[3] ?? null]);
        }
    }
}
