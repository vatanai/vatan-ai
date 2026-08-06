<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAiModelRequest;
use App\Http\Requests\Admin\UpdateAiModelRequest;
use App\Models\AiModel;
use App\Models\Category;
use App\Support\ProviderStatus;
use App\Models\AiProviderSetting;
use App\Services\AiProviderConnectionTester;
use App\Services\AiCatalogSyncService;
use App\Services\ServiceCreditOverviewService;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AiModelController extends Controller
{
    public function index(ExchangeRateService $exchangeRate)
    {
        $models = AiModel::latest()->get();
        return view('admin.ai-models.index', ['models' => $models, 'exchange' => $exchangeRate->usdToIrr()]);
    }

    public function providers(ServiceCreditOverviewService $creditOverview)
    {
        $models = AiModel::latest()->get();
        $providerStatus = ProviderStatus::all();
        $providerSettings = collect(ProviderStatus::PROVIDERS)->mapWithKeys(function (string $provider) {
            try {
                return [$provider => AiProviderSetting::forProvider($provider)];
            } catch (\Throwable) {
                return [$provider => null];
            }
        });
        $creditAccounts = $creditOverview->get()['accounts']->keyBy('slug');
        return view('admin.ai-models.providers', compact('models', 'providerStatus', 'providerSettings', 'creditAccounts'));
    }

    public function createProvider()
    {
        return view('admin.ai-models.create-provider', ['providers' => ProviderStatus::PROVIDERS]);
    }

    /**
     * تغییر وضعیت روشن/خاموش یک provider (Liara یا OpenRouter).
     *
     * این متد در پنل ادمین یک کلید کوچک بالای لیست مدل‌ها می‌سازد؛
     * وقتی کاربر روی آن می‌زند، وضعیت provider در Cache دائمی ذخیره می‌شود
     * و کل سیستم (فرم ثبت محصول، روتر تولید تصویر و ...) فوراً از آن پیروی می‌کند.
     * کد OpenRouterService یا LiaraAiService دست‌نخورده باقی می‌ماند — فقط
     * یک flag روشن/خاموش عوض می‌شود.
     *
     * مسیر: POST /admin/ai-models/toggle-provider
     */
    public function toggleProvider(Request $request)
    {
        $data = $request->validate([
            'provider' => 'required|string|in:' . implode(',', ProviderStatus::PROVIDERS),
            'enabled'  => 'required|boolean',
        ]);

        ProviderStatus::setEnabled($data['provider'], (bool) $data['enabled']);

        $label = match ($data['provider']) {
            'liara' => 'لیارا', 'openrouter' => 'OpenRouter', 'fal' => 'Fal.ai', 'replicate' => 'Replicate',
        };
        $stateFa = $data['enabled'] ? 'روشن' : 'خاموش';

        return redirect()->route('admin.ai-models.providers')
            ->with('success', "سرویس {$label} با موفقیت {$stateFa} شد.");
    }

    public function toggleModel(AiModel $aiModel)
    {
        $aiModel->update(['is_active' => ! $aiModel->is_active]);

        return redirect()->route('admin.ai-models.index')
            ->with('success', 'وضعیت مدل با موفقیت تغییر کرد.');
    }

    public function updateProviderSettings(Request $request)
    {
        $data = $request->validate([
            'provider' => 'required|string|in:' . implode(',', ProviderStatus::PROVIDERS),
            'api_key' => 'nullable|string|max:5000',
            'base_url' => 'nullable|url|max:500',
            'webhook_secret' => 'nullable|string|max:5000',
            'timeout' => 'required|integer|min:5|max:3600',
            'max_retries' => 'required|integer|min:0|max:10',
            'webhook_enabled' => 'nullable|boolean',
            'clear_api_key' => 'nullable|boolean',
            'clear_webhook_secret' => 'nullable|boolean',
        ]);

        $setting = AiProviderSetting::firstOrNew(['provider' => $data['provider']]);
        $setting->fill([
            'base_url' => $data['base_url'] ?? null,
            'timeout' => $data['timeout'],
            'max_retries' => $data['max_retries'],
            'webhook_enabled' => (bool) ($data['webhook_enabled'] ?? false),
        ]);
        if ($data['clear_api_key'] ?? false) $setting->api_key = null;
        elseif (filled($data['api_key'] ?? null)) $setting->api_key = $data['api_key'];
        if ($data['clear_webhook_secret'] ?? false) $setting->webhook_secret = null;
        elseif (filled($data['webhook_secret'] ?? null)) $setting->webhook_secret = $data['webhook_secret'];
        $setting->save();

        return redirect()->route('admin.ai-models.providers')->with('success', 'تنظیمات provider با رمزنگاری ذخیره شد.');
    }

    public function testProvider(Request $request, AiProviderConnectionTester $tester)
    {
        $data = $request->validate([
            'provider' => 'required|string|in:' . implode(',', ProviderStatus::PROVIDERS),
        ]);

        try {
            $tester->test($data['provider']);
            return redirect()->route('admin.ai-models.providers')->with('success', 'اتصال provider با موفقیت بررسی شد.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.ai-models.providers')->withErrors(['provider' => $e->getMessage()]);
        }
    }

    public function syncCatalog(Request $request, AiCatalogSyncService $syncer)
    {
        $data = $request->validate([
            'provider' => 'required|string|in:fal,replicate,all',
        ]);

        try {
            $result = $syncer->sync($data['provider']);
            $total = collect($result)->sum('total');
            return redirect()->route('admin.ai-models.providers')
                ->with('success', "کاتالوگ با موفقیت همگام شد؛ {$total} مدل عکس/ویدیو بررسی شد.");
        } catch (\Throwable $e) {
            return redirect()->route('admin.ai-models.providers')->withErrors(['catalog' => $e->getMessage()]);
        }
    }

    public function create()
    {
        return view('admin.ai-models.create', ['categories' => Category::roots()->active()->orderBy('sort_order')->orderBy('name_fa')->get()]);
    }

    public function store(StoreAiModelRequest $request)
    {
        // 💡 نکته تستی: اگر می‌خواهید ببینید دقیقاً چه داده‌هایی از فرم ارسال می‌شود،
        // خط زیر را از کامنت خارج کنید تا ارسال فرم متوقف و داده‌ها چاپ شوند:
        // dd($request->all());

        // قوانین اعتبارسنجی به App\Http\Requests\Admin\StoreAiModelRequest منتقل شد.
        $validatedData = $request->validated();

        // ذخیره‌سازی هوشمند با مقادیر پیش‌فرض مپ شده
        $model = AiModel::create([
            'name'                 => $validatedData['name'],
            'openrouter_model_id'  => $validatedData['openrouter_model_id'],
            'external_model_id'    => $validatedData['external_model_id'] ?? $validatedData['openrouter_model_id'],
            'external_version'     => $validatedData['external_version'] ?? null,
            'provider_name'        => $validatedData['provider_name'],
            'provider'             => $request->input('provider', 'openrouter'),
            'liara_plan'           => $request->input('provider') === 'liara' ? $request->input('liara_plan') : null,
            'output_modality'      => $validatedData['output_modality'],
            'task_type'            => $validatedData['task_type'] ?? null,
            'supports_image_input' => $request->input('supports_image_input', '0') == '1',
            'supports_face_identity' => $request->boolean('supports_face_identity'),
            'supports_multiple_faces' => $request->boolean('supports_multiple_faces'),
            'supports_audio'       => $request->boolean('supports_audio'),
            'supports_video_input' => $request->boolean('supports_video_input'),
            'cost_per_generation'  => $validatedData['cost_per_generation'],
            'cost_per_generation_usd' => $validatedData['cost_per_generation_usd'] ?? null,
            'default_width'        => $validatedData['default_width'] ?: 1024,
            'default_height'       => $validatedData['default_height'] ?: 1024,
            'max_resolution'       => $validatedData['max_resolution'] ?? null,
            'max_duration'         => $validatedData['max_duration'] ?? null,
            'default_parameters'   => $validatedData['default_parameters'],
            'input_schema'         => $this->decodeJson($validatedData['input_schema'] ?? null),
            'capability_config'    => $this->decodeJson($validatedData['capability_config'] ?? null),
            'recommended_category_ids' => array_values(array_map('intval', (array) $request->input('category_ids', []))),
            'pricing_config'       => $this->decodeJson($validatedData['pricing_config'] ?? null),
            'pricing_type'         => $validatedData['pricing_type'] ?? null,
            'commercial_use'       => $request->has('commercial_use') ? $request->boolean('commercial_use') : null,
            'supports_webhook'     => $request->boolean('supports_webhook'),
            'terms_url'            => $validatedData['terms_url'] ?? null,
            'data_retention_notes' => $validatedData['data_retention_notes'] ?? null,
            'description'          => $validatedData['description'],
            'is_active'            => $request->input('is_active', '1') == '1',
        ]);

        // آپلود تصویر مدل در صورت وجود
        if ($request->hasFile('model_image')) {
            $image = $request->file('model_image');
            $safeName = str_replace(['/', '\\', ':', '*'], '-', $model->openrouter_model_id);
            $filename = $safeName . '.' . $image->getClientOriginalExtension();

            $destinationPath = public_path('uploads/models');
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }
            $image->move($destinationPath, $filename);
        }

        return redirect()->route('admin.ai-models.index')
            ->with('success', 'مدل هوش مصنوعی جدید با موفقیت به همراه تصویر اختصاصی ثبت و در پایگاه داده ذخیره شد.');
    }

    public function edit($id)
    {
        $model = AiModel::findOrFail($id);
        $categories = Category::roots()->active()->orderBy('sort_order')->orderBy('name_fa')->get();
        return view('admin.ai-models.edit', compact('model', 'categories'));
    }

    public function update(UpdateAiModelRequest $request, $id)
    {
        $model = AiModel::findOrFail($id);

        // قوانین اعتبارسنجی به App\Http\Requests\Admin\UpdateAiModelRequest منتقل شد.
        $validatedData = $request->validated();

        // آپلود تصویر جدید و جایگزینی آن در صورت انتخاب توسط کاربر
        if ($request->hasFile('model_image')) {
            $image = $request->file('model_image');
            $safeName = str_replace(['/', '\\', ':', '*'], '-', $validatedData['openrouter_model_id']);

            $destinationPath = public_path('uploads/models');

            // حذف فایل‌های با فرمت قبلی برای جلوگیری از تداخل نام همسان
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $oldFile = $destinationPath . '/' . $safeName . '.' . $ext;
                if (File::exists($oldFile)) {
                    File::delete($oldFile);
                }
            }

            $filename = $safeName . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $filename);
        }

        $model->update([
            'name'                 => $validatedData['name'],
            'openrouter_model_id'  => $validatedData['openrouter_model_id'],
            'external_model_id'    => $validatedData['external_model_id'] ?? $validatedData['openrouter_model_id'],
            'external_version'     => $validatedData['external_version'] ?? null,
            'provider_name'        => $validatedData['provider_name'],
            'provider'             => $request->input('provider', $model->provider ?? 'openrouter'),
            'liara_plan'           => $request->input('provider', $model->provider) === 'liara' ? $request->input('liara_plan') : null,
            'output_modality'      => $validatedData['output_modality'],
            'task_type'            => $validatedData['task_type'] ?? null,
            'supports_image_input' => $validatedData['supports_image_input'],
            'supports_face_identity' => $request->boolean('supports_face_identity'),
            'supports_multiple_faces' => $request->boolean('supports_multiple_faces'),
            'supports_audio'       => $request->boolean('supports_audio'),
            'supports_video_input' => $request->boolean('supports_video_input'),
            'cost_per_generation'  => $validatedData['cost_per_generation'],
            'cost_per_generation_usd' => $validatedData['cost_per_generation_usd'] ?? null,
            'default_width'        => $validatedData['default_width'] ?? 1024,
            'default_height'       => $validatedData['default_height'] ?? 1024,
            'max_resolution'       => $validatedData['max_resolution'] ?? null,
            'max_duration'         => $validatedData['max_duration'] ?? null,
            'default_parameters'   => $validatedData['default_parameters'],
            'input_schema'         => $this->decodeJson($validatedData['input_schema'] ?? null),
            'capability_config'    => $this->decodeJson($validatedData['capability_config'] ?? null),
            'recommended_category_ids' => array_values(array_map('intval', (array) $request->input('category_ids', []))),
            'pricing_config'       => $this->decodeJson($validatedData['pricing_config'] ?? null),
            'pricing_type'         => $validatedData['pricing_type'] ?? null,
            'commercial_use'       => $request->has('commercial_use') ? $request->boolean('commercial_use') : null,
            'supports_webhook'     => $request->boolean('supports_webhook'),
            'terms_url'            => $validatedData['terms_url'] ?? null,
            'data_retention_notes' => $validatedData['data_retention_notes'] ?? null,
            'description'          => $validatedData['description'],
            'is_active'            => $validatedData['is_active'],
        ]);

        return redirect()->route('admin.ai-models.index')
            ->with('success', 'اطلاعات مدل هوش مصنوعی با موفقیت به‌روزرسانی شد.');
    }

    public function destroy($id)
    {
        $model = AiModel::findOrFail($id);

        // حذف فیزیکی عکس مدل از سرور هنگام حذف از دیتابیس
        $safeName = str_replace(['/', '\\', ':', '*'], '-', $model->openrouter_model_id);
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            $file = public_path('uploads/models/' . $safeName . '.' . $ext);
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        $model->delete();

        return redirect()->route('admin.ai-models.index')
            ->with('success', 'مدل هوش مصنوعی با موفقیت از پایگاه داده حذف شد.');
    }

    private function decodeJson(?string $value): ?array
    {
        if (blank($value)) return null;
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }
}
