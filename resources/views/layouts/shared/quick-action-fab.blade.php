@php
    $user = auth()->user();
    $canSales = !$user || $user->hasRole('Super Admin') || $user->can('create sales') || $user->can('view sales');
    $canCustomers = !$user || $user->hasRole('Super Admin') || $user->can('view customers');
    $canProducts = !$user || $user->hasRole('Super Admin') || $user->can('view products');
    $canPurchases = !$user || $user->hasRole('Super Admin') || $user->can('view purchases');
    $canStock = !$user || $user->hasRole('Super Admin') || $user->can('view stock adjustments') || $user->can('view journals');
@endphp

<!-- Quick Action Floating Action Button (FAB) -->
<div id="quick-action-fab-container" class="quick-action-fab-container">
    <!-- Click-outside backdrop -->
    <div id="quick-action-backdrop" class="quick-action-backdrop"></div>

    <!-- Quick Action Popup Card -->
    <div id="quick-action-menu" class="quick-action-menu" role="dialog" aria-modal="true" aria-labelledby="quickActionTitle">
        <!-- Header -->
        <div class="quick-action-header">
            <div class="d-flex align-items-center gap-2">
                <div class="quick-action-header-badge">
                    <i class="ri-flashlight-fill"></i>
                </div>
                <div>
                    <h6 class="quick-action-header-title mb-0" id="quickActionTitle">Quick Actions</h6>
                    <span class="quick-action-header-sub">Instant navigation</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <span class="quick-action-shortcut-badge d-none d-sm-inline-flex" title="Shortcut">Alt + Q</span>
                <button type="button" id="quick-action-close-btn" class="quick-action-close-btn" aria-label="Close menu">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        </div>

        <!-- Links Body -->
        <div class="quick-action-body">
            <div class="quick-action-list">
                @if($canSales)
                    <!-- POS Grid -->
                    <a href="{{ route('pos.grid') }}" class="quick-action-link">
                        <div class="quick-action-icon-box bg-purple-soft text-purple">
                            <i class="ri-grid-fill"></i>
                        </div>
                        <div class="quick-action-info">
                            <div class="d-flex align-items-center gap-2">
                                <span class="quick-action-name">POS Grid</span>
                                <span class="quick-action-tag bg-purple-subtle text-purple">Touch</span>
                            </div>
                            <span class="quick-action-desc">Visual touch POS terminal</span>
                        </div>
                        <i class="ri-arrow-right-s-line quick-action-chevron"></i>
                    </a>

                    <!-- POS Classic -->
                    <a href="{{ route('pos.index') }}" class="quick-action-link">
                        <div class="quick-action-icon-box bg-primary-soft text-primary">
                            <i class="ri-shopping-basket-fill"></i>
                        </div>
                        <div class="quick-action-info">
                            <span class="quick-action-name">POS Terminal</span>
                            <span class="quick-action-desc">Fast barcode & search register</span>
                        </div>
                        <i class="ri-arrow-right-s-line quick-action-chevron"></i>
                    </a>

                    <!-- Sales Invoices -->
                    <a href="{{ route('sales.index') }}" class="quick-action-link">
                        <div class="quick-action-icon-box bg-success-soft text-success">
                            <i class="ri-file-list-3-fill"></i>
                        </div>
                        <div class="quick-action-info">
                            <span class="quick-action-name">Sales Invoices</span>
                            <span class="quick-action-desc">All sales orders & invoices</span>
                        </div>
                        <i class="ri-arrow-right-s-line quick-action-chevron"></i>
                    </a>
                @endif

                @if($canCustomers)
                    <!-- Customers -->
                    <a href="{{ route('customers.index') }}" class="quick-action-link">
                        <div class="quick-action-icon-box bg-warning-soft text-warning">
                            <i class="ri-contacts-fill"></i>
                        </div>
                        <div class="quick-action-info">
                            <span class="quick-action-name">Customers</span>
                            <span class="quick-action-desc">Customer list, credit & dues</span>
                        </div>
                        <i class="ri-arrow-right-s-line quick-action-chevron"></i>
                    </a>
                @endif

                @if($canProducts)
                    <!-- Products -->
                    <a href="{{ route('products.index') }}" class="quick-action-link">
                        <div class="quick-action-icon-box bg-info-soft text-info">
                            <i class="ri-archive-fill"></i>
                        </div>
                        <div class="quick-action-info">
                            <span class="quick-action-name">Products</span>
                            <span class="quick-action-desc">Master products & inventory</span>
                        </div>
                        <i class="ri-arrow-right-s-line quick-action-chevron"></i>
                    </a>
                @endif

                @if($canPurchases)
                    <!-- Purchases -->
                    <a href="{{ route('purchases.index') }}" class="quick-action-link">
                        <div class="quick-action-icon-box bg-danger-soft text-danger">
                            <i class="ri-shopping-bag-3-fill"></i>
                        </div>
                        <div class="quick-action-info">
                            <span class="quick-action-name">Purchases</span>
                            <span class="quick-action-desc">Shipments & supplier orders</span>
                        </div>
                        <i class="ri-arrow-right-s-line quick-action-chevron"></i>
                    </a>
                @endif

                @if($canStock)
                    <!-- Stock Verification -->
                    <a href="{{ route('stock-verification.index') }}" class="quick-action-link">
                        <div class="quick-action-icon-box bg-teal-soft text-teal">
                            <i class="ri-shield-check-fill"></i>
                        </div>
                        <div class="quick-action-info">
                            <span class="quick-action-name">Stock Verification</span>
                            <span class="quick-action-desc">Warehouse integrity & audit</span>
                        </div>
                        <i class="ri-arrow-right-s-line quick-action-chevron"></i>
                    </a>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="quick-action-footer">
            <span class="quick-action-footer-brand"><i class="ri-sparkling-fill text-warning me-1"></i> Radhikas ERP</span>
            <span class="quick-action-footer-hint">Press <kbd>Esc</kbd> to close</span>
        </div>
    </div>

    <!-- Main FAB Trigger Button -->
    <button type="button" id="quick-action-fab-btn" class="quick-action-fab-btn" aria-expanded="false" aria-label="Toggle Quick Actions Menu" title="Quick Actions (Alt + Q)">
        <span class="quick-action-fab-pulse"></span>
        <span class="quick-action-fab-icon-wrap">
            <i class="ri-apps-2-fill quick-action-icon-open"></i>
            <i class="ri-close-line quick-action-icon-close"></i>
        </span>
    </button>
