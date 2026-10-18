<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vatan\Content\Models\PromptVersion;

class PromptController extends Controller
{
    public const LAYERS = [
        'product' => 'شناخت محصول', 'fidelity' => 'وفاداری محصول و لوگو', 'style' => 'انتخاب سبک', 'hook' => 'ساخت هوک',
        'scenario' => 'ساخت سناریو', 'motion' => 'انتخاب حرکت', 'music' => 'پیشنهاد موسیقی', 'caption' => 'ساخت کپشن',
        'cta' => 'دعوت به اقدام', 'qc' => 'کنترل کیفیت', 'analysis' => 'تحلیل عملکرد', 'experiment' => 'پیشنهاد آزمایش بعدی',
        'calendar' => 'تقویم محتوایی', 'story' => 'استوری‌های همراه پست', 'visual' => 'تصویر و کلیپ هوش مصنوعی', 'master' => 'پرامپت جامع ساخت پست',
    ];

    public const HELP = [
        'product' => 'شناخت محصول وطن و مشتری هدفش؛ ورودی همه‌ی لایه‌های بعدی.',
        'fidelity' => 'قانون حفظ شکل، رنگ، لوگو و نوشته‌ی محصول در هر تصویر یا کلیپ هوش مصنوعی.',
        'style' => 'تشخیص سبک ویدیوی مرجع از روی کانتکت‌شیت؛ هنگام «تحلیل ویدیوها» اجرا می‌شود.',
        'hook' => 'ساخت ۳ هوک برای ۱.۵ ثانیه‌ی اول؛ هنگام پیشنهاد نسخه‌ی جایگزین استفاده می‌شود.',
        'scenario' => 'تبدیل ایده‌ی ردیف تقویم به صحنه‌های قابل رندر (JSON صحنه‌ها).',
        'motion' => 'انتخاب حرکت و افکت هر صحنه بر اساس سبک.',
        'music' => 'انتخاب موسیقی از کتابخانه‌ی مجاز؛ کلاد موسیقی نمی‌سازد.',
        'caption' => 'نوشتن کپشن انسانی هنگام «ساخت». در صورت خطای هوش، متن آماده‌ی تقویم استفاده می‌شود.',
        'cta' => 'دعوت به اقدام و کلمه‌ی کلیدی کامنت که به سناریوی دایرکت اینستاگرام هوشمند وصل است.',
        'qc' => 'چک‌لیست کنترل کیفیت خروجی قبل از ارسال برای تأیید.',
        'analysis' => 'تحلیل هفتگی عملکرد با تعداد نمونه و میزان اطمینان.',
        'experiment' => 'پیشنهاد آزمایش بعدی بر اساس داده.',
        'calendar' => 'ساخت تقویم ماهانه از پرسش‌نامه و عملکرد ماه قبل.',
        'story' => 'طراحی ۱ تا ۳ استوری مخصوص هر پست.',
        'visual' => 'پرامپت تصویر/کلیپ برای مدل‌های تصویری و ویدیویی (fal).',
        'master' => 'قالب کامل دستور ساخت هر پست؛ بخش‌ها با «## عنوان» جدا می‌شوند. هر پست در صفحه‌ی خودش نسخه‌ی شخصی این قالب را دارد (ویرایش، حذف و افزودن بخش). صحنه‌ها، استوری و کپشن با همین پرامپت نوشته می‌شوند و پرامپت نهایی کنار هر نسخه‌ی ساخته‌شده ذخیره می‌شود.',
    ];

    /** لایه‌های پایه + لایه‌های افزوده‌ی ادمین */
    public static function layers(int $ws): array
    {
        $custom = (array) \Vatan\Content\Models\Setting::get('custom_layers', [], $ws);

        return self::LAYERS + collect($custom)->mapWithKeys(fn ($v, $k) => [$k => $v['title'] ?? $k])->all();
    }

    public function addLayer(Request $request)
    {
        $d = $request->validate(['key' => 'required|regex:/^[a-z][a-z0-9_]{1,30}$/', 'title' => 'required|string|max:80', 'help' => 'nullable|string|max:500']);
        abort_if(isset(self::layers($this->ws())[$d['key']]), 422, 'این شناسه قبلاً هست.');
        $custom = (array) \Vatan\Content\Models\Setting::get('custom_layers', [], $this->ws());
        $custom[$d['key']] = ['title' => $d['title'], 'help' => $d['help'] ?? null];
        \Vatan\Content\Models\Setting::put('custom_layers', $custom, $this->ws());

        return redirect()->route('cf.prompts', ['layer' => $d['key']])->with('success', 'لایه‌ی «'.$d['title'].'» ساخته شد؛ متنش را بنویسید و فعال کنید.');
    }

