<!-- ========== Topbar Start ========== -->
<div class="navbar-custom">
    <div class="topbar container-fluid">
        <div class="d-flex align-items-center gap-lg-2 gap-1">

            <!-- Topbar Brand Logo -->
            <div class="logo-topbar">
                <!-- Logo light -->
                <a href="/" class="logo-light">
                    <span class="logo-lg" style="color: white; font-size: 16px; font-weight: bold; white-space: normal; line-height: 1.2;">
                        Radhikas Trade International
                    </span>
                    <span class="logo-sm" style="color: white; font-size: 16px; font-weight: bold;">
                        RTI
                    </span>
                </a>

                <!-- Logo Dark -->
                <a href="/" class="logo-dark">
                    <span class="logo-lg" style="color: #313a46; font-size: 16px; font-weight: bold; white-space: normal; line-height: 1.2;">
                        Radhikas Trade International
                    </span>
                    <span class="logo-sm" style="color: #313a46; font-size: 16px; font-weight: bold;">
                        RTI
                    </span>
                </a>
            </div>

            <!-- Sidebar Menu Toggle Button -->
            <button class="button-toggle-menu">
                <i class="ri-menu-2-fill"></i>
            </button>

            <!-- Horizontal Menu Toggle Button -->
            <button class="navbar-toggle" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <div class="lines">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </button>

           
        </div>

        <ul class="topbar-menu d-flex align-items-center gap-3">
        
            <li class="dropdown notification-list">
                <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <i class="ri-notification-3-fill fs-22"></i>
                    @if(auth()->check() && auth()->user()->unreadNotifications->count() > 0)
                        <span class="noti-icon-badge badge text-bg-danger rounded-circle" style="font-size: 10px; padding: 2px 5px; position: absolute; top: 0; right: 0; transform: translate(25%, -25%);">{{ auth()->user()->unreadNotifications->count() }}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated dropdown-lg py-0">
                    <div class="p-2 border-top-0 border-start-0 border-end-0 border-dashed border">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-0 fs-16 fw-semibold"> Notification</h6>
                            </div>
                            <div class="col-auto">
                                <a href="javascript: void(0);" id="mark-all-read" class="text-dark text-decoration-underline">
                                    <small>Mark All Read</small>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div style="max-height: 300px;" data-simplebar>
                        @if(auth()->check())
                            @forelse(auth()->user()->notifications()->take(10)->get() as $notification)
                            <a href="javascript:void(0);" class="dropdown-item p-0 notify-item {{ $notification->read_at ? 'read-noti' : 'unread-noti' }} card m-0 shadow-none">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="notify-icon bg-primary">
                                                <i class="ri-notification-3-line fs-18"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 text-truncate ms-2">
                                            <h5 class="noti-item-title fw-semibold fs-14">{{ $notification->data['title'] ?? 'Notification' }} <small class="fw-normal text-muted float-end ms-1">{{ $notification->created_at->diffForHumans() }}</small></h5>
                                            <small class="noti-item-subtitle text-muted">{{ $notification->data['message'] ?? '' }}</small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            @empty
                            <div class="p-3 text-center text-muted">
                                No notifications yet
                            </div>
                            @endforelse
                        @endif
                    </div>

                    <!-- All-->
                    <a href="javascript:void(0);" class="dropdown-item text-center text-primary text-decoration-underline fw-bold notify-item border-top border-light py-2">
                        View All
                    </a>

                </div>
            </li>
            
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const markReadBtn = document.getElementById('mark-all-read');
                    if(markReadBtn) {
                        markReadBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            fetch('{{ route("notifications.markRead") }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json'
                                }
                            }).then(() => window.location.reload());
                        });
                    }
                });
            </script>

        
            <li class="d-none d-sm-inline-block">
                <div class="nav-link" id="light-dark-mode" data-bs-toggle="tooltip" data-bs-placement="left" title="Theme Mode">
                    <i class="ri-moon-fill fs-22"></i>
                </div>
            </li>


            

            <li class="dropdown">
                <a class="nav-link dropdown-toggle arrow-none nav-user px-2" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <span class="account-user-avatar">
                        <img src="/images/users/avatar-1.jpg" alt="user-image" width="32" class="rounded-circle">
                    </span>
                    <span class="d-lg-flex flex-column gap-1 d-none">
                        <h5 class="my-0">
                            {{ auth()->user()->name }}
                        </h5>
                        <h6 class="my-0 fw-normal">Founder</h6>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated profile-dropdown">
                    <!-- item-->
                    <div class=" dropdown-header noti-title">
                        <h6 class="text-overflow m-0">Welcome !</h6>
                    </div>

                    <!-- item-->
                    <a href="{{ route('second', ['pages', 'profile']) }}" class="dropdown-item">
                        <i class="ri-account-circle-fill fs-18 align-middle me-1"></i>
                        <span>My Account</span>
                    </a>

                    <!-- item-->
                    <a href="{{ route('second', ['pages', 'profile']) }}" class="dropdown-item">
                        <i class="ri-settings-4-fill fs-18 align-middle me-1"></i>
                        <span>Settings</span>
                    </a>

                    <!-- item-->
                    <a href="{{ route('second', ['pages', 'faq']) }}" class="dropdown-item">
                        <i class="ri-customer-service-2-fill fs-18 align-middle me-1"></i>
                        <span>Support</span>
                    </a>

                    <!-- item-->
                    <a href="{{ route('second', ['auth', 'lock-screen']) }}" class="dropdown-item">
                        <i class="ri-lock-password-fill fs-18 align-middle me-1"></i>
                        <span>Lock Screen</span>
                    </a>

                    <!-- item-->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a onclick="event.preventDefault(); this.closest('form').submit();" class="dropdown-item">
                            <i class="ri-logout-box-fill fs-18 align-middle me-1"></i>
                            <span>Logout</span>
                        </a>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</div>
<!-- ========== Topbar End ========== -->