</div>

<style>
/* ─── Floating Action Button & Quick Actions Menu Styles ─────────────────── */
.quick-action-fab-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 1060;
    font-family: inherit;
}

@media print {
    .quick-action-fab-container {
        display: none !important;
    }
}

/* Backdrop */
.quick-action-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.25);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    z-index: 1058;
    opacity: 0;
    transition: opacity 0.2s ease-in-out;
}

.quick-action-fab-container.active .quick-action-backdrop {
    display: block;
    opacity: 1;
}

/* FAB Button */
.quick-action-fab-btn {
    position: relative;
    width: 54px;
    height: 54px;
    border-radius: 50%;
    border: none;
    background: linear-gradient(135deg, #727cf5 0%, #4e5aca 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 10px 25px -4px rgba(114, 124, 245, 0.5), 0 4px 10px -2px rgba(114, 124, 245, 0.3);
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
    z-index: 1061;
    outline: none;
    padding: 0;
}

.quick-action-fab-btn:hover {
    transform: translateY(-3px) scale(1.06);
    box-shadow: 0 14px 30px -4px rgba(114, 124, 245, 0.6), 0 6px 14px -2px rgba(114, 124, 245, 0.35);
}

.quick-action-fab-btn:active {
    transform: translateY(-1px) scale(0.98);
}

/* FAB Subtle Pulse Ring */
.quick-action-fab-pulse {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 50%;
    pointer-events: none;
    animation: fab-pulse-ring 3.5s infinite;
}

@keyframes fab-pulse-ring {
    0% {
        box-shadow: 0 0 0 0 rgba(114, 124, 245, 0.5);
    }
    50% {
        box-shadow: 0 0 0 12px rgba(114, 124, 245, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(114, 124, 245, 0);
    }
}

.quick-action-fab-container.active .quick-action-fab-pulse {
    display: none;
}

/* Rotating Icon Swap */
.quick-action-fab-icon-wrap {
    position: relative;
    width: 26px;
    height: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quick-action-icon-open,
.quick-action-icon-close {
    position: absolute;
    font-size: 24px;
    line-height: 1;
    transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease;
}

.quick-action-icon-open {
    opacity: 1;
    transform: rotate(0deg) scale(1);
}

.quick-action-icon-close {
    opacity: 0;
    transform: rotate(-90deg) scale(0.5);
}

.quick-action-fab-container.active .quick-action-icon-open {
    opacity: 0;
    transform: rotate(90deg) scale(0.5);
}

.quick-action-fab-container.active .quick-action-icon-close {
    opacity: 1;
    transform: rotate(0deg) scale(1);
}

/* Quick Action Popup Menu */
.quick-action-menu {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 320px;
    max-height: calc(100vh - 110px);
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.18), 0 0 0 1px rgba(15, 23, 42, 0.06);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    z-index: 1060;
    visibility: hidden;
    opacity: 0;
    transform: translateY(14px) scale(0.95);
    transform-origin: bottom right;
    transition: opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                transform 0.22s cubic-bezier(0.16, 1, 0.3, 1),
                visibility 0.22s;
    pointer-events: none;
}

.quick-action-fab-container.active .quick-action-menu {
    visibility: visible;
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
}

/* Header */
.quick-action-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid #f1f3f8;
    background: #ffffff;
}

.quick-action-header-badge {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: rgba(114, 124, 245, 0.12);
    color: #727cf5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.quick-action-header-title {
    font-size: 14px;
    font-weight: 700;
    color: #313a46;
    letter-spacing: -0.2px;
}

.quick-action-header-sub {
    font-size: 11px;
    color: #98a6ad;
    display: block;
    margin-top: -1px;
}

.quick-action-shortcut-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 6px;
    border-radius: 5px;
    background: #f3f4f8;
    color: #6c757d;
    border: 1px solid #e5e7eb;
}

