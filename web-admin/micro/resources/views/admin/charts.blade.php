@extends('admin')

@section('title', 'Charts')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Charts</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Charts</li>
    </ol>

    <div class="card mb-4">
        <div class="card-body">
            Chart.js is used to generate the charts. See 
            <a href="https://www.chartjs.org/docs/latest/" target="_blank">Chart.js documentation</a> for customization.
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-chart-area me-1"></i> Area Chart Example</div>
        <div class="card-body"><canvas id="myAreaChart" width="100%" height="30"></canvas></div>
        <div class="card-footer small text-muted">Updated yesterday at 11:59 PM</div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-chart-bar me-1"></i> Bar Chart Example</div>
                <div class="card-body"><canvas id="myBarChart" width="100%" height="50"></canvas></div>
                <div class="card-footer small text-muted">Updated yesterday at 11:59 PM</div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-chart-pie me-1"></i> Pie Chart Example</div>
                <div class="card-body"><canvas id="myPieChart" width="100%" height="50"></canvas></div>
                <div class="card-footer small text-muted">Updated yesterday at 11:59 PM</div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>
    <script src="{{ asset('admin/assets/demo/chart-area-demo.js') }}"></script>
    <script src="{{ asset('admin/assets/demo/chart-bar-demo.js') }}"></script>
    <script src="{{ asset('admin/assets/demo/chart-pie-demo.js') }}"></script>
@endsection
