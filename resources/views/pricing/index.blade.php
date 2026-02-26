<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pricing Comparison - GrabMaps vs Google Maps</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/pricing.css">

    <style>
        body {
            background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 50%, #e8f0fe 100%);
            min-height: 100vh;
        }
    </style>
</head>

<body>
    <!-- NAVBAR -->
    <nav class="pricing-navbar">
        <div class="container">
            <a href="{{ route('pageHome') }}" class="navbar-brand">
                <span class="logo-dot">G</span>
                <span>GrabMaps</span>
            </a>
            <div style="display:flex;align-items:center;gap:12px;">
                <a href="{{ route('pricing.admin') }}" class="btn-back">
                    <i class="bi bi-gear"></i> Admin
                </a>
                <a href="{{ route('pageHome') }}" class="btn-back">
                    <i class="bi bi-map"></i> Back to Map
                </a>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero-section">
        <div class="container">
            <h1>API Pricing Comparison</h1>
            <p>ALS-GRAB (Amazon Location Service - GrabMaps) vs Google Maps Platform</p>
            <small>Prices per 1,000 requests (USD)</small>
        </div>
    </section>

    <!-- CALCULATOR -->
    <section class="content-section">
        <div class="calculator-card animate-in">
            <h5><i class="bi bi-calculator"></i> Cost Calculator</h5>
            <p class="calc-subtitle">Enter your estimated monthly API requests to compare costs</p>

            <div class="calc-input-wrapper">
                <div class="calc-input-box">
                    <span class="calc-input-icon"><i class="bi bi-hash"></i></span>
                    <input type="text" id="volumeInput" value="50,000" inputmode="numeric" autocomplete="off" placeholder="e.g. 50,000" onkeydown="if(event.key==='Enter')calculateCosts()" onfocus="this.select()">
                    <button type="button" class="calc-input-clear" id="clearVolume" title="Clear"><i class="bi bi-x-lg"></i></button>
                    <span class="calc-input-suffix">req/month</span>
                </div>
                <button class="btn-calculate" onclick="calculateCosts()">
                    <i class="bi bi-graph-up"></i> Calculate
                </button>
            </div>

            <div class="calc-input-error" id="volumeError">Please enter the number of requests</div>

            <div class="slider-wrapper">
                <input type="range" id="volumeSlider" class="volume-slider" min="0" max="500000" step="1000" value="50000">
                <div class="slider-labels">
                    <span>0</span>
                    <span>100K</span>
                    <span>200K</span>
                    <span>300K</span>
                    <span>400K</span>
                    <span>500K</span>
                </div>
            </div>

            <div id="calculatorSummary"></div>
        </div>
    </section>

    <!-- CHARTS -->
    <section class="content-section">
        <div class="charts-section animate-in delay-1">
            <div class="chart-card">
                <h6><i class="bi bi-bar-chart-fill" style="color:var(--grab-green)"></i> Cost by Category</h6>
                <canvas id="barChart"></canvas>
            </div>
            <div class="chart-card">
                <h6><i class="bi bi-graph-up-arrow" style="color:var(--grab-green)"></i> Cost Curve</h6>
                <div class="chart-controls" id="lineChartControls"></div>
                <canvas id="lineChart"></canvas>
            </div>
        </div>
    </section>

    <!-- UNIFIED PRICING TABLES -->
    <section class="content-section pricing-tables-section animate-in delay-2">
        @foreach($categories as $category)
        @php
        $allAlsOnly = $category->items->where('als_only', true)->count() === $category->items->count();
        @endphp
        <div class="pricing-card" data-category="{{ $category->slug }}">
            <div class="pricing-card-header">
                <h5>{{ $category->name }}</h5>
                <span class="category-badge {{ $allAlsOnly ? 'badge-als-only' : 'badge-both' }}">
                    {{ $allAlsOnly ? 'ALS-GRAB Only' : 'Both Platforms' }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="unified-table">
                    <colgroup>
                        <col style="width:40px;">
                        <col style="width:auto;">
                        <col style="width:80px;">
                        <col style="width:110px;">
                        <col style="width:110px;">
                        <col style="width:90px;">
                        <col style="width:110px;">
                        <col style="width:110px;">
                        <col style="width:100px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" class="check-all" data-category="{{ $category->slug }}" checked></th>
                            <th>API</th>
                            <th>Tier</th>
                            <th class="col-price">ALS/1K</th>
                            <th class="col-price">Google/1K</th>
                            <th class="col-free">Free Tier</th>
                            <th class="col-cost">ALS Cost</th>
                            <th class="col-cost">Google Cost</th>
                            <th class="col-savings">Savings</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($category->items as $item)
                        <tr data-item-id="{{ $item->id }}">
                            <td class="td-check"><input type="checkbox" class="item-check" data-item-id="{{ $item->id }}" checked></td>
                            <td class="td-api">{{ $item->api_name }}</td>
                            <td>
                                @if($item->tier)
                                @php
                                $tierClass = match(strtolower($item->tier)) {
                                'core' => 'tier-core',
                                'advanced' => 'tier-advanced',
                                'premium' => 'tier-premium',
                                'stored' => 'tier-stored',
                                default => 'tier-default',
                                };
                                @endphp
                                <span class="tier-badge {{ $tierClass }}">{{ $item->tier }}</span>
                                @else
                                <span class="text-muted-cell">-</span>
                                @endif
                            </td>
                            <td class="td-price td-als">
                                {{ $item->als_price !== null ? '$' . number_format((float)$item->als_price, 4) : '-' }}
                            </td>
                            <td class="td-price td-google">
                                {{ $item->google_price !== null ? '$' . number_format((float)$item->google_price, 4) : 'N/A' }}
                            </td>
                            <td class="td-free">
                                @if($item->google_free_threshold > 0)
                                {{ number_format($item->google_free_threshold) }}
                                @else
                                <span class="text-muted-cell">-</span>
                                @endif
                            </td>
                            <td class="td-cost td-cost-als">-</td>
                            <td class="td-cost td-cost-google">-</td>
                            <td class="td-savings">-</td>
                        </tr>
                        @endforeach
                        <tr class="subtotal-row" data-category-subtotal="{{ $category->slug }}">
                            <td colspan="6"><strong>Subtotal</strong></td>
                            <td class="td-cost td-cost-als subtotal-als">-</td>
                            <td class="td-cost td-cost-google subtotal-google">-</td>
                            <td class="td-savings subtotal-savings">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </section>

    <!-- KEY INSIGHTS -->
    <section class="content-section insights-section animate-in delay-3">
        <h4 style="text-align:center;font-weight:700;margin-bottom:24px;">Key Insights</h4>
        <div class="insights-grid">
            <div class="insight-card">
                <div class="insight-icon green">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                <span class="insight-value">~14K</span>
                <h6>Breakeven Point</h6>
                <p>At just 14,000 requests/month for Places, GrabMaps is already cheaper than Google Maps</p>
            </div>
            <div class="insight-card">
                <div class="insight-icon blue">
                    <i class="bi bi-percent"></i>
                </div>
                <span class="insight-value">96%</span>
                <h6>Map Tiles Savings</h6>
                <p>GrabMaps Map Tiles cost $0.04/1K vs Google's $1.00/1K - a dramatic difference at scale</p>
            </div>
            <div class="insight-card">
                <div class="insight-icon amber">
                    <i class="bi bi-star"></i>
                </div>
                <span class="insight-value">4+</span>
                <h6>Exclusive Features</h6>
                <p>Trackers, Geofences, Isolines, and Snap to Roads available only on ALS-GRAB</p>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="pricing-footer">
        <div class="container">
            <p>
                Sources:
                <a href="https://aws.amazon.com/location/pricing/" target="_blank">Amazon Location Service Pricing</a>
                &middot;
                <a href="https://mapsplatform.google.com/pricing/" target="_blank">Google Maps Platform Pricing</a>
            </p>
            <p style="margin-top:4px;">
                <a href="{{ route('pricing.admin') }}">Manage Pricing Data</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        // ---- DATA FROM DB ----
        const pricingData = @json($categories);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // ---- SYNC SLIDER & INPUT ----
        const volumeInput = document.getElementById('volumeInput');
        const volumeSlider = document.getElementById('volumeSlider');

        function getVolume() {
            return parseInt(volumeInput.value.replace(/,/g, '')) || 0;
        }

        function formatNumber(n) {
            return Math.max(0, Math.min(10000000, n)).toLocaleString();
        }

        // ---- CLEAR BUTTON ----
        document.getElementById('clearVolume').addEventListener('click', () => {
            volumeInput.value = '';
            volumeSlider.value = 0;
            volumeInput.focus();
        });

        volumeSlider.addEventListener('input', () => {
            volumeInput.value = formatNumber(parseInt(volumeSlider.value));
        });

        volumeInput.addEventListener('input', () => {
            // Clear error on typing
            document.querySelector('.calc-input-box').classList.remove('has-error');
            document.getElementById('volumeError').classList.remove('visible');

            const raw = volumeInput.value.replace(/[^0-9]/g, '');
            const num = parseInt(raw) || 0;
            const clamped = Math.max(0, Math.min(10000000, num));

            // Save cursor position relative to end
            const pos = volumeInput.selectionStart;
            const oldLen = volumeInput.value.length;

            volumeInput.value = formatNumber(clamped);

            // Restore cursor position adjusted for new formatting
            const newLen = volumeInput.value.length;
            volumeInput.setSelectionRange(pos + (newLen - oldLen), pos + (newLen - oldLen));

            if (clamped <= parseInt(volumeSlider.max)) {
                volumeSlider.value = clamped;
            }
        });

        // ---- CHARTS ----
        let barChart = null;
        let lineChart = null;
        let lastCalcData = null;

        // ---- CHECKBOX LOGIC ----
        function getCheckedItemIds() {
            const checked = new Set();
            document.querySelectorAll('.item-check:checked').forEach(cb => {
                checked.add(parseInt(cb.dataset.itemId));
            });
            return checked;
        }

        function reRender() {
            if (lastCalcData) {
                renderResults(lastCalcData);
                renderBarChart(lastCalcData);
            }
        }

        // Individual checkbox → re-render + sync "Select All"
        document.querySelectorAll('.item-check').forEach(cb => {
            cb.addEventListener('change', () => {
                const row = cb.closest('tr');
                row.classList.toggle('row-disabled', !cb.checked);

                // Sync "Select All" for this category
                const card = cb.closest('.pricing-card');
                const allBoxes = card.querySelectorAll('.item-check');
                const checkAll = card.querySelector('.check-all');
                const allChecked = [...allBoxes].every(b => b.checked);
                const someChecked = [...allBoxes].some(b => b.checked);
                checkAll.checked = allChecked;
                checkAll.indeterminate = !allChecked && someChecked;

                reRender();
            });
        });

        // "Select All" checkbox → toggle all in category
        document.querySelectorAll('.check-all').forEach(cb => {
            cb.addEventListener('change', () => {
                const card = cb.closest('.pricing-card');
                card.querySelectorAll('.item-check').forEach(box => {
                    box.checked = cb.checked;
                    box.closest('tr').classList.toggle('row-disabled', !cb.checked);
                });
                reRender();
            });
        });

        // ---- CALCULATE ----
        async function calculateCosts() {
            const volume = getVolume();
            const inputBox = document.querySelector('.calc-input-box');
            const errorEl = document.getElementById('volumeError');

            if (!volume || volume <= 0) {
                inputBox.classList.add('has-error');
                errorEl.classList.add('visible');
                volumeInput.focus();
                return;
            }
            inputBox.classList.remove('has-error');
            errorEl.classList.remove('visible');

            try {
                const response = await fetch('/api/pricing/calculate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        volume
                    }),
                });

                const data = await response.json();
                lastCalcData = data;
                renderResults(data);
                renderBarChart(data);
            } catch (err) {
                console.error('Calculate error:', err);
            }
        }

        // Format number as USD currency
        function formatUSD(value) {
            return value.toLocaleString('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function renderResults(data) {
            // --- Calculate totals (separate comparable vs exclusive) ---
            let comparableAls = 0, comparableGoogle = 0, exclusiveAls = 0;
            const checkedIds = getCheckedItemIds();

            data.results.forEach(cat => {
                cat.items.forEach(item => {
                    if (!checkedIds.has(item.id)) return;
                    if (!item.als_only && item.als_cost !== null && item.google_cost !== null) {
                        comparableAls += item.als_cost;
                        comparableGoogle += item.google_cost;
                    } else if (item.als_cost !== null) {
                        exclusiveAls += item.als_cost;
                    }
                });
            });

            const savings = comparableGoogle > 0 ? ((1 - (comparableAls / comparableGoogle)) * 100).toFixed(1) : 0;
            const savedAmount = comparableGoogle - comparableAls;

            // --- Summary cards ---
            const summaryContainer = document.getElementById('calculatorSummary');
            summaryContainer.innerHTML = `<div class="result-summary">
                <div class="summary-card summary-als">
                    <div class="summary-label">ALS-GRAB</div>
                    <div class="summary-value">${formatUSD(comparableAls)}</div>
                    <div class="summary-sub">comparable APIs</div>
                </div>
                <div class="summary-card summary-google">
                    <div class="summary-label">Google Maps</div>
                    <div class="summary-value">${formatUSD(comparableGoogle)}</div>
                    <div class="summary-sub">comparable APIs</div>
                </div>
                <div class="summary-card summary-savings">
                    <div class="summary-label">You Save</div>
                    <div class="summary-value">${savings}%</div>
                    <div class="summary-sub">${formatUSD(savedAmount)}/month</div>
                </div>
                <div class="summary-card summary-exclusive">
                    <div class="summary-label">ALS Exclusive</div>
                    <div class="summary-value">${formatUSD(exclusiveAls)}</div>
                    <div class="summary-sub">Trackers, Geofences, etc.</div>
                </div>
            </div>`;

            // --- Update table cells per item ---
            data.results.forEach(cat => {
                let catComparableAls = 0, catComparableGoogle = 0;
                let catAlsAll = 0, catGoogleAll = 0;

                cat.items.forEach(item => {
                    const row = document.querySelector(`tr[data-item-id="${item.id}"]`);
                    if (!row) return;

                    const isChecked = checkedIds.has(item.id);

                    if (isChecked) {
                        const alsCostStr = item.als_cost !== null ? formatUSD(item.als_cost) : '-';
                        const googleCostStr = item.google_cost !== null ? formatUSD(item.google_cost) : '-';

                        row.querySelector('.td-cost-als').textContent = alsCostStr;
                        row.querySelector('.td-cost-google').textContent = googleCostStr;

                        if (item.als_only) {
                            row.querySelector('.td-savings').innerHTML = '<span class="als-only-badge">ALS Only</span>';
                        } else if (item.savings_percent !== null && item.savings_percent > 0) {
                            row.querySelector('.td-savings').innerHTML = `<span class="savings-badge"><i class="bi bi-check-circle-fill"></i> ${item.savings_percent}%</span>`;
                        } else {
                            row.querySelector('.td-savings').textContent = '-';
                        }

                        // Subtotal counts
                        if (item.als_cost !== null) catAlsAll += item.als_cost;
                        if (item.google_cost !== null) catGoogleAll += item.google_cost;
                        if (!item.als_only && item.als_cost !== null && item.google_cost !== null) {
                            catComparableAls += item.als_cost;
                            catComparableGoogle += item.google_cost;
                        }
                    } else {
                        row.querySelector('.td-cost-als').textContent = '-';
                        row.querySelector('.td-cost-google').textContent = '-';
                        row.querySelector('.td-savings').textContent = '-';
                    }
                });

                // Update subtotal row
                const subtotalRow = document.querySelector(`tr[data-category-subtotal="${cat.slug}"]`);
                if (subtotalRow) {
                    subtotalRow.querySelector('.subtotal-als').innerHTML = `<strong>${formatUSD(catAlsAll)}</strong>`;
                    subtotalRow.querySelector('.subtotal-google').innerHTML = `<strong>${formatUSD(catGoogleAll)}</strong>`;
                    const catSavings = catComparableGoogle > 0 ? ((1 - (catComparableAls / catComparableGoogle)) * 100).toFixed(1) : '-';
                    subtotalRow.querySelector('.subtotal-savings').innerHTML = catComparableGoogle > 0 ? `<strong>${catSavings}%</strong>` : '-';
                }
            });
        }

        // ---- BAR CHART ----
        function renderBarChart(data) {
            const ctx = document.getElementById('barChart').getContext('2d');

            const labels = [];
            const alsData = [];
            const googleData = [];

            const checkedIds = getCheckedItemIds();
            data.results.forEach(cat => {
                labels.push(cat.name);
                let alsTotal = 0,
                    googleTotal = 0;
                cat.items.forEach(item => {
                    if (!checkedIds.has(item.id)) return;
                    alsTotal += item.als_cost || 0;
                    googleTotal += item.google_cost || 0;
                });
                alsData.push(parseFloat(alsTotal.toFixed(2)));
                googleData.push(parseFloat(googleTotal.toFixed(2)));
            });

            if (barChart) barChart.destroy();

            barChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                            label: 'ALS-GRAB',
                            data: alsData,
                            backgroundColor: 'rgba(0, 177, 79, 0.8)',
                            borderColor: '#00B14F',
                            borderWidth: 1,
                            borderRadius: 6,
                        },
                        {
                            label: 'Google Maps',
                            data: googleData,
                            backgroundColor: 'rgba(66, 133, 244, 0.8)',
                            borderColor: '#4285F4',
                            borderWidth: 1,
                            borderRadius: 6,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: $${ctx.raw.toFixed(2)}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: v => '$' + v
                            }
                        }
                    }
                }
            });
        }

        // ---- LINE CHART ----
        function buildLineChartControls() {
            const container = document.getElementById('lineChartControls');
            const comparableItems = [];

            pricingData.forEach(cat => {
                cat.items.forEach(item => {
                    if (item.als_price !== null && item.google_price !== null) {
                        const label = item.api_name + (item.tier ? ` (${item.tier})` : '');
                        comparableItems.push({
                            label,
                            alsRate: parseFloat(item.als_price),
                            googleRate: parseFloat(item.google_price),
                            freeThreshold: item.google_free_threshold || 0,
                        });
                    }
                });
            });

            if (comparableItems.length === 0) return;

            comparableItems.forEach((item, idx) => {
                const btn = document.createElement('button');
                btn.className = 'btn-chip' + (idx === 0 ? ' active' : '');
                btn.textContent = item.label.length > 25 ? item.label.substring(0, 22) + '...' : item.label;
                btn.title = item.label;
                btn.onclick = () => {
                    container.querySelectorAll('.btn-chip').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    renderLineChart(item);
                };
                container.appendChild(btn);
            });

            // Render first item by default
            renderLineChart(comparableItems[0]);
        }

        function renderLineChart(item) {
            const ctx = document.getElementById('lineChart').getContext('2d');
            const maxVolume = getVolume() || 50000;
            const steps = 25;
            const stepSize = maxVolume / steps;

            const volumes = [];
            const alsLine = [];
            const googleLine = [];

            for (let i = 0; i <= steps; i++) {
                const v = Math.round(i * stepSize);
                volumes.push(v);
                alsLine.push(parseFloat(((v / 1000) * item.alsRate).toFixed(2)));
                const billable = Math.max(0, v - item.freeThreshold);
                googleLine.push(parseFloat(((billable / 1000) * item.googleRate).toFixed(2)));
            }

            if (lineChart) lineChart.destroy();

            lineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: volumes.map(v => v >= 1000 ? (v / 1000) + 'K' : v),
                    datasets: [{
                            label: 'ALS-GRAB',
                            data: alsLine,
                            borderColor: '#00B14F',
                            backgroundColor: 'rgba(0,177,79,0.08)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'Google Maps',
                            data: googleLine,
                            borderColor: '#4285F4',
                            backgroundColor: 'rgba(66,133,244,0.08)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: $${ctx.raw.toFixed(2)}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Monthly Requests'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cost (USD)'
                            },
                            ticks: {
                                callback: v => '$' + v
                            }
                        }
                    }
                }
            });
        }

        // ---- INIT ----
        document.addEventListener('DOMContentLoaded', () => {
            buildLineChartControls();
            calculateCosts();
        });
    </script>
</body>

</html>