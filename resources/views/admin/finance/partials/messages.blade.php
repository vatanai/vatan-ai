@if(session('success'))
  <div class="finance-alert success"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
@endif
@if($errors->any())
  <div class="finance-alert danger">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
  </div>
@endif
