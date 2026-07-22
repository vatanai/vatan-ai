<?php

namespace App\Http\Controllers;

use App\Models\HomeSection;
use App\Services\HomeBuilder\HomeSectionRenderService;

class HomeController extends Controller
{
    public function __construct(protected HomeSectionRenderService $renderService)
    {
    }

    /**
     * نمایش صفحه اصلی اپ.
     * Sectionهای این صفحه دیگر در کد ثابت نیستند — از پنل مدیریت («مدیریت صفحه هوم»، فیچر Home Builder)
     * به‌صورت داینامیک مدیریت می‌شوند. فقط Sectionهای published و به‌ترتیب position واکشی می‌شوند.
     */
    public function index()
    {
        $pageKey = config('home_builder.default_page_key', HomeSection::DEFAULT_PAGE_KEY);

        $sections = HomeSection::forPage($pageKey)->published()->ordered()->get();

        $renderedSections = $this->renderService->prepareMany($sections);

        return view('app.home', compact('renderedSections'));
    }
}
