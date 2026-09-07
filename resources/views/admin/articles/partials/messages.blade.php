@if(session('success'))<div class="article-alert article-alert--success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="article-alert article-alert--error"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>@endif
@if($errors->any())<ul class="article-validation">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