    public function deleteLayer(string $layer)
    {
        abort_if(isset(self::LAYERS[$layer]), 422, 'لایه‌های پایه حذف نمی‌شوند؛ می‌توانید نسخه‌هایشان را حذف یا عوض کنید.');
        $custom = (array) \Vatan\Content\Models\Setting::get('custom_layers', [], $this->ws());
        unset($custom[$layer]);
        \Vatan\Content\Models\Setting::put('custom_layers', $custom, $this->ws());
        PromptVersion::where('workspace_id', $this->ws())->where('layer', $layer)->delete();

        return redirect()->route('cf.prompts')->with('success', 'لایه حذف شد.');
    }

    public function destroy(PromptVersion $prompt)
    {
        abort_unless($prompt->workspace_id === $this->ws(), 404);
        if ($prompt->status === 'active') {
            return back()->with('warning', 'نسخه‌ی فعال حذف نمی‌شود؛ اول نسخه‌ی دیگری را فعال کنید.');
        }
        $layer = $prompt->layer;
        $prompt->delete();

        return redirect()->route('cf.prompts', ['layer' => $layer])->with('success', 'نسخه حذف شد.');
    }

    public function index(Request $request)
    {
        $all = PromptVersion::where('workspace_id', $this->ws())->orderBy('layer')->orderByDesc('version')->get()->groupBy('layer');

        $custom = (array) \Vatan\Content\Models\Setting::get('custom_layers', [], $this->ws());

        return view('cf::prompts.index', [
            'cfTitle' => 'آزمایشگاه پرامپت', 'cfIcon' => 'fa-flask', 'cfHelp' => 'prompts', 'testResult' => session('cf_test'),
            'help' => self::HELP + collect($custom)->map(fn ($v) => $v['help'] ?? 'لایه‌ی افزوده‌ی ادمین.')->all(), 'custom' => array_keys($custom),
            'cfSubtitle' => 'پرامپت‌ها لایه‌لایه و نسخه‌دار هستند. هیچ تغییری بدون تأیید شما فعال نمی‌شود.',
            'layers' => self::layers($this->ws()), 'prompts' => $all, 'selected' => $request->get('layer', 'master'),
        ]);
    }

    public function store(Request $request)
    {
        $layers = self::layers($this->ws());
        $d = $request->validate(['layer' => 'required|in:'.implode(',', array_keys($layers)), 'body' => 'required|string', 'notes' => 'nullable|string|max:2000', 'activate' => 'nullable|boolean']);
        $activate = (bool) ($d['activate'] ?? false);
        unset($d['activate']);
        $prev = PromptVersion::where('workspace_id', $this->ws())->where('layer', $d['layer'])->orderByDesc('version')->first();
        $pv = PromptVersion::create($d + ['workspace_id' => $this->ws(), 'title' => $layers[$d['layer']], 'version' => ($prev->version ?? 0) + 1,
            'status' => 'draft', 'parent_id' => $prev?->id, 'editor' => $this->adminName()]);
        if ($activate) {
            return $this->activate($pv);
        }

        return redirect()->route('cf.prompts', ['layer' => $d['layer']])->with('success', 'پیش‌نویس جدید ذخیره شد.');
    }

    public function activate(PromptVersion $prompt)
    {
        abort_unless($prompt->workspace_id === $this->ws(), 404);
        DB::transaction(function () use ($prompt) {
            PromptVersion::where('workspace_id', $prompt->workspace_id)->where('layer', $prompt->layer)->where('status', 'active')->update(['status' => 'archived']);
            $prompt->update(['status' => 'active']);
        });

        return back()->with('success', "نسخه‌ی {$prompt->version} فعال شد (نسخه‌ی قبلی بایگانی و قابل بازگشت است).");
    }

    /** اجرای آزمایشی یک نسخه روی نمونه‌ی ورودی (بدون تغییر چیزی) */
    public function test(Request $request, PromptVersion $prompt, \Vatan\Content\Services\Ai $ai)
    {
        abort_unless($prompt->workspace_id === $this->ws(), 404);
        $vars = json_decode((string) $request->input('vars', '{}'), true) ?: [];
        [$system, $user] = array_pad(preg_split('/^-{3,}\s*USER\s*-{3,}\s*$/mu', $prompt->body, 2), 2, '');
        $fill = fn ($t) => preg_replace_callback('/\{\{\s*([a-z_\.]+)\s*\}\}/u', fn ($m) => is_array($v = data_get($vars, $m[1])) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) ($v ?? ''), $t);
        $r = $ai->text('writer', 'prompt.test', $fill($system), $fill($user) ?: 'نمونه', [], ['max_tokens' => 900]);

        return redirect()->route('cf.prompts', ['layer' => $prompt->layer])->with('cf_test', ['version' => $prompt->version, 'ok' => (bool) $r,
            'text' => $r['content'] ?? ('هوش در دسترس نبود: '.$ai->lastError.' — روی سرور اصلی (با پل اتصال) دوباره امتحان کنید.')]);
    }
}
