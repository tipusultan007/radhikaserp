<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts.shared/title-meta', ['title' => 'Log In'])
    @include('layouts.shared/head-css')
    @vite(['resources/js/head.js'])
    <style>
        .login-bg {
            background: url('https://images.unsplash.com/photo-1557804506-669a67965ba0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
            position: relative;
        }
        .login-bg::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(14,42,71,0.9) 0%, rgba(0,69,158,0.8) 100%);
        }
        .login-bg-content {
            position: relative;
            z-index: 1;
        }
        .right-pane {
            background-color: #f8f9fc;
        }
        .login-form-container {
            max-width: 440px;
            width: 100%;
            background-color: #ffffff;
            border: 1px solid #e9ecef;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }
        .login-logo {
            max-height: 150px;
            width: auto;
        }
        .modern-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .modern-input {
            background-color: #ffffff;
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 15px;
            color: #212529;
            transition: all 0.2s ease-in-out;
        }
        .modern-input:focus {
            background-color: #ffffff;
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            outline: 0;
        }
        .input-group-merge .modern-input {
            border-right: none;
        }
        .input-group-merge .input-group-text {
            background-color: #ffffff;
            border: 1px solid #ced4da;
            border-left: none;
            border-radius: 0 8px 8px 0;
            padding-right: 16px;
            color: #6c757d;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }
        .input-group-merge:focus-within .input-group-text {
            background-color: #ffffff;
            border-color: #86b7fe;
        }
        .btn-login {
            background-color: #0d6efd;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }
        .btn-login:hover {
            background-color: #0b5ed7;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        .brand-text {
            font-size: 2.25rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: white;
            line-height: 1.3;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
</head>

<body class="authentication-bg pb-0">
    <div class="row g-0 vh-100">
        <!-- Left Side: Image/Brand -->
        <div class="col-lg-6 d-none d-lg-flex login-bg align-items-center justify-content-center flex-column text-center px-5">
            <div class="login-bg-content">
                <h1 class="brand-text mb-3">Radhikas Trade International</h1>
                <p class="text-white-50 fs-5 fw-light mt-2">Empowering your business with modern,<br>intelligent ERP solutions.</p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="col-lg-6 col-12 d-flex align-items-center justify-content-center flex-column right-pane px-3">
            
            <!-- Logo Above Card -->
            <div class="mb-4 text-center">
                <img src="{{ asset('logo.png') }}" alt="Radhikas Trade International" class="login-logo">
            </div>

            <div class="login-form-container p-4 p-sm-5">
                <div class="mb-4 text-center">
                    <h3 class="fw-semibold text-dark mb-2">Sign In</h3>
                    <p class="text-muted">Enter your email and password to access the admin panel.</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger rounded-3 shadow-sm border-0 bg-danger text-white">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-4">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="emailaddress" class="modern-label">Email Address</label>
                        <input class="form-control modern-input" type="email" id="emailaddress" required placeholder="name@example.com" name="email">
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="modern-label mb-0">Password</label>
                        </div>
                        <div class="input-group input-group-merge shadow-sm-none">
                            <input type="password" id="password" class="form-control modern-input" placeholder="Enter your password" name="password" required>
                            <div class="input-group-text" data-password="false">
                                <span class="password-eye"></span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="checkbox-signin" checked>
                            <label class="form-check-label text-muted ms-1" for="checkbox-signin">Remember me</label>
                        </div>
                    </div>

                    <div class="d-grid mb-4">
                        <button class="btn btn-primary btn-login text-white shadow-sm" type="submit">
                            Sign In
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-5">
                    <span class="text-muted fs-14">
                        <script>document.write(new Date().getFullYear())</script> © Radhikas Trade International
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- App js -->
    @vite(['resources/js/app.js'])
    @include('layouts.shared/footer-script')

</body>

</html>
