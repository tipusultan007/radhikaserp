@extends('layouts.vertical')

@section('content')
<div class="container-fluid">
    <div class="row pt-3">
        <!-- Sidebar -->
        <div class="col-md-3">
            @include('reports.partials.sidebar')
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            @yield('report_content')
        </div>
    </div>
</div>
@endsection
