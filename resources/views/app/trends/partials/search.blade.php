<form class="trends-search" id="trends-search-form" action="{{ route('products.index') }}" method="GET" autocomplete="off">
  <div class="trends-search-input-wrap">
    <i class="fa-solid fa-magnifying-glass trends-search-icon" aria-hidden="true"></i>
    <input
      id="trends-search-input"
      class="trends-search-input"
      type="search"
      name="search"
      placeholder="فقط بنویس دنبال چی هستی"
      aria-label="جست‌وجو در محصولات"
      aria-controls="trends-search-results"
      aria-autocomplete="list"
      spellcheck="false"
    >
    <button class="trends-search-submit" type="submit" aria-label="جست‌وجو">
      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
    </button>
  </div>
  <div class="trends-search-results" id="trends-search-results" role="listbox" hidden></div>
</form>
