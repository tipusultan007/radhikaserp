import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/scss/app.scss',
                'resources/scss/icons.scss',

                // Dashboard css
                'node_modules/daterangepicker/daterangepicker.css',
                'node_modules/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css',

                // Apps Css
                'node_modules/fullcalendar/main.min.css',
                'node_modules/quill/dist/quill.core.css',
                'node_modules/quill/dist/quill.snow.css',
                'node_modules/quill/dist/quill.bubble.css',
                'node_modules/jquery-toast-plugin/dist/jquery.toast.min.css',
                'resources/js/pages/demo.widgets.js',
                'resources/js/pages/component.chat.js',
                'resources/js/pages/demo.inbox.js',
                'node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
                'node_modules/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css',
                'node_modules/datatables.net-fixedcolumns-bs5/css/fixedColumns.bootstrap5.min.css',
                'node_modules/datatables.net-fixedheader-bs5/css/fixedHeader.bootstrap5.min.css',
                'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css',
                'node_modules/datatables.net-select-bs5/css/select.bootstrap5.min.css',
                
                // Extended Css
                'node_modules/select2/dist/css/select2.min.css',
                'node_modules/daterangepicker/daterangepicker.css',
                'node_modules/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.css',
                'node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css',
                'node_modules/bootstrap-timepicker/css/bootstrap-timepicker.min.css',
                'node_modules/flatpickr/dist/flatpickr.min.css',

                // Maps css
                'node_modules/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css',                

                // Theme Js
                'resources/js/app.js',
                'resources/js/head.js',
                'resources/js/layout.js',

                // Dashboard Js
                'resources/js/pages/demo.dashboard.js',

                // Apps Js  
                'resources/js/pages/demo.calendar.js',
                'resources/js/pages/component.dragula.js',
                'resources/js/pages/demo.tasks.js',
                'resources/js/pages/demo.toastr.js',

                // Extended Ui Js
                'resources/js/pages/component.range-slider.js',
                'resources/js/pages/component.dragula.js',
                'resources/js/pages/component.rating.js',
                'resources/js/pages/demo.typehead.js',
                'resources/js/pages/demo.flatpickr.js',
                'resources/js/pages/component.fileupload.js',
                'resources/js/pages/demo.form-wizard.js',

                // Icons JS
                'resources/js/pages/demo.bootstrapicons.js',
                'resources/js/pages/demo.material-symbol.js',
                'resources/js/pages/demo.remixicons.js',

                // Maps Js
                'resources/js/pages/demo.apex-area.js',
                'resources/js/pages/demo.apex-bar.js',
                'resources/js/pages/demo.apex-boxplot.js',
                'resources/js/pages/demo.apex-bubble.js',
                'resources/js/pages/demo.apex-candlestick.js',
                'resources/js/pages/demo.apex-column.js',
                'resources/js/pages/demo.apex-heatmap.js',
                'resources/js/pages/demo.apex-line.js',
                'resources/js/pages/demo.apex-mixed.js',
                'resources/js/pages/demo.apex-pie.js',
                'resources/js/pages/demo.apex-polar-area.js',
                'resources/js/pages/demo.apex-radar.js',
                'resources/js/pages/demo.apex-radialbar.js',
                'resources/js/pages/demo.apex-scatter.js',
                'resources/js/pages/demo.apex-sparklines.js',
                'resources/js/pages/demo.apex-timeline.js',
                'resources/js/pages/demo.apex-treemap.js',
                'resources/js/pages/demo.chartjs-area.js',
                'resources/js/pages/demo.chartjs-bar.js',
                'resources/js/pages/demo.chartjs-line.js',
                'resources/js/pages/demo.chartjs-other.js',

                // Froms Js
                'resources/js/pages/demo.form-advanced.js',
                'resources/js/pages/demo.typehead.js',
                'resources/js/pages/demo.flatpickr.js',
                'resources/js/pages/demo.quilljs.js',
                'resources/js/pages/component.fileupload.js',
                'resources/js/pages/demo.form-wizard.js',

                // Tables Js
                'resources/js/pages/demo.datatable-init.js',

                // Maps Js
                'resources/js/pages/demo.google-maps.js',
                'resources/js/pages/demo.vector-maps.js',

            ],
            refresh: true,
        }),
    ],
    build: {
        sourcemap: false,
    },
    resolve: {
        alias: {
            $: "jQuery",
        },
    },
});
