{{-- Admin dashboard scripts — split into partials during standardization (behavior-identical) --}}
@include('admin.partials.scripts.shamsi-calendar')
<script src="{{ asset('admin/js/main.js') }}"></script>
@include('admin.partials.scripts.dashboard-main-js')
<script src="{{ asset('admin/js/crm-api.js') }}"></script>