.quick-action-close-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: #98a6ad;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.15s, color 0.15s;
}

.quick-action-close-btn:hover {
    background: #f1f3f8;
    color: #313a46;
}

/* Body / List */
.quick-action-body {
    padding: 8px;
    overflow-y: auto;
    max-height: 420px;
}

.quick-action-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

/* Action Link */
.quick-action-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 10px;
    border-radius: 10px;
    text-decoration: none;
    color: #313a46;
    transition: background-color 0.16s ease, transform 0.16s ease;
}

.quick-action-link:hover {
    background-color: #f6f8fb;
    transform: translateX(3px);
    color: #313a46;
}

/* Icon Box */
.quick-action-icon-box {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.quick-action-link:hover .quick-action-icon-box {
    transform: scale(1.1);
}

/* Soft Color Accents */
.bg-purple-soft {
    background-color: rgba(114, 103, 239, 0.12);
}
.text-purple {
    color: #7267ef !important;
}
.bg-purple-subtle {
    background-color: rgba(114, 103, 239, 0.15);
}

.bg-primary-soft {
    background-color: rgba(114, 124, 245, 0.12);
}
.bg-success-soft {
    background-color: rgba(10, 207, 151, 0.12);
}
.bg-warning-soft {
    background-color: rgba(255, 188, 0, 0.14);
}
.bg-info-soft {
    background-color: rgba(57, 175, 209, 0.12);
}
.bg-danger-soft {
    background-color: rgba(250, 92, 124, 0.12);
}
.bg-teal-soft {
    background-color: rgba(2, 168, 181, 0.12);
}
.text-teal {
    color: #02a8b5 !important;
}

/* Link Info */
.quick-action-info {
    flex-grow: 1;
    min-width: 0;
}

.quick-action-name {
    font-size: 13px;
    font-weight: 600;
    color: #313a46;
    display: block;
    line-height: 1.25;
}

.quick-action-desc {
    font-size: 11px;
    color: #98a6ad;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
    margin-top: 2px;
}

.quick-action-tag {
    font-size: 9.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 1px 5px;
    border-radius: 4px;
    line-height: 1.2;
}

.quick-action-chevron {
    color: #c0c7d0;
    font-size: 16px;
    transition: transform 0.16s ease, color 0.16s ease;
    flex-shrink: 0;
}

.quick-action-link:hover .quick-action-chevron {
    color: #727cf5;
    transform: translateX(2px);
}

/* Footer */
.quick-action-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: #fafbfe;
    border-top: 1px solid #f1f3f8;
    font-size: 11px;
    color: #98a6ad;
}

