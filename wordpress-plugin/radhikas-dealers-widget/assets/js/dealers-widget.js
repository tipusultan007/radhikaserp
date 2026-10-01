/**
 * Radhikas Dealers Directory - Frontend Widget Script
 * AJAX live search, district filtering, type tabs, and interactive pagination.
 */
(function () {
    'use strict';

    function initDealersWidget(container) {
        if (!container || container.dataset.initialized === 'true') {
            return;
        }
        container.dataset.initialized = 'true';

        var apiEndpoint = container.dataset.apiEndpoint || 'https://erp.radhikastradeintl.com/api/dealers';
        var perPage = parseInt(container.dataset.perPage, 10) || 12;
        var currentType = container.dataset.defaultType || 'all';
        var currentPage = 1;
        var currentDistrict = 'all';
        var searchQuery = '';
        var searchTimeout = null;

        var showBadge = container.dataset.showBadge !== '0';
        var showPhone = container.dataset.showPhone !== '0';
        var showEmail = container.dataset.showEmail !== '0';
        var showDistrictTag = container.dataset.showDistrictTag !== '0';
        var showAddress = container.dataset.showAddress !== '0';
        var highlightSpecial = container.dataset.highlightSpecial !== '0';

        // DOM elements
        var grid = container.querySelector('.rdw-grid');
        var emptyState = container.querySelector('.rdw-empty-state');
        var errorState = container.querySelector('.rdw-error-state');
        var paginationWrapper = container.querySelector('.rdw-pagination-wrapper');
        var paginationEl = container.querySelector('.rdw-pagination');
        var searchInput = container.querySelector('.rdw-search-input');
        var searchClearBtn = container.querySelector('.rdw-search-clear');
        var districtSelect = container.querySelector('.rdw-district-select');
        var tabBtns = container.querySelectorAll('.rdw-tab-btn');
        var resultsInfo = container.querySelector('.rdw-results-info');
        var loadingIndicator = container.querySelector('.rdw-loading-indicator');
        var resetBtn = container.querySelector('.rdw-btn-reset-filters');
        var retryBtn = container.querySelector('.rdw-btn-retry');

        // Tab count elements
        var countAll = container.querySelector('.rdw-count-all');
        var countSpecial = container.querySelector('.rdw-count-special');
        var countRegular = container.querySelector('.rdw-count-regular');

        var districtsLoaded = false;

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function buildApiUrl(page) {
            var url = new URL(apiEndpoint, window.location.href);
            url.searchParams.set('page', page || currentPage);
            url.searchParams.set('per_page', perPage);

            if (currentType && currentType !== 'all') {
                url.searchParams.set('type', currentType);
            }
            if (currentDistrict && currentDistrict !== 'all') {
                url.searchParams.set('district', currentDistrict);
            }
            if (searchQuery && searchQuery.trim() !== '') {
                url.searchParams.set('search', searchQuery.trim());
            }

            return url.toString();
        }

        function showSkeletons() {
            if (!grid) return;
            emptyState.style.display = 'none';
            errorState.style.display = 'none';
            paginationWrapper.style.display = 'none';
            if (loadingIndicator) loadingIndicator.style.display = 'inline-flex';

            var skeletons = '';
            for (var i = 0; i < perPage; i++) {
                skeletons += '<div class="rdw-card rdw-card-skeleton">' +
                    '<div class="rdw-skel-line rdw-skel-badge"></div>' +
                    '<div class="rdw-skel-line rdw-skel-title"></div>' +
                    '<div class="rdw-skel-line rdw-skel-sub"></div>' +
                    '<div class="rdw-skel-line rdw-skel-text"></div>' +
                    '<div class="rdw-skel-line rdw-skel-btn"></div>' +
                    '</div>';
            }
            grid.innerHTML = skeletons;
            grid.classList.remove('rdw-updating');
        }

        function renderCard(dealer) {
            var isSpecial = dealer.is_special || dealer.type === 'special_dealer';
            var cardClass = 'rdw-card' + (isSpecial && highlightSpecial ? ' rdw-is-special' : '');

            var headerHtml = '<div class="rdw-card-header">';
            
            // District tag
            if (showDistrictTag && dealer.district) {
                headerHtml += '<span class="rdw-district-badge">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>' +
                    escapeHtml(dealer.district) +
                    '</span>';
            } else {
                headerHtml += '<span></span>';
            }

            // Dealer Type Badge
            if (showBadge) {
                if (isSpecial) {
                    headerHtml += '<span class="rdw-badge rdw-badge-special">' +
                        '<span class="rdw-badge-icon">★</span> ' + escapeHtml(dealer.type_label || 'Special Dealer') +
                        '</span>';
                } else {
                    headerHtml += '<span class="rdw-badge rdw-badge-dealer">' +
                        '<span class="rdw-badge-icon">✓</span> ' + escapeHtml(dealer.type_label || 'Authorized Dealer') +
                        '</span>';
                }
            }
            headerHtml += '</div>';

            // Body
            var title = dealer.company ? escapeHtml(dealer.company) : escapeHtml(dealer.name);
            var subtitle = dealer.company && dealer.name ? escapeHtml(dealer.name) : '';

            var bodyHtml = '<div class="rdw-card-body">' +
                '<h3 class="rdw-company">' + title + '</h3>';

            if (subtitle) {
                bodyHtml += '<div class="rdw-dealer-name">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>' +
                    '<span>' + subtitle + '</span>' +
                    '</div>';
            }

            if (showAddress && dealer.address) {
                bodyHtml += '<div class="rdw-address" title="' + escapeHtml(dealer.address) + '">' +
                    escapeHtml(dealer.address) +
                    '</div>';
            }
            bodyHtml += '</div>';

            // Footer / Actions
            var footerHtml = '';
            if (showPhone || showEmail) {
                footerHtml = '<div class="rdw-card-footer">';
                if (showPhone && dealer.phone) {
                    footerHtml += '<a href="tel:' + encodeURIComponent(dealer.phone) + '" class="rdw-btn-call" title="Call ' + escapeHtml(dealer.name) + '">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>' +
                        '<span>' + escapeHtml(dealer.phone) + '</span>' +
                        '</a>';
                }
                if (showEmail && dealer.email) {
                    footerHtml += '<a href="mailto:' + encodeURIComponent(dealer.email) + '" class="rdw-btn-email" title="Email ' + escapeHtml(dealer.name) + '">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>' +
                        '</a>';
                }
                footerHtml += '</div>';
            }

            return '<div class="' + cardClass + '">' + headerHtml + bodyHtml + footerHtml + '</div>';
        }

        function renderPagination(meta) {
            if (!paginationWrapper || !paginationEl) return;

            if (!meta || meta.total <= meta.per_page) {
                paginationWrapper.style.display = 'none';
                return;
            }

            paginationWrapper.style.display = 'flex';
            var totalPages = meta.last_page;
            var current = meta.current_page;
            var html = '';

            // Previous Button
            html += '<button type="button" class="rdw-page-btn rdw-page-prev" data-page="' + (current - 1) + '"' +
                (current <= 1 ? ' disabled' : '') + ' aria-label="Previous Page">&laquo; Prev</button>';

            var maxVisiblePages = 5;
            var startPage = Math.max(1, current - 2);
            var endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            if (startPage > 1) {
                html += '<button type="button" class="rdw-page-btn" data-page="1">1</button>';
                if (startPage > 2) {
                    html += '<span class="rdw-page-ellipsis">&hellip;</span>';
                }
            }

            for (var p = startPage; p <= endPage; p++) {
                html += '<button type="button" class="rdw-page-btn' + (p === current ? ' active' : '') + '" data-page="' + p + '">' + p + '</button>';
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += '<span class="rdw-page-ellipsis">&hellip;</span>';
                }
                html += '<button type="button" class="rdw-page-btn" data-page="' + totalPages + '">' + totalPages + '</button>';
            }

            // Next Button
            html += '<button type="button" class="rdw-page-btn rdw-page-next" data-page="' + (current + 1) + '"' +
                (current >= totalPages ? ' disabled' : '') + ' aria-label="Next Page">Next &raquo;</button>';

            paginationEl.innerHTML = html;
        }

        function populateDistricts(districts) {
            if (!districtSelect || districtsLoaded || !districts || !districts.length) return;
            districtsLoaded = true;

            var currentVal = districtSelect.value;
            var optionsHtml = '<option value="all">All Districts</option>';
            districts.forEach(function (dist) {
                if (dist) {
                    optionsHtml += '<option value="' + escapeHtml(dist) + '">' + escapeHtml(dist) + '</option>';
                }
            });
            districtSelect.innerHTML = optionsHtml;
            districtSelect.value = currentVal;
        }

        function updateCounts(counts) {
            if (!counts) return;
            if (countAll) countAll.textContent = counts.all || 0;
            if (countSpecial) countSpecial.textContent = counts.special_dealer || 0;
            if (countRegular) countRegular.textContent = counts.dealer || 0;
        }

        function fetchDealers(page, isBackground) {
            page = page || 1;
            currentPage = page;

            if (!isBackground) {
                showSkeletons();
            } else if (loadingIndicator) {
                loadingIndicator.style.display = 'inline-flex';
                grid.classList.add('rdw-updating');
            }

            var url = buildApiUrl(page);

            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                }
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Network error: ' + response.status);
                }
                return response.json();
            })
            .then(function (res) {
                if (loadingIndicator) loadingIndicator.style.display = 'none';
                grid.classList.remove('rdw-updating');

                if (!res || !res.success || !res.data) {
                    throw new Error('Invalid API response format');
                }

                // Populate districts if present
                if (res.districts) {
                    populateDistricts(res.districts);
                }

                // Update type tab counts
                if (res.counts) {
                    updateCounts(res.counts);
                }

                var items = res.data;
                var meta = res.meta || {};

                // Update result count text
                if (resultsInfo) {
                    if (meta.total > 0) {
                        resultsInfo.textContent = 'Showing ' + (meta.from || 1) + ' - ' + (meta.to || items.length) + ' of ' + meta.total + ' dealers';
                    } else {
                        resultsInfo.textContent = 'No dealers found';
                    }
                }

                if (items.length === 0) {
                    grid.innerHTML = '';
                    emptyState.style.display = 'block';
                    paginationWrapper.style.display = 'none';
                    return;
                }

                emptyState.style.display = 'none';
                errorState.style.display = 'none';

                var cardsHtml = '';
                items.forEach(function (dealer) {
                    cardsHtml += renderCard(dealer);
                });
                grid.innerHTML = cardsHtml;

                // Render Pagination
                renderPagination(meta);
            })
            .catch(function (err) {
                console.error('[Radhikas Dealers Widget Error]:', err);
                if (loadingIndicator) loadingIndicator.style.display = 'none';
                grid.classList.remove('rdw-updating');
                grid.innerHTML = '';
                errorState.style.display = 'block';
                paginationWrapper.style.display = 'none';
                if (resultsInfo) resultsInfo.textContent = 'Failed to load directory';
            });
        }

        // ── Event Handlers ──────────────────────────────────────────────────

        // Type Tabs
        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var type = this.dataset.type;
                if (currentType === type) return;

                tabBtns.forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');

                currentType = type;
                currentPage = 1;
                fetchDealers(1, false);
            });
        });

        // District Dropdown
        if (districtSelect) {
            districtSelect.addEventListener('change', function () {
                currentDistrict = this.value;
                currentPage = 1;
                fetchDealers(1, false);
            });
        }

        // Search Input (Debounced)
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var val = this.value;
                if (searchClearBtn) {
                    searchClearBtn.style.display = val.length > 0 ? 'block' : 'none';
                }

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    searchQuery = val;
                    currentPage = 1;
                    fetchDealers(1, true);
                }, 350);
            });

            if (searchClearBtn) {
                searchClearBtn.addEventListener('click', function () {
                    searchInput.value = '';
                    searchClearBtn.style.display = 'none';
                    searchQuery = '';
                    currentPage = 1;
                    fetchDealers(1, false);
                    searchInput.focus();
                });
            }
        }

        // Pagination Click Delegation
        if (paginationWrapper) {
            paginationWrapper.addEventListener('click', function (e) {
                var btn = e.target.closest('.rdw-page-btn');
                if (!btn || btn.disabled || btn.classList.contains('active')) return;

                var page = parseInt(btn.dataset.page, 10);
                if (page > 0) {
                    fetchDealers(page, false);

                    // Smooth scroll to container top
                    var yOffset = -80;
                    var y = container.getBoundingClientRect().top + window.pageYOffset + yOffset;
                    window.scrollTo({ top: y, behavior: 'smooth' });
                }
            });
        }

        // Reset Filters Button
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                if (searchInput) {
                    searchInput.value = '';
                    if (searchClearBtn) searchClearBtn.style.display = 'none';
                }
                searchQuery = '';
                currentDistrict = 'all';
                if (districtSelect) districtSelect.value = 'all';
                currentType = 'all';
                tabBtns.forEach(function (b) {
                    b.classList.toggle('active', b.dataset.type === 'all');
                });
                currentPage = 1;
                fetchDealers(1, false);
            });
        }

        // Retry Button
        if (retryBtn) {
            retryBtn.addEventListener('click', function () {
                fetchDealers(currentPage, false);
            });
        }

        // Initial Load
        fetchDealers(1, false);
    }

    // Auto-init on page load and Elementor ready hook
    function initAllWidgets() {
        document.querySelectorAll('.rdw-container').forEach(function (container) {
            initDealersWidget(container);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllWidgets);
    } else {
        initAllWidgets();
    }

    // Hook into Elementor Frontend
    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/radhikas_dealers_directory.default', function ($scope) {
            var container = $scope[0].querySelector('.rdw-container');
            if (container) {
                initDealersWidget(container);
            }
        });
    }
})();
