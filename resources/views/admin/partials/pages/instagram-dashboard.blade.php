
{{-- Instagram Smart Comments Dashboard --}}
<div class="space-y-6">

  {{-- Header --}}
  <div class="bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 rounded-xl p-8 text-white shadow-lg">
    <h1 class="text-3xl font-bold mb-2">📸 اینستاگرام — کامنت هوشمند</h1>
    <p class="text-white/90">مدیریت جامع پست‌ها، کامنت‌ها، و دایرکت‌ها با تکنولوژی هوشمند</p>
  </div>

  {{-- Tab Navigation --}}
  <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700 overflow-x-auto pb-4">
    <button type="button" class="tab-btn active px-4 py-2 font-medium whitespace-nowrap border-b-2 border-pink-500 text-pink-600" onclick="switchTab('posts')">
      <i class="fa-solid fa-image mr-2"></i>تنظیمات پست‌ها
    </button>
    <button type="button" class="tab-btn px-4 py-2 font-medium whitespace-nowrap text-gray-600 hover:text-gray-900 dark:text-gray-400" onclick="switchTab('analytics')">
      <i class="fa-solid fa-chart-line mr-2"></i>تحلیل و گزارش
    </button>
    <button type="button" class="tab-btn px-4 py-2 font-medium whitespace-nowrap text-gray-600 hover:text-gray-900 dark:text-gray-400" onclick="switchTab('settings')">
      <i class="fa-solid fa-gear mr-2"></i>تنظیمات عمومی
    </button>
  </div>

  {{-- Posts Tab --}}
  <div id="posts-tab" class="tab-content space-y-6">
    
    {{-- Create New Post Button --}}
    <button type="button" class="bg-pink-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-pink-700 transition" onclick="openCreateModal()">
      <i class="fa-solid fa-plus mr-2"></i>ایجاد پست جدید
    </button>

    {{-- Template Selector --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <button type="button" class="template-card p-4 border-2 border-purple-300 rounded-lg hover:shadow-lg transition" onclick="selectTemplate('product')">
        <div class="text-3xl mb-2">📦</div>
        <div class="font-medium text-sm">محصول جدید</div>
      </button>
      <button type="button" class="template-card p-4 border-2 border-pink-300 rounded-lg hover:shadow-lg transition" onclick="selectTemplate('discount')">
        <div class="text-3xl mb-2">🏷️</div>
        <div class="font-medium text-sm">تخفیف ویژه</div>
      </button>
      <button type="button" class="template-card p-4 border-2 border-orange-300 rounded-lg hover:shadow-lg transition" onclick="selectTemplate('sample')">
        <div class="text-3xl mb-2">🎁</div>
        <div class="font-medium text-sm">نمونه رایگان</div>
      </button>
      <button type="button" class="template-card p-4 border-2 border-red-300 rounded-lg hover:shadow-lg transition" onclick="selectTemplate('custom')">
        <div class="text-3xl mb-2">✏️</div>
        <div class="font-medium text-sm">سفارشی</div>
      </button>
    </div>

    {{-- Posts List --}}
    <div class="space-y-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">پست‌های فعال</h3>
      <div class="space-y-3" id="posts-list">
        {{-- Posts will be loaded here --}}
      </div>
    </div>
  </div>

  {{-- Analytics Tab --}}
  <div id="analytics-tab" class="tab-content hidden space-y-6">
    
    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <div class="text-sm text-gray-600 dark:text-gray-400">کل کامنت‌ها</div>
        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">۲,۴۵۷</div>
        <div class="text-xs text-green-600 mt-1">↑ ۱۲% نسبت به هفته قبل</div>
      </div>
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <div class="text-sm text-gray-600 dark:text-gray-400">تبدیل دایرکت</div>
        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">۷۸%</div>
        <div class="text-xs text-green-600 mt-1">↑ ۵% بهتری</div>
      </div>
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <div class="text-sm text-gray-600 dark:text-gray-400">رزولوشن کامنت</div>
        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">۹۲%</div>
        <div class="text-xs text-green-600 mt-1">پاسخ‌های خودکار</div>
      </div>
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <div class="text-sm text-gray-600 dark:text-gray-400">دنبال‌کنندگان جدید</div>
        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">۵۶۲</div>
        <div class="text-xs text-green-600 mt-1">از طریق کامنت هوشمند</div>
      </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h4 class="font-semibold mb-4 text-gray-900 dark:text-white">روند کامنت‌ها (۳۰ روز)</h4>
        <svg class="w-full h-64" id="trend-chart"></svg>
      </div>
      <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h4 class="font-semibold mb-4 text-gray-900 dark:text-white">درصد تبدیل (Funnel)</h4>
        <svg class="w-full h-64" id="funnel-chart"></svg>
      </div>
    </div>
  </div>

  {{-- Settings Tab --}}
  <div id="settings-tab" class="tab-content hidden space-y-6">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">تنظیمات اینستاگرام</h3>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">توکن دسترسی اینستاگرام</label>
        <input type="password" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg" placeholder="توکن دسترسی شما">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">حساب اینستاگرام</label>
        <input type="text" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg" placeholder="@your_instagram_handle">
      </div>
      <button type="button" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700">
        ذخیره تنظیمات
      </button>
    </div>
  </div>

</div>

<script>
function switchTab(tabName) {
  // Hide all tabs
  document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active', 'border-b-2', 'border-pink-500', 'text-pink-600'));
  
  // Show selected tab
  document.getElementById(tabName + '-tab').classList.remove('hidden');
  event.target.closest('.tab-btn').classList.add('active', 'border-b-2', 'border-pink-500', 'text-pink-600');
}

function selectTemplate(template) {
  console.log('Selected template:', template);
  alert('قالب ' + template + ' انتخاب شد - به زودی پیاده سازی خواهد شد');
}

function openCreateModal() {
  alert('مدال ایجاد پست جدید - به زودی پیاده سازی خواهد شد');
}
</script>

<style>
  .tab-btn.active {
    @apply border-b-2 border-pink-500 text-pink-600;
  }
  
  .template-card {
    @apply cursor-pointer transition-all hover:shadow-lg;
  }
</style>
