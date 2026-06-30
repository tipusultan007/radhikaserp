@extends('layouts.vertical', ['page_title' => 'ChartJs Line', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

         <!-- start page title -->
         <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Line Chartjs</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Jidox</a></li>
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Charts</a></li>
                        <li class="breadcrumb-item active">Line Chartjs</li>
                    </ol>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-4">Interpolation</h4>

                        <div >
                            <div class="mt-3 chartjs-chart" style="height: 320px;">
                                <canvas id="interpolation-example" data-colors="#3e60d5,#47ad77"></canvas>
                            </div>
                        </div>
                    </div> <!-- end card body-->
                </div> <!-- end card -->
            </div><!-- end col-->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-4">Line</h4>

                        <div >
                            <div class="mt-3 chartjs-chart" style="height: 320px;">
                                <canvas id="line-example" data-colors="#3e60d5,#47ad77"></canvas>
                            </div>
                        </div>
                    </div> <!-- end card body-->
                </div> <!-- end card -->
            </div><!-- end col-->
        </div>
        <!-- end row-->


        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-4">Multi-Axes</h4>

                        <div >
                            <div class="mt-3 chartjs-chart" style="height: 320px;">
                                <canvas id="multi-axes-example" data-colors="#3e60d5,#47ad77"></canvas>
                            </div>
                        </div>
                    </div> <!-- end card body-->
                </div> <!-- end card -->
            </div><!-- end col-->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-4">Point Styling</h4>

                        <div >
                            <div class="mt-3 chartjs-chart" style="height: 320px;">
                                <canvas id="point-styling-example" data-colors="#3e60d5,#47ad77"></canvas>
                            </div>
                        </div>
                    </div> <!-- end card body-->
                </div> <!-- end card -->
            </div><!-- end col-->
        </div>
        <!-- end row-->

        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-4">Line Segment</h4>

                        <div >
                            <div class="mt-3 chartjs-chart" style="height: 320px;">
                                <canvas id="line-segment-example" data-colors="#3e60d5,#47ad77"></canvas>
                            </div>
                        </div>
                    </div> <!-- end card body-->
                </div> <!-- end card -->
            </div><!-- end col-->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-4">Stepped</h4>

                        <div >
                            <div class="mt-3 chartjs-chart" style="height: 320px;">
                                <canvas id="stepped-example" data-colors="#3e60d5,#47ad77"></canvas>
                            </div>
                        </div>
                    </div> <!-- end card body-->
                </div> <!-- end card -->
            </div><!-- end col-->
        </div>
        <!-- end row-->

    </div> <!-- container -->
@endsection

@section('script')
    @vite(['resources/js/pages/demo.chartjs-line.js'])
@endsection
