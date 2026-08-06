{{-- Admin dashboard scripts — split into partials during standardization (behavior-identical) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
@include('admin.partials.scripts.shamsi-calendar')
<script src="{{ asset('admin/js/main.js') }}"></script>
@include('admin.partials.scripts.dashboard-main-js')
<script src="{{ asset('admin/js/crm.js') }}"></script>
<script src="{{ asset('admin/js/crm-api.js') }}"></script>
