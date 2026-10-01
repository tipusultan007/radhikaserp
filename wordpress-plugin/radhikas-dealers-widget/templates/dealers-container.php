<?php
if (!defined('ABSPATH')) {
    exit;
}

$api_endpoint = !empty($settings['api_endpoint']) ? esc_url($settings['api_endpoint']) : 'https://erp.radhikastradeintl.com/api/dealers';
$per_page = !empty($settings['per_page']) ? (int) $settings['per_page'] : 12;
$default_type = !empty($settings['default_dealer_type']) ? sanitize_text_field($settings['default_dealer_type']) : 'all';
$show_search = !empty($settings['show_search']) && $settings['show_search'] === 'yes';
$search_placeholder = !empty($settings['search_placeholder']) ? esc_attr($settings['search_placeholder']) : esc_attr__('Search by dealer name, shop, or district...', 'radhikas-dealers');
$show_type_tabs = !empty($settings['show_type_tabs']) && $settings['show_type_tabs'] === 'yes';
$show_district_filter = !empty($settings['show_district_filter']) && $settings['show_district_filter'] === 'yes';
$show_counts = !empty($settings['show_counts']) && $settings['show_counts'] === 'yes';

$show_badge = !empty($settings['show_badge']) && $settings['show_badge'] === 'yes';
$show_phone = !empty($settings['show_phone']) && $settings['show_phone'] === 'yes';
$show_email = !empty($settings['show_email']) && $settings['show_email'] === 'yes';
$show_district_tag = !empty($settings['show_district_tag']) && $settings['show_district_tag'] === 'yes';
$show_address = !empty($settings['show_address']) && $settings['show_address'] === 'yes';
$highlight_special = !empty($settings['special_card_border_highlight']) && $settings['special_card_border_highlight'] === 'yes';
?>

<div id="<?php echo esc_attr($widget_id); ?>" class="rdw-container"
    data-api-endpoint="<?php echo esc_attr($api_endpoint); ?>"
    data-per-page="<?php echo esc_attr($per_page); ?>"
    data-default-type="<?php echo esc_attr($default_type); ?>"
    data-show-badge="<?php echo $show_badge ? '1' : '0'; ?>"
    data-show-phone="<?php echo $show_phone ? '1' : '0'; ?>"
    data-show-email="<?php echo $show_email ? '1' : '0'; ?>"
    data-show-district-tag="<?php echo $show_district_tag ? '1' : '0'; ?>"
    data-show-address="<?php echo $show_address ? '1' : '0'; ?>"
    data-highlight-special="<?php echo $highlight_special ? '1' : '0'; ?>">

    <!-- ── Filter & Search Bar ───────────────────────────────────────────── -->
    <?php if ($show_search || $show_district_filter || $show_type_tabs) : ?>
    <div class="rdw-controls-wrapper">
        <div class="rdw-filters-row">
            <?php if ($show_type_tabs) : ?>
            <div class="rdw-type-tabs" role="tablist">
                <button type="button" class="rdw-tab-btn <?php echo $default_type === 'all' ? 'active' : ''; ?>" data-type="all">
                    <span><?php esc_html_e('All Dealers', 'radhikas-dealers'); ?></span>
                    <span class="rdw-tab-count rdw-count-all">—</span>
                </button>
                <button type="button" class="rdw-tab-btn <?php echo $default_type === 'special_dealer' ? 'active' : ''; ?>" data-type="special_dealer">
                    <span class="rdw-star-icon">★</span>
                    <span><?php esc_html_e('Special Dealers', 'radhikas-dealers'); ?></span>
                    <span class="rdw-tab-count rdw-count-special">—</span>
                </button>
                <button type="button" class="rdw-tab-btn <?php echo $default_type === 'dealer' ? 'active' : ''; ?>" data-type="dealer">
                    <span><?php esc_html_e('Authorized Dealers', 'radhikas-dealers'); ?></span>
                    <span class="rdw-tab-count rdw-count-regular">—</span>
                </button>
            </div>
            <?php endif; ?>

            <div class="rdw-search-and-select">
                <?php if ($show_district_filter) : ?>
                <div class="rdw-district-select-wrap">
                    <svg class="rdw-icon-map" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <select class="rdw-district-select" aria-label="<?php esc_attr_e('Select District', 'radhikas-dealers'); ?>">
                        <option value="all"><?php esc_html_e('All Districts', 'radhikas-dealers'); ?></option>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($show_search) : ?>
                <div class="rdw-search-box-wrap">
                    <svg class="rdw-icon-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" class="rdw-search-input" placeholder="<?php echo $search_placeholder; ?>" autocomplete="off" />
                    <button type="button" class="rdw-search-clear" title="<?php esc_attr_e('Clear search', 'radhikas-dealers'); ?>">&times;</button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($show_counts) : ?>
        <div class="rdw-meta-bar">
            <span class="rdw-results-info"><?php esc_html_e('Loading dealer directory...', 'radhikas-dealers'); ?></span>
            <div class="rdw-loading-indicator" style="display: none;">
                <span class="rdw-spinner"></span>
                <span><?php esc_html_e('Updating...', 'radhikas-dealers'); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Dealers Grid ──────────────────────────────────────────────────── -->
    <div class="rdw-grid-wrapper">
        <div class="rdw-grid" aria-live="polite">
            <!-- Cards rendered dynamically via JS -->
            <?php for ($i = 0; $i < 6; $i++) : ?>
            <div class="rdw-card rdw-card-skeleton">
                <div class="rdw-skel-line rdw-skel-badge"></div>
                <div class="rdw-skel-line rdw-skel-title"></div>
                <div class="rdw-skel-line rdw-skel-sub"></div>
                <div class="rdw-skel-line rdw-skel-text"></div>
                <div class="rdw-skel-line rdw-skel-btn"></div>
            </div>
            <?php endfor; ?>
        </div>

        <!-- ── Empty State ───────────────────────────────────────────────── -->
        <div class="rdw-empty-state" style="display: none;">
            <div class="rdw-empty-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="M21 21l-4.35-4.35"></path>
                    <path d="M8 11h6"></path>
                </svg>
            </div>
            <h4 class="rdw-empty-title"><?php esc_html_e('No dealers found', 'radhikas-dealers'); ?></h4>
            <p class="rdw-empty-desc"><?php esc_html_e('We could not find any dealers matching your current filter criteria. Try searching with different keywords or clear the filters.', 'radhikas-dealers'); ?></p>
            <button type="button" class="rdw-btn-reset-filters"><?php esc_html_e('Reset All Filters', 'radhikas-dealers'); ?></button>
        </div>

        <!-- ── Error State ───────────────────────────────────────────────── -->
        <div class="rdw-error-state" style="display: none;">
            <div class="rdw-error-icon">⚠️</div>
            <h4 class="rdw-error-title"><?php esc_html_e('Unable to load dealer directory', 'radhikas-dealers'); ?></h4>
            <p class="rdw-error-desc"><?php esc_html_e('Please check your network connection or verify the ERP API endpoint in the widget settings.', 'radhikas-dealers'); ?></p>
            <button type="button" class="rdw-btn-retry"><?php esc_html_e('Retry', 'radhikas-dealers'); ?></button>
        </div>
    </div>

    <!-- ── Pagination ────────────────────────────────────────────────────── -->
    <div class="rdw-pagination-wrapper" style="display: none;">
        <div class="rdw-pagination" role="navigation" aria-label="<?php esc_attr_e('Dealer directory pagination', 'radhikas-dealers'); ?>">
            <!-- Rendered dynamically via JS -->
        </div>
    </div>

</div>
