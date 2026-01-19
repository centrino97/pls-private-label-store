<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get date range (default to last 30 days)
$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : date( 'Y-m-d', strtotime( '-30 days' ) );
$date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : date( 'Y-m-d' );

wp_localize_script(
    'pls-admin',
    'PLS_BIDashboard',
    array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'pls_admin_nonce' ),
        'date_from' => $date_from,
        'date_to'   => $date_to,
    )
);

// Enqueue Chart.js
wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true );
?>
<div class="wrap pls-wrap pls-page-bi">
    <div class="pls-page-head">
        <div>
            <p class="pls-label"><?php esc_html_e( 'Analytics', 'pls-private-label-store' ); ?></p>
            <h1><?php esc_html_e( 'BI Dashboard', 'pls-private-label-store' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Track revenue, commission, marketing costs, and profit.', 'pls-private-label-store' ); ?></p>
        </div>
        <div>
            <form method="get" action="" class="pls-date-range-form" style="display: inline-flex; gap: 8px; align-items: center;">
                <input type="hidden" name="page" value="pls-bi" />
                <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" class="pls-input" />
                <span>to</span>
                <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" class="pls-input" />
                <button type="submit" class="button"><?php esc_html_e( 'Update', 'pls-private-label-store' ); ?></button>
            </form>
        </div>
    </div>

    <div class="pls-bi-dashboard">
        <!-- Metrics Cards -->
        <div class="pls-bi-metrics-grid" id="pls-bi-metrics">
            <div class="pls-bi-card">
                <div class="pls-bi-card__header">
                    <h3><?php esc_html_e( 'Total Revenue', 'pls-private-label-store' ); ?></h3>
                </div>
                <div class="pls-bi-card__value" id="pls-bi-revenue">$0.00</div>
                <div class="pls-bi-card__change" id="pls-bi-revenue-change">--</div>
            </div>
            <div class="pls-bi-card">
                <div class="pls-bi-card__header">
                    <h3><?php esc_html_e( 'Total Commission', 'pls-private-label-store' ); ?></h3>
                </div>
                <div class="pls-bi-card__value" id="pls-bi-commission">$0.00</div>
                <div class="pls-bi-card__change" id="pls-bi-commission-change">--</div>
            </div>
            <div class="pls-bi-card">
                <div class="pls-bi-card__header">
                    <h3><?php esc_html_e( 'Marketing Costs', 'pls-private-label-store' ); ?></h3>
                </div>
                <div class="pls-bi-card__value" id="pls-bi-marketing">$0.00</div>
                <div class="pls-bi-card__change" id="pls-bi-marketing-change">--</div>
            </div>
            <div class="pls-bi-card pls-bi-card--highlight">
                <div class="pls-bi-card__header">
                    <h3><?php esc_html_e( 'Net Profit', 'pls-private-label-store' ); ?></h3>
                </div>
                <div class="pls-bi-card__value" id="pls-bi-profit">$0.00</div>
                <div class="pls-bi-card__change" id="pls-bi-profit-change">--</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="pls-bi-charts-grid">
            <div class="pls-bi-card">
                <div class="pls-bi-card__header">
                    <h3><?php esc_html_e( 'Revenue Trend', 'pls-private-label-store' ); ?></h3>
                </div>
                <div class="pls-bi-card__body">
                    <canvas id="pls-bi-revenue-chart"></canvas>
                </div>
            </div>
            <div class="pls-bi-card">
                <div class="pls-bi-card__header">
                    <h3><?php esc_html_e( 'Marketing Costs by Channel', 'pls-private-label-store' ); ?></h3>
                </div>
                <div class="pls-bi-card__body">
                    <canvas id="pls-bi-marketing-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Marketing Cost Entry -->
        <div class="pls-bi-card">
            <div class="pls-bi-card__header">
                <h3><?php esc_html_e( 'Add Marketing Cost', 'pls-private-label-store' ); ?></h3>
            </div>
            <div class="pls-bi-card__body">
                <form id="pls-bi-marketing-form" class="pls-form">
                    <div class="pls-form-row">
                        <div class="pls-form-field">
                            <label><?php esc_html_e( 'Date', 'pls-private-label-store' ); ?></label>
                            <input type="date" name="cost_date" value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" class="pls-input" required />
                        </div>
                        <div class="pls-form-field">
                            <label><?php esc_html_e( 'Channel', 'pls-private-label-store' ); ?></label>
                            <select name="channel" class="pls-input" required>
                                <option value="meta"><?php esc_html_e( 'Meta (Facebook/Instagram)', 'pls-private-label-store' ); ?></option>
                                <option value="google"><?php esc_html_e( 'Google Ads', 'pls-private-label-store' ); ?></option>
                                <option value="creative"><?php esc_html_e( 'Creative Agency', 'pls-private-label-store' ); ?></option>
                                <option value="other"><?php esc_html_e( 'Other', 'pls-private-label-store' ); ?></option>
                            </select>
                        </div>
                        <div class="pls-form-field">
                            <label><?php esc_html_e( 'Amount (AUD)', 'pls-private-label-store' ); ?></label>
                            <input type="number" step="0.01" name="amount" class="pls-input" required />
                        </div>
                    </div>
                    <div class="pls-form-row">
                        <div class="pls-form-field" style="flex: 1;">
                            <label><?php esc_html_e( 'Description', 'pls-private-label-store' ); ?></label>
                            <textarea name="description" class="pls-input" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="pls-form-actions">
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Add Marketing Cost', 'pls-private-label-store' ); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Product Performance -->
        <div class="pls-bi-card">
            <div class="pls-bi-card__header">
                <h3><?php esc_html_e( 'Product Performance', 'pls-private-label-store' ); ?></h3>
            </div>
            <div class="pls-bi-card__body">
                <div id="pls-bi-product-performance">
                    <p><?php esc_html_e( 'Loading...', 'pls-private-label-store' ); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    if (typeof PLS_BIDashboard === 'undefined') {
        return;
    }

    // Load dashboard data
    function loadDashboardData() {
        $.ajax({
            url: PLS_BIDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'pls_get_bi_metrics',
                nonce: PLS_BIDashboard.nonce,
                date_from: PLS_BIDashboard.date_from,
                date_to: PLS_BIDashboard.date_to
            },
            success: function(response) {
                if (response.success && response.data) {
                    updateMetrics(response.data);
                }
            }
        });

        // Load chart data
        $.ajax({
            url: PLS_BIDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'pls_get_bi_chart_data',
                nonce: PLS_BIDashboard.nonce,
                date_from: PLS_BIDashboard.date_from,
                date_to: PLS_BIDashboard.date_to
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderCharts(response.data);
                }
            }
        });

        // Load product performance
        $.ajax({
            url: PLS_BIDashboard.ajax_url,
            type: 'POST',
            data: {
                action: 'pls_get_product_performance',
                nonce: PLS_BIDashboard.nonce,
                date_from: PLS_BIDashboard.date_from,
                date_to: PLS_BIDashboard.date_to
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderProductPerformance(response.data);
                }
            }
        });
    }

    function updateMetrics(data) {
        $('#pls-bi-revenue').text('$' + parseFloat(data.revenue || 0).toFixed(2));
        $('#pls-bi-commission').text('$' + parseFloat(data.commission || 0).toFixed(2));
        $('#pls-bi-marketing').text('$' + parseFloat(data.marketing_cost || 0).toFixed(2));
        $('#pls-bi-profit').text('$' + parseFloat(data.profit || 0).toFixed(2));
    }

    function renderCharts(data) {
        // Revenue chart
        if (typeof Chart !== 'undefined' && data.revenue_trend) {
            const revenueCtx = document.getElementById('pls-bi-revenue-chart');
            if (revenueCtx) {
                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: data.revenue_trend.labels || [],
                        datasets: [{
                            label: 'Revenue',
                            data: data.revenue_trend.values || [],
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }]
                    }
                });
            }
        }

        // Marketing chart
        if (typeof Chart !== 'undefined' && data.marketing_by_channel) {
            const marketingCtx = document.getElementById('pls-bi-marketing-chart');
            if (marketingCtx) {
                new Chart(marketingCtx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(data.marketing_by_channel),
                        datasets: [{
                            label: 'Marketing Cost',
                            data: Object.values(data.marketing_by_channel),
                            backgroundColor: 'rgba(54, 162, 235, 0.5)'
                        }]
                    }
                });
            }
        }
    }

    function renderProductPerformance(data) {
        if (!data || !data.length) {
            $('#pls-bi-product-performance').html('<p>No product data available.</p>');
            return;
        }

        let html = '<table class="pls-table-modern"><thead><tr><th>Product</th><th>Revenue</th><th>Units</th></tr></thead><tbody>';
        data.forEach(function(item) {
            html += '<tr><td>' + item.name + '</td><td>$' + parseFloat(item.revenue || 0).toFixed(2) + '</td><td>' + (item.units || 0) + '</td></tr>';
        });
        html += '</tbody></table>';
        $('#pls-bi-product-performance').html(html);
    }

    // Handle marketing cost form submission
    $('#pls-bi-marketing-form').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.ajax({
            url: PLS_BIDashboard.ajax_url,
            type: 'POST',
            data: formData + '&action=pls_save_marketing_cost&nonce=' + PLS_BIDashboard.nonce,
            success: function(response) {
                if (response.success) {
                    alert('Marketing cost added successfully!');
                    $('#pls-bi-marketing-form')[0].reset();
                    loadDashboardData();
                } else {
                    alert('Error: ' + (response.data?.message || 'Unknown error'));
                }
            }
        });
    });

    // Initial load
    loadDashboardData();
});
</script>
