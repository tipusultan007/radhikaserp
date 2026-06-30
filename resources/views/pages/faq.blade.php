@extends('layouts.vertical', ['page_title' => 'FAQ', 'mode' => $mode ?? '', 'demo' => $demo ?? ''])

@section('css')
@endsection

@section('content')
    <!-- Start Content-->
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box justify-content-between d-flex align-items-md-center flex-md-row flex-column">
                    <h4 class="page-title">FAQ</h4>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Jidox</a></li>
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Pages</a></li>
                        <li class="breadcrumb-item active">FAQ</li>
                    </ol>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row justify-content-center">
                            <div class="col-md-6 ">
                                <div class="text-center">
                                    <h3 class="mt-3">Frequently Asked Questions</h3>
                                    <p class="text-muted mt-3"> Do you have a question about your subscription, a recent order, products, shipping or you want to suggest a new magazine? Here you can find some helpful answers to frequently asked questions (FAQ).</p>

                                    <button type="button" class="btn btn-success mt-2"><i class="ri-mail-line me-1"></i> Email us your question</button>
                                    <button type="button" class="btn btn-info mt-2 ms-1"><i class="ri-twitter-line me-1"></i> Send us a tweet</button>
                                </div>
                            </div><!-- end col -->
                        </div><!-- end row -->


                        <div class="row mt-4 justify-content-center">
                            <div class="col-xl-5">
                                <div class="p-2">
                                    <div class="accordion" id="accordionExample">
                                        <div class="accordion-item border rounded mb-2">
                                            <div class="accordion-header">
                                                <a href="#" class="accordion-button bg-light fw-medium text-dark" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                                    What is Lorem Ipsum?
                                                </a>
                                            </div>

                                            <div id="collapseOne" class="collapse show" data-bs-parent="#accordionExample">
                                                <div class="p-3">
                                                    <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
                                                    <p class="mb-0">Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item border rounded mb-2">
                                            <div class="accordion-header">
                                                <a href="#" class="accordion-button bg-light fw-medium text-dark" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                                    Is safe use Lorem Ipsum?
                                                </a>
                                            </div>
                                            <div id="collapseTwo" class="collapse" data-bs-parent="#accordionExample">
                                                <div class="p-3">
                                                    <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
                                                    <p class="mb-0">Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item border rounded mb-2">
                                            <div class="accordion-header">
                                                <a href="#" class="accordion-button bg-light fw-medium text-dark" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                                    Why use Lorem Ipsum?
                                                </a>
                                            </div>
                                            <div id="collapseThree" class="collapse" data-bs-parent="#accordionExample">
                                                <div class="p-3">
                                                    <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
                                                    <p class="mb-0">Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- accordian end -->

                                </div>
                            </div>
                            <!-- end col -->

                            <div class="col-xl-5">
                                <div class="p-2">
                                    <div class="accordion custom-accordion" id="accordionExample1">
                                        <div class="accordion-item border rounded mb-2">
                                            <div class="accordion-header">
                                                <a href="#" class="accordion-button bg-light fw-medium text-dark" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="true" aria-controls="collapseFour">
                                                    License & Copyright
                                                </a>
                                            </div>

                                            <div id="collapseFour" class="collapse show" data-bs-parent="#accordionExample1">
                                                <div class="p-3">
                                                    <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
                                                    <p class="mb-0">Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item border rounded mb-2">
                                            <div class="accordion-header">
                                                <a href="#" class="accordion-button bg-light fw-medium text-dark" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                                    How many variations exist?
                                                </a>
                                            </div>
                                            <div id="collapseFive" class="collapse" data-bs-parent="#accordionExample1">
                                                <div class="p-3">
                                                    <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
                                                    <p class="mb-0">Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod.</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item border rounded mb-2">
                                            <div class="accordion-header">
                                                <a href="#" class="accordion-button bg-light fw-medium text-dark" class="collapsed" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                                    Why use Lorem Ipsum?
                                                </a>
                                            </div>
                                            <div id="collapseSix" class="collapse" data-bs-parent="#accordionExample1">
                                                <div class="p-3">
                                                    <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
                                                    <p class="mb-0">Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- accordian end -->
                                </div>
                            </div>
                            <!-- end col -->
                        </div>
                        <!-- end row -->
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- container -->
@endsection