.quick-action-footer-brand {
    font-weight: 500;
}

.quick-action-footer-hint kbd {
    font-size: 10px;
    padding: 1px 4px;
    background: #eef2f7;
    border: 1px solid #dee2e6;
    border-radius: 3px;
    color: #495057;
}

/* Dark Mode Support (Hyper Theme Dark Mode) */
[data-bs-theme="dark"] .quick-action-menu,
[data-theme-mode="dark"] .quick-action-menu,
.dark-mode .quick-action-menu {
    background: #252b34;
    box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.08);
}

[data-bs-theme="dark"] .quick-action-header,
[data-theme-mode="dark"] .quick-action-header,
.dark-mode .quick-action-header {
    background: #252b34;
    border-color: rgba(255, 255, 255, 0.07);
}

[data-bs-theme="dark"] .quick-action-header-title,
[data-theme-mode="dark"] .quick-action-header-title,
.dark-mode .quick-action-header-title {
    color: #e3eaef;
}

[data-bs-theme="dark"] .quick-action-link,
[data-theme-mode="dark"] .quick-action-link,
.dark-mode .quick-action-link {
    color: #e3eaef;
}

[data-bs-theme="dark"] .quick-action-name,
[data-theme-mode="dark"] .quick-action-name,
.dark-mode .quick-action-name {
    color: #e3eaef;
}

[data-bs-theme="dark"] .quick-action-link:hover,
[data-theme-mode="dark"] .quick-action-link:hover,
.dark-mode .quick-action-link:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

[data-bs-theme="dark"] .quick-action-footer,
[data-theme-mode="dark"] .quick-action-footer,
.dark-mode .quick-action-footer {
    background: #20262e;
    border-color: rgba(255, 255, 255, 0.07);
}

/* Mobile Responsiveness */
@media (max-width: 576px) {
    .quick-action-fab-container {
        bottom: 18px;
        right: 18px;
    }
    .quick-action-menu {
        width: calc(100vw - 36px);
        right: 0;
        bottom: 66px;
    }
}
</style>

<script>
(function() {
    function initQuickActionFab() {
        const container = document.getElementById('quick-action-fab-container');
        const fabBtn = document.getElementById('quick-action-fab-btn');
        const closeBtn = document.getElementById('quick-action-close-btn');
        const backdrop = document.getElementById('quick-action-backdrop');

        if (!container || !fabBtn) return;

        function toggleMenu() {
            const isActive = container.classList.toggle('active');
            fabBtn.setAttribute('aria-expanded', isActive ? 'true' : 'false');
        }

        function closeMenu() {
            if (container.classList.contains('active')) {
                container.classList.remove('active');
                fabBtn.setAttribute('aria-expanded', 'false');
            }
        }

        fabBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleMenu();
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                closeMenu();
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function() {
                closeMenu();
            });
        }

        // Close on Escape & toggle on Alt+Q
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && container.classList.contains('active')) {
                closeMenu();
            } else if (e.altKey && (e.key === 'q' || e.key === 'Q')) {
                e.preventDefault();
                toggleMenu();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initQuickActionFab);
    } else {
        initQuickActionFab();
    }
})();
</script>
