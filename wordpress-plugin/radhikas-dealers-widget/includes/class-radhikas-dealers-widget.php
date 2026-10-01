<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;

class Radhikas_Dealers_Widget extends Widget_Base {

    public function get_name() {
        return 'radhikas_dealers_directory';
    }

    public function get_title() {
        return esc_html__('Dealers Directory Grid', 'radhikas-dealers');
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return ['radhikas-widgets', 'general'];
    }

    public function get_keywords() {
        return ['dealer', 'dealers', 'grid', 'cards', 'api', 'directory', 'radhikas', 'special dealer'];
    }

    public function get_script_depends() {
        return ['radhikas-dealers-js'];
    }

    public function get_style_depends() {
        return ['radhikas-dealers-css'];
    }

    protected function register_controls() {

        // ══════════════════════════════════════════════════════════════════════
        // SECTION: API & Data Source
        // ══════════════════════════════════════════════════════════════════════
        $this->start_controls_section(
            'section_api',
            [
                'label' => esc_html__('API Configuration', 'radhikas-dealers'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'api_endpoint',
            [
                'label' => esc_html__('ERP API Endpoint URL', 'radhikas-dealers'),
                'type' => Controls_Manager::TEXT,
                'default' => 'https://erp.radhikastradeintl.com/api/dealers',
                'placeholder' => 'https://erp.radhikastradeintl.com/api/dealers',
                'description' => esc_html__('Full URL to the Radhikas ERP /api/dealers endpoint.', 'radhikas-dealers'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'per_page',
            [
                'label' => esc_html__('Dealers Per Page', 'radhikas-dealers'),
                'type' => Controls_Manager::NUMBER,
                'min' => 3,
                'max' => 60,
                'step' => 3,
                'default' => 12,
            ]
        );

        $this->add_control(
            'default_dealer_type',
            [
                'label' => esc_html__('Default Dealer Filter', 'radhikas-dealers'),
                'type' => Controls_Manager::SELECT,
                'default' => 'all',
                'options' => [
                    'all' => esc_html__('All Dealers & Special Dealers', 'radhikas-dealers'),
                    'dealer' => esc_html__('Authorized Dealers Only', 'radhikas-dealers'),
                    'special_dealer' => esc_html__('Special Dealers Only', 'radhikas-dealers'),
                ],
            ]
        );

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════════
        // SECTION: Filters & Search Controls
        // ══════════════════════════════════════════════════════════════════════
        $this->start_controls_section(
            'section_filters',
            [
                'label' => esc_html__('Search & Filter Bar', 'radhikas-dealers'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_search',
            [
                'label' => esc_html__('Show Search Input', 'radhikas-dealers'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'radhikas-dealers'),
                'label_off' => esc_html__('Hide', 'radhikas-dealers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'search_placeholder',
            [
                'label' => esc_html__('Search Placeholder Text', 'radhikas-dealers'),
                'type' => Controls_Manager::TEXT,
                'default' => esc_html__('Search by dealer name, shop, or district...', 'radhikas-dealers'),
                'condition' => [
                    'show_search' => 'yes',
                ],
                'label_block' => true,
            ]
        );

        $this->add_control(
            'show_type_tabs',
            [
                'label' => esc_html__('Show Type Filter Tabs', 'radhikas-dealers'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'radhikas-dealers'),
                'label_off' => esc_html__('Hide', 'radhikas-dealers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_district_filter',
            [
                'label' => esc_html__('Show District Dropdown', 'radhikas-dealers'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'radhikas-dealers'),
                'label_off' => esc_html__('Hide', 'radhikas-dealers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_counts',
            [
                'label' => esc_html__('Show Result Count / Status', 'radhikas-dealers'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'radhikas-dealers'),
                'label_off' => esc_html__('Hide', 'radhikas-dealers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════════
        // SECTION: Card Elements
        // ══════════════════════════════════════════════════════════════════════
        $this->start_controls_section(
            'section_card_elements',
            [
                'label' => esc_html__('Card Elements', 'radhikas-dealers'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_badge',
            [
                'label' => esc_html__('Show Dealer Badge', 'radhikas-dealers'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_phone',
            [
                'label' => esc_html__('Show Phone & Call Button', 'radhikas-dealers'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_email',
            [
                'label' => esc_html__('Show Email (if available)', 'radhikas-dealers'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_district_tag',
            [
                'label' => esc_html__('Show District Badge', 'radhikas-dealers'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_address',
            [
                'label' => esc_html__('Show Address', 'radhikas-dealers'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════════
        // STYLE TAB: Grid Layout
        // ══════════════════════════════════════════════════════════════════════
        $this->start_controls_section(
            'section_style_grid',
            [
                'label' => esc_html__('Grid Layout', 'radhikas-dealers'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'radhikas-dealers'),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1 Column',
                    '2' => '2 Columns',
                    '3' => '3 Columns',
                    '4' => '4 Columns',
                ],
                'selectors' => [
                    '{{WRAPPER}} .rdw-grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
                ],
            ]
        );

        $this->add_responsive_control(
            'grid_gap',
            [
                'label' => esc_html__('Grid Gap', 'radhikas-dealers'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 24,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rdw-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════════
        // STYLE TAB: Dealer Card Styling
        // ══════════════════════════════════════════════════════════════════════
        $this->start_controls_section(
            'section_style_card',
            [
                'label' => esc_html__('Card Design', 'radhikas-dealers'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_bg_color',
            [
                'label' => esc_html__('Card Background', 'radhikas-dealers'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rdw-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'special_card_border_highlight',
            [
                'label' => esc_html__('Highlight Special Dealers', 'radhikas-dealers'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => esc_html__('Gives Special Dealer cards a distinctive gold accent line.', 'radhikas-dealers'),
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'card_border',
                'selector' => '{{WRAPPER}} .rdw-card',
            ]
        );

        $this->add_control(
            'card_border_radius',
            [
                'label' => esc_html__('Border Radius', 'radhikas-dealers'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 16,
                    'right' => 16,
                    'bottom' => 16,
                    'left' => 16,
                    'unit' => 'px',
                    'isLinked' => true,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rdw-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'card_box_shadow',
                'selector' => '{{WRAPPER}} .rdw-card',
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label' => esc_html__('Card Padding', 'radhikas-dealers'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default' => [
                    'top' => 24,
                    'right' => 24,
                    'bottom' => 24,
                    'left' => 24,
                    'unit' => 'px',
                    'isLinked' => true,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rdw-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════════
        // STYLE TAB: Typography & Colors
        // ══════════════════════════════════════════════════════════════════════
        $this->start_controls_section(
            'section_style_typography',
            [
                'label' => esc_html__('Typography & Colors', 'radhikas-dealers'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'heading_company',
            [
                'label' => esc_html__('Company / Shop Name', 'radhikas-dealers'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'company_color',
            [
                'label' => esc_html__('Color', 'radhikas-dealers'),
                'type' => Controls_Manager::COLOR,
                'default' => '#1e293b',
                'selectors' => [
                    '{{WRAPPER}} .rdw-company' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'company_typography',
                'selector' => '{{WRAPPER}} .rdw-company',
            ]
        );

        $this->add_control(
            'heading_dealer_name',
            [
                'label' => esc_html__('Dealer Person Name', 'radhikas-dealers'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'dealer_name_color',
            [
                'label' => esc_html__('Color', 'radhikas-dealers'),
                'type' => Controls_Manager::COLOR,
                'default' => '#475569',
                'selectors' => [
                    '{{WRAPPER}} .rdw-dealer-name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'dealer_name_typography',
                'selector' => '{{WRAPPER}} .rdw-dealer-name',
            ]
        );

        $this->add_control(
            'heading_address',
            [
                'label' => esc_html__('Address & District', 'radhikas-dealers'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'address_color',
            [
                'label' => esc_html__('Color', 'radhikas-dealers'),
                'type' => Controls_Manager::COLOR,
                'default' => '#64748b',
                'selectors' => [
                    '{{WRAPPER}} .rdw-address' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'address_typography',
                'selector' => '{{WRAPPER}} .rdw-address',
            ]
        );

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════════
        // STYLE TAB: Action Buttons & Badges
        // ══════════════════════════════════════════════════════════════════════
        $this->start_controls_section(
            'section_style_buttons',
            [
                'label' => esc_html__('Buttons & Badges', 'radhikas-dealers'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'call_btn_bg',
            [
                'label' => esc_html__('Call Button Background', 'radhikas-dealers'),
                'type' => Controls_Manager::COLOR,
                'default' => '#0284c7',
                'selectors' => [
                    '{{WRAPPER}} .rdw-btn-call' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'call_btn_color',
            [
                'label' => esc_html__('Call Button Text Color', 'radhikas-dealers'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rdw-btn-call' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'special_badge_bg',
            [
                'label' => esc_html__('Special Dealer Badge Background', 'radhikas-dealers'),
                'type' => Controls_Manager::COLOR,
                'default' => '#d97706',
                'selectors' => [
                    '{{WRAPPER}} .rdw-badge-special' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'regular_badge_bg',
            [
                'label' => esc_html__('Authorized Dealer Badge Background', 'radhikas-dealers'),
                'type' => Controls_Manager::COLOR,
                'default' => '#059669',
                'selectors' => [
                    '{{WRAPPER}} .rdw-badge-dealer' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $widget_id = 'rdw_' . $this->get_id();

        // Enqueue styles and scripts
        wp_enqueue_style('radhikas-dealers-css');
        wp_enqueue_script('radhikas-dealers-js');

        include RADHIKAS_DEALERS_PATH . 'templates/dealers-container.php';
    }
}
