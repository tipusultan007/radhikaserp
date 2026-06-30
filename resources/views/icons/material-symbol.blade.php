@extends('layouts.vertical', ['page_title' => 'Material Symbol', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">Material Icons</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Jidox</a></li>
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Icons</a></li>
                        <li class="breadcrumb-item active">Material Icons</li>
                    </ol>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="header-title mb-4">All Icons<a class="badge badge-soft-primary ms-2" href="https://fonts.google.com/icons" target="_blank">Google Icon</a></h4>
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <i class="material-symbols-outlined">home</i>
                            <span>home</span>
                            <code> .material-symbols-outlined </code>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-4">
                            <i class="material-symbols-outlined fill-1">home</i>
                            <span>home</span>
                            <code> .material-symbols-outlined .fill-1</code>
                        </div>
                        <div class="row icons-list-demo" id="icons"> </div>
                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div>
        </div>

    </div> <!-- container -->
@endsection

@section('script')
    @vite(['resources/js/pages/demo.material-symbol.js'])
@endsection
