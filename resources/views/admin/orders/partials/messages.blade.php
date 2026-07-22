@if(session('success'))<div class="order-flash"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>@endif
@if(isset($errors) && $errors->any())<div class="order-errors"><strong>عملیات انجام نشد:</strong><ul class="mt-1 mr-4 list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
