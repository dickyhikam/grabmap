@php
    // Harga satuan: $1.00 dan $0.50 lebih mudah dibandingkan daripada $1.0000.
    // Angka di bawah 10 sen tetap butuh desimal tambahan supaya tidak jadi $0.04
    // untuk dua harga yang berbeda ($0.035 vs $0.04).
    $money = function ($value) {
        $value = (float) $value;
        $text = $value < 0.1
            ? rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.')
            : number_format($value, 2, '.', ',');
        return '$' . $text;
    };

@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Tema dipasang sebelum CSS supaya tidak ada kedipan putih saat mode gelap. --}}
    <script>
        (function () {
            try {
                document.documentElement.setAttribute('data-theme', localStorage.getItem('gm-theme') || 'system');
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'system');
            }
        })();
    </script>
    <link rel="shortcut icon" href="{{ asset('logo2.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('logo2.png') }}" type="image/png" sizes="32x32">
    <title>{{ __('pricing.title') }} - GrabMaps vs Google Maps</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/pricing.css">
    <link rel="stylesheet" href="/css/pricing-v2.css?v={{ filemtime(public_path('css/pricing-v2.css')) }}">

</head>

<body>
    <div class="price-progress"><div class="price-progress-fill" id="priceProgressFill"></div></div>

    {{-- Kepala halaman: satu kartu mengambang berisi tombol kembali, judul, dan
         aksi — menggantikan navbar berlogo plus hero hijau yang mengulang judul
         yang sama.

         Yang melekat di atas adalah cangkangnya, bukan kartunya. Tanpa cangkang
         selebar layar, isi halaman yang lewat di sela kiri-kanan kartu terlihat
         sebagai serpihan putih di sekelilingnya saat digulir. --}}
    <div class="head-shell" id="headShell">
    <header class="page-head" id="pageHead">
        <a href="{{ route('pageHome') }}" class="head-back" aria-label="{{ __('pricing.back_to_map') }}">
            <i class="bi bi-arrow-left"></i>
        </a>
        <a href="{{ route('pageHome') }}" class="head-logo" aria-label="GrabMaps">
            <img src="{{ asset('logo.png') }}" alt="GrabMaps">
        </a>
        <div class="head-text">
            <h1>{{ __('pricing.title') }}</h1>
            <p>{{ __('pricing.subtitle') }}</p>
        </div>
        {{-- Bahasa dan tema: pengaturan yang jarang disentuh, jadi ditaruh di
             kepala halaman dan mengatup saat digulir supaya ruangnya bisa
             dipakai ringkasan angka. --}}
        <div class="head-tools">
            {{-- Bahasa: roda pilih. Hanya kode di tengah yang menonjol, tetangganya
                 meredup di tepi — delapan locale muat di ruang selebar tiga. Bisa
                 digulir (roda tetikus atau geser) dan diklik. --}}
            <div class="lang-wheel" id="langWheel" role="listbox" aria-label="{{ __('pricing.language') }}">
                <div class="lang-track" id="langTrack">
                    @foreach(config('pricing_locales', []) as $code => $info)
                    <a href="{{ route('pricing') }}?lang={{ $code }}"
                       class="lang-item {{ app()->getLocale() === $code ? 'active' : '' }}"
                       data-lang="{{ $code }}"
                       role="option"
                       aria-selected="{{ app()->getLocale() === $code ? 'true' : 'false' }}"
                       title="{{ $info['label'] }} ({{ $info['country'] }})">{{ strtoupper($code) }}</a>
                    @endforeach
                </div>
            </div>

            {{-- Tema: terang / gelap / ikut sistem --}}
            <div class="theme-switch" id="themeToggle">
            <button type="button" data-theme-set="light" title="{{ __('pricing.theme_light') }}" aria-label="{{ __('pricing.theme_light') }}"><i class="bi bi-sun"></i></button>
            <button type="button" data-theme-set="dark" title="{{ __('pricing.theme_dark') }}" aria-label="{{ __('pricing.theme_dark') }}"><i class="bi bi-moon"></i></button>
            <button type="button" data-theme-set="system" title="{{ __('pricing.theme_system') }}" aria-label="{{ __('pricing.theme_system') }}"><i class="bi bi-circle-half"></i></button>
            </div>
        </div>

        {{-- Ringkasan ringkas yang muncul saat kepala mengatup: volume yang sudah
             diketik beserta hasilnya, supaya angka kalkulator tetap terbaca
             walau kartunya sudah tergulir jauh ke atas. --}}
        <div class="head-stats" id="headStats" aria-live="polite">
            <button type="button" class="head-stat head-volume" id="headVolume" title="{{ __('pricing.cost_calculator') }}">
                <i class="bi bi-hash"></i><span>-</span>
            </button>
            <span class="head-stat"><i class="stat-dot als"></i>ALS <b id="headAls">-</b></span>
            <span class="head-stat"><i class="stat-dot google"></i>Google <b id="headGoogle">-</b></span>
            <span class="head-stat head-save" id="headSave">-</span>
        </div>

        <a href="{{ route('pricing.admin') }}" class="head-btn" hidden>
            <i class="bi bi-gear"></i> {{ __('pricing.admin') }}
        </a>
        <a href="{{ route('pageHome') }}" class="head-btn">
            <i class="bi bi-map"></i> {{ __('pricing.back_to_map') }}
        </a>
    </header>
    </div>

    <!-- CALCULATOR -->
    <section class="content-section">
        <div class="calculator-card animate-in">
            <div class="calc-head">
                <div class="calc-head-text">
                    <h5><i class="bi bi-calculator"></i> {{ __('pricing.cost_calculator') }}</h5>
                    <p class="calc-subtitle">{{ __('pricing.calc_subtitle') }}</p>
                </div>

                <div class="calc-head-tools">
                    @if($currency)
                    {{-- Mata uang: USD atau mata uang lokal locale ini. Kursnya
                         boleh diubah di tempat; nilainya tersimpan di browser. --}}
                    <div class="cur-switch" role="group" aria-label="{{ $currency['code'] }}">
                        <button type="button" class="cur-opt active" data-cur="USD">USD</button>
                        <button type="button" class="cur-opt" data-cur="LOCAL">{{ $currency['code'] }}</button>
                    </div>
                    <label class="rate-box" id="rateBox" hidden>
                        <span>1 USD =</span>
                        <input type="text" id="rateInput" inputmode="decimal" autocomplete="off"
                               value="{{ rtrim(rtrim(number_format((float) $currency['rate'], 2, '.', ''), '0'), '.') }}">
                        <span>{{ $currency['code'] }}</span>
                    </label>
                    @endif

                </div>
            </div>

            <div class="calc-input-wrapper">
                <div class="calc-input-box">
                    <span class="calc-input-icon"><i class="bi bi-hash"></i></span>
                    <input type="text" id="volumeInput" value="50,000" inputmode="numeric" autocomplete="off" placeholder="e.g. 50,000" onkeydown="if(event.key==='Enter')calculateCosts()" onfocus="this.select()">
                    <button type="button" class="calc-input-clear" id="clearVolume" title="{{ __('pricing.clear') }}"><i class="bi bi-x-lg"></i></button>
                    <span class="calc-input-suffix">{{ __('pricing.req_month') }}</span>
                </div>
                <button class="btn-calculate" onclick="calculateCosts()">
                    <i class="bi bi-graph-up"></i> {{ __('pricing.calculate') }}
                </button>
            </div>

            <div class="calc-input-error" id="volumeError">{{ __('pricing.volume_error') }}</div>

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

    <!-- PROVIDER TABS — Compare / Amazon Location / Google Maps -->
    <section class="content-section" style="padding-bottom:0;">
        <div class="provider-tabs-wrap">
            <div class="provider-tabs" role="tablist" aria-label="Provider view">
                <button type="button" class="provider-tab active" data-view="compare" role="tab" aria-selected="true">
                    <i class="bi bi-arrow-left-right"></i>
                    <span class="provider-label">{{ __('pricing.tab_compare') }}</span>
                </button>
                <button type="button" class="provider-tab" data-view="als" role="tab" aria-selected="false">
                    <i class="bi bi-cloud-fill"></i>
                    <span class="provider-label">Amazon Location</span>
                </button>
                <button type="button" class="provider-tab" data-view="google" role="tab" aria-selected="false">
                    <i class="bi bi-google"></i>
                    <span class="provider-label">Google Maps</span>
                </button>
            </div>
        </div>
    </section>

    {{-- Dua kolom: daftar harga di kiri, grafik menempel di kanan.
         Grafik ditulis lebih dulu supaya saat kolomnya menumpuk di layar
         sempit, grafik tetap muncul di atas daftar. --}}
    <section class="content-section pricing-split">
        <div class="split-side">
                <div class="charts-section animate-in delay-1">
                    <div class="chart-card">
                        <h6><i class="bi bi-bar-chart-fill" style="color:var(--grab-green)"></i> {{ __('pricing.cost_by_category') }}</h6>
                        <div class="chart-wrapper">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h6><i class="bi bi-graph-up-arrow" style="color:var(--grab-green)"></i> {{ __('pricing.cost_curve') }}</h6>
                        <div class="chart-controls" id="lineChartControls"></div>
                        <div class="chart-wrapper">
                            <canvas id="lineChart"></canvas>
                        </div>
                    </div>
                </div>
        </div>

        <div class="split-main pricing-tables-section animate-in delay-2">
                {{-- Hanya berguna kalau ada kategori yang cuma ada di ALS. Sejak katalog
                     dipangkas ke aksi yang dipakai GrabMaps, biasanya tidak ada. --}}
                @if($categories->contains(fn ($c) => $c->items->contains('als_only', true)))
                <!-- Shown only in Google-only view: notice that some categories are AWS-exclusive -->
                <div class="als-only-notice">
                    <i class="bi bi-info-circle-fill"></i>
                    {{ __('pricing.als_only_notice') }}
                </div>
                @endif

                @foreach($categories as $category)
                @php
                $allAlsOnly = $category->items->where('als_only', true)->count() === $category->items->count();
                @endphp
                <div class="pricing-card {{ $allAlsOnly ? 'als-only-card' : '' }}" data-category="{{ $category->slug }}">
                    <div class="pricing-card-header">
                        <div class="pricing-card-title-wrap">
                            <h5>{{ $category->name_translated ?? $category->name }}</h5>
                        </div>
                        <span class="category-badge {{ $allAlsOnly ? 'badge-als-only' : 'badge-both' }}">
                            {{ $allAlsOnly ? __('pricing.als_only') : __('pricing.both_platforms') }}
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="unified-table">
                            <thead>
                                <tr>
                                    @php
                                    // Satu baris = satu API. Tier (Core/Advanced/Stored) adalah pilihan
                                    // di dalam baris itu, bukan baris tersendiri, supaya tidak terbaca
                                    // sebagai tiga API berbeda dan tidak bisa dihitung dobel.
                                    $groups = $category->items->groupBy('api_name');
                                    $totalCount = $groups->count();
                                    $recCount = $groups->filter(fn ($g) => $g->contains('is_recommended', true))->count();
                                    $checkAllChecked = $recCount === $totalCount && $totalCount > 0;
                                    $checkAllIndeterminate = $recCount > 0 && $recCount < $totalCount;
                                    @endphp
                                    {{-- Kolom dikelompokkan per penyedia: harga satuan dan biaya bulanan
                                         milik penyedia yang sama berdampingan, dengan nama penyedianya
                                         dicetak kecil di atas judulnya. Nama penyedia sengaja tidak memakai
                                         colspan karena kolom bisa disembunyikan (tampilan per penyedia atau
                                         layar sempit) dan rentang colspan-nya akan melenceng. --}}
                                    <th class="col-check"><input type="checkbox" class="check-all" data-category="{{ $category->slug }}" {{ $checkAllChecked ? 'checked' : '' }}></th>
                                    <th>{{ __('pricing.api') }}</th>
                                    <th>{{ __('pricing.tier') }}</th>
                                    <th class="col-price col-als-price band-als band-start">
                                        <span class="th-owner owner-als">ALS-GRAB</span>{{ __('pricing.price_1k') }}
                                    </th>
                                    <th class="col-cost col-als-cost band-als">
                                        <span class="th-owner owner-als">ALS-GRAB</span>{{ __('pricing.cost_col') }}
                                    </th>
                                    <th class="col-price col-google-price band-google band-start">
                                        <span class="th-owner owner-google">Google Maps</span>{{ __('pricing.price_1k') }}
                                    </th>
                                    <th class="col-cost col-google-cost band-google">
                                        <span class="th-owner owner-google">Google Maps</span>{{ __('pricing.cost_col') }}
                                    </th>
                                    <th class="col-savings band-start">{{ __('pricing.savings') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groups as $apiName => $tierItems)
                                @php
                                $selected = $tierItems->firstWhere('is_recommended', true) ?? $tierItems->first();
                                $hasTierChoice = $tierItems->count() > 1;
                                $tierClassOf = fn ($tier) => match (strtolower((string) $tier)) {
                                'core' => 'tier-core',
                                'advanced' => 'tier-advanced',
                                'premium' => 'tier-premium',
                                'stored' => 'tier-stored',
                                default => 'tier-default',
                                };
                                @endphp
                                <tr data-item-id="{{ $selected->id }}" data-is-recommended="1">
                                    <td class="td-check"><input type="checkbox" class="item-check" data-item-id="{{ $selected->id }}" checked></td>
                                    <td class="td-api">
                                        <span class="api-name-cell">{{ $selected->api_name_translated ?? $selected->api_name }}</span>
                                        {{-- Keterangan tier yang sedang dipilih: kapan tier itu kena,
                                             bukan lagi disembunyikan di balik tombol tanda tanya. --}}
                                        @if($selected->description_translated ?? $selected->description)
                                        <span class="api-note note-{{ strtolower($selected->tier ?? 'plain') }}">{{ $selected->description_translated ?? $selected->description }}</span>
                                        @endif
                                    </td>
                                    <td class="td-tier">
                                        @if($hasTierChoice)
                                        <div class="tier-picker" role="radiogroup" aria-label="{{ __('pricing.tier') }}">
                                            @foreach($tierItems as $tierItem)
                                            <button type="button"
                                                    class="tier-opt {{ $tierClassOf($tierItem->tier) }} {{ $tierItem->id === $selected->id ? 'active' : '' }}"
                                                    role="radio"
                                                    aria-checked="{{ $tierItem->id === $selected->id ? 'true' : 'false' }}"
                                                    data-item-id="{{ $tierItem->id }}"
                                                    data-als="{{ $tierItem->als_price !== null ? (float) $tierItem->als_price : '' }}"
                                                    data-google="{{ $tierItem->google_price !== null ? (float) $tierItem->google_price : '' }}"
                                                    data-free="{{ (int) $tierItem->google_free_threshold }}"
                                                    data-tier="{{ $tierItem->tier }}"
                                                    data-info="{{ e($tierItem->description_translated ?? $tierItem->description) }}">{{ $tierItem->tier }}</button>
                                            @endforeach
                                        </div>
                                        @elseif($selected->tier)
                                        <span class="tier-badge {{ $tierClassOf($selected->tier) }}">{{ $selected->tier }}</span>
                                        @else
                                        <span class="text-muted-cell">-</span>
                                        @endif
                                    </td>
                                    <td class="td-price td-als band-als band-start">
                                        {{ $selected->als_price !== null ? $money($selected->als_price) : '-' }}
                                    </td>
                                    <td class="td-cost td-cost-als band-als">-</td>
                                    <td class="td-price td-google band-google band-start">
                                        {{ $selected->google_price !== null ? $money($selected->google_price) : 'N/A' }}
                                    </td>
                                    <td class="td-cost td-cost-google band-google">-</td>
                                    <td class="td-savings band-start">-</td>
                                </tr>
                                @endforeach
                                {{-- Satu sel per kolom, bukan colspan: kolom yang disembunyikan di
                                     layar sempit ikut hilang di baris ini juga, jadi angkanya tetap
                                     sejajar dengan judul kolomnya. --}}
                                <tr class="subtotal-row" data-category-subtotal="{{ $category->slug }}">
                                    <td class="td-check"></td>
                                    <td class="td-api"><strong>{{ __('pricing.subtotal') }}</strong></td>
                                    <td class="td-tier"></td>
                                    <td class="td-price td-als band-als band-start"></td>
                                    <td class="td-cost td-cost-als subtotal-als band-als">-</td>
                                    <td class="td-price td-google band-google band-start"></td>
                                    <td class="td-cost td-cost-google subtotal-google band-google">-</td>
                                    <td class="td-savings subtotal-savings band-start">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
        </div>
    </section>

    <!-- KEY INSIGHTS -->
    <section class="content-section insights-section animate-in delay-3">
        <h4 style="text-align:center;font-weight:700;margin-bottom:24px;">{{ __('pricing.key_insights') }}</h4>
        <div class="insights-grid" id="insightsGrid">
            <div class="insight-card">
                <div class="insight-icon green">
                    <i class="bi bi-arrow-down-circle"></i>
                </div>
                {{-- Titik impas hanya masuk akal selama kuota gratis Google ikut
                     dihitung. Sejak kuota itu diabaikan, yang berguna adalah
                     rata-rata selisih harganya. --}}
                <span class="insight-value" id="insightAvgSavings">-</span>
                <h6>{{ __('pricing.avg_savings') }}</h6>
                <p id="insightAvgSavingsDesc">{{ __('pricing.avg_savings_desc', ['count' => '-']) }}</p>
            </div>
            <div class="insight-card">
                <div class="insight-icon blue">
                    <i class="bi bi-percent"></i>
                </div>
                <span class="insight-value" id="insightMapTiles">96%</span>
                <h6>{{ __('pricing.map_tiles_savings') }}</h6>
                <p id="insightMapTilesDesc">{{ __('pricing.map_tiles_desc', ['als' => '0.04', 'google' => '1.00']) }}</p>
            </div>
            <div class="insight-card" id="insightExclusiveCard">
                <div class="insight-icon amber">
                    <i class="bi bi-star"></i>
                </div>
                <span class="insight-value" id="insightExclusive">4+</span>
                <h6>{{ __('pricing.exclusive_features') }}</h6>
                <p>{{ __('pricing.exclusive_desc') }}</p>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="pricing-footer">
        <div class="container">
            <p>
                {{ __('pricing.sources') }}:
                <a href="https://aws.amazon.com/location/pricing/" target="_blank">Amazon Location Service Pricing</a>
                &middot;
                <a href="https://mapsplatform.google.com/pricing/" target="_blank">Google Maps Platform Pricing</a>
            </p>
            <p style="margin-top:4px;">
                <a href="{{ route('pricing.admin') }}">{{ __('pricing.manage_pricing') }}</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    @php
        $pricingT = [
            'summary_als' => __('pricing.summary_als'),
            'summary_google' => __('pricing.summary_google'),
            'summary_comparable' => __('pricing.summary_comparable'),
            'you_save' => __('pricing.you_save'),
            'per_month' => __('pricing.per_month'),
            'als_exclusive' => __('pricing.als_exclusive'),
            'trackers_geofences' => __('pricing.trackers_geofences'),
            'als_only_badge' => __('pricing.als_only_badge'),
            'monthly_requests' => __('pricing.monthly_requests'),
            'cost_usd' => __('pricing.cost_usd'),
            'avg_savings_desc' => __('pricing.avg_savings_desc', ['count' => ':count']),
            'map_tiles_desc' => __('pricing.map_tiles_desc', ['als' => ':als', 'google' => ':google']),
        ];
    @endphp
    <script>
        // ---- TRANSLATIONS ----
        const pricingT = @json($pricingT);

        // ---- MATA UANG ----
        // Semua angka disimpan dalam USD; konversi hanya di lapisan tampilan,
        // jadi mengganti mata uang tidak pernah mengubah data atau hitungan.
        const currencyConf = @json($currency);
        const currencyState = {
            mode: 'USD',
            rate: currencyConf ? parseFloat(currencyConf.rate) : 1,
            decimals: currencyConf ? currencyConf.decimals : 2,
        };

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

        // ---- DEBOUNCE ----
        let debounceCalc = null;
        let debounceLineChart = null;

        function scheduleAutoCalc() {
            if (debounceCalc) clearTimeout(debounceCalc);
            debounceCalc = setTimeout(() => {
                const v = getVolume();
                if (v > 0) calculateCosts();
                debounceCalc = null;
            }, 600);
        }

        function scheduleLineChartUpdate() {
            if (debounceLineChart) clearTimeout(debounceLineChart);
            debounceLineChart = setTimeout(() => {
                updateLineChartVolume();
                debounceLineChart = null;
            }, 150);
        }

        // ---- CLEAR BUTTON ----
        document.getElementById('clearVolume').addEventListener('click', () => {
            volumeInput.value = '';
            volumeSlider.value = 0;
            volumeInput.focus();
        });

        volumeSlider.addEventListener('input', () => {
            volumeInput.value = formatNumber(parseInt(volumeSlider.value));
            scheduleAutoCalc();
            scheduleLineChartUpdate();
        });

        volumeInput.addEventListener('input', () => {
            document.querySelector('.calc-input-box').classList.remove('has-error');
            document.getElementById('volumeError').classList.remove('visible');

            const raw = volumeInput.value.replace(/[^0-9]/g, '');
            const num = parseInt(raw) || 0;
            const clamped = Math.max(0, Math.min(10000000, num));

            const pos = volumeInput.selectionStart;
            const oldLen = volumeInput.value.length;

            volumeInput.value = formatNumber(clamped);

            const newLen = volumeInput.value.length;
            volumeInput.setSelectionRange(pos + (newLen - oldLen), pos + (newLen - oldLen));

            if (clamped <= parseInt(volumeSlider.max)) {
                volumeSlider.value = clamped;
            }
            scheduleAutoCalc();
            scheduleLineChartUpdate();
        });

        // ---- CHARTS ----
        let barChart = null;
        let lineChart = null;
        let lastCalcData = null;
        let lastHeadTotals = [0, 0, 0];

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

        // Satu baris mewakili satu API; tier yang dipilih menentukan item mana yang
        // dipakai baris itu. Peralihan tier menukar data-item-id baris berikut sel
        // harga, kuota gratis, dan keterangannya.
        const itemById = new Map();
        pricingData.forEach(cat => cat.items.forEach(item => itemById.set(item.id, item)));

        function usdOrDash(value, fallback) {
            return value === null || value === undefined || value === '' ? fallback : unitPrice(value);
        }

        function applyTierChoice(btn, silent) {
            const row = btn.closest('tr');
            const picker = btn.closest('.tier-picker');
            const id = parseInt(btn.dataset.itemId);
            const item = itemById.get(id);

            picker.querySelectorAll('.tier-opt').forEach(b => {
                const on = b === btn;
                b.classList.toggle('active', on);
                b.setAttribute('aria-checked', on ? 'true' : 'false');
            });

            row.dataset.itemId = id;
            row.querySelector('.item-check').dataset.itemId = id;
            row.querySelector('.td-als').textContent = usdOrDash(btn.dataset.als, '-');
            row.querySelector('.td-google').textContent = usdOrDash(btn.dataset.google, 'N/A');

            const note = row.querySelector('.api-note');
            if (note) {
                note.textContent = btn.dataset.info || '';
                note.className = 'api-note note-' + btn.dataset.tier.toLowerCase();
            }

            const nameCell = row.querySelector('.api-name-cell');
            if (nameCell && item) nameCell.textContent = item.api_name_translated || item.api_name;

            // calculateCosts() menghitung ulang dari server lalu menggambar tabel dan
            // grafik; reRender() di sini hanya akan menampilkan angka tier lama sesaat.
            // Saat memulihkan keadaan, hitungannya cukup sekali di akhir.
            if (silent) return;
            buildLineChartControls();
            renderKeyInsights();
            calculateCosts();
        }

        document.querySelectorAll('.tier-opt').forEach(btn => {
            btn.addEventListener('click', () => applyTierChoice(btn));
        });

        // Individual checkbox → re-render + sync "Select All"
        document.querySelectorAll('.item-check').forEach(cb => {
            cb.addEventListener('change', () => {
                const row = cb.closest('tr');
                row.classList.toggle('row-disabled', !cb.checked);

                const card = cb.closest('.pricing-card');
                const allBoxes = card.querySelectorAll('.item-check');
                const checkAll = card.querySelector('.check-all');
                const allChecked = [...allBoxes].every(b => b.checked);
                const someChecked = [...allBoxes].some(b => b.checked);
                checkAll.checked = allChecked;
                checkAll.indeterminate = !allChecked && someChecked;

                reRender();
                buildLineChartControls();
                renderKeyInsights();
            });
        });

        // "Select All" checkbox → toggle every API in the category
        document.querySelectorAll('.check-all').forEach(cb => {
            cb.addEventListener('change', () => {
                const card = cb.closest('.pricing-card');
                card.querySelectorAll('.item-check').forEach(box => {
                    box.checked = cb.checked;
                    box.closest('tr').classList.toggle('row-disabled', !cb.checked);
                });
                reRender();
                buildLineChartControls();
                renderKeyInsights();
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
                updateLineChartVolume();
            } catch (err) {
                console.error('Calculate error:', err);
            }
        }

        // Format number as USD currency
        // Ringkasan di kepala halaman memakai angka yang sama dengan kartu
        // ringkasan kalkulator, jadi keduanya tidak mungkin berbeda.
        function updateHeadStats(alsTotal, googleTotal, savings) {
            const volEl = document.querySelector('#headVolume span');
            const alsEl = document.getElementById('headAls');
            const googleEl = document.getElementById('headGoogle');
            const saveEl = document.getElementById('headSave');
            if (!volEl || !alsEl) return;

            volEl.textContent = getVolume().toLocaleString();
            alsEl.textContent = formatUSD(alsTotal);
            googleEl.textContent = formatUSD(googleTotal);
            saveEl.textContent = googleTotal > 0 ? savings + '%' : '-';
        }

        function formatMoney(value, minDigits) {
            const n = Number(value) || 0;
            if (currencyState.mode === 'USD') {
                return '$' + n.toLocaleString('en-US', {
                    minimumFractionDigits: minDigits ?? 2,
                    maximumFractionDigits: minDigits ?? 2,
                });
            }
            const converted = n * currencyState.rate;
            const d = currencyState.decimals;
            return currencyConf.symbol + ' ' + converted.toLocaleString(currencyConf.js_locale, {
                minimumFractionDigits: d,
                maximumFractionDigits: d,
            });
        }

        // Nama lama masih dipakai di banyak tempat; diteruskan saja.
        function formatUSD(value) {
            return formatMoney(value);
        }

        // Grafik memakai nilai yang sudah dikonversi, jadi label sumbu dan tooltip
        // tinggal memberi simbol — bukan mengonversi lagi.
        const moneyFactor = () => (currencyState.mode === 'USD' ? 1 : currencyState.rate);
        const moneySymbol = () => (currencyState.mode === 'USD' ? '$' : currencyConf.symbol + ' ');
        const numLocale = () => (currencyState.mode === 'USD' ? 'en-US' : currencyConf.js_locale);
        const axisMoney = (v) => moneySymbol() + Number(v).toLocaleString(numLocale(), { maximumFractionDigits: 0 });
        const chartMoney = (v) => moneySymbol() + Number(v).toLocaleString(numLocale(), {
            minimumFractionDigits: currencyState.mode === 'USD' ? 2 : currencyState.decimals,
            maximumFractionDigits: currencyState.mode === 'USD' ? 2 : currencyState.decimals,
        });

        // Harga satuan di tabel dirender server dalam USD; saat mata uang berganti
        // sel-selnya ditulis ulang dari data mentah.
        function repaintUnitPrices() {
            document.querySelectorAll('tr[data-item-id]').forEach(row => {
                const item = itemById.get(parseInt(row.dataset.itemId));
                if (!item) return;
                const als = row.querySelector('.td-als');
                const google = row.querySelector('.td-google');
                if (als) als.textContent = item.als_price !== null ? unitPrice(item.als_price) : '-';
                if (google) google.textContent = item.google_price !== null ? unitPrice(item.google_price) : 'N/A';
            });
        }

        // Harga per 1.000 request: dua desimal, lebih rinci hanya untuk angka kecil.
        function unitPrice(value) {
            const n = Number(value);
            if (currencyState.mode === 'USD') {
                return '$' + (n < 0.1 ? String(parseFloat(n.toFixed(4))) : n.toFixed(2));
            }
            const converted = n * currencyState.rate;
            return currencyConf.symbol + ' ' + converted.toLocaleString(currencyConf.js_locale, {
                minimumFractionDigits: currencyState.decimals,
                maximumFractionDigits: currencyState.decimals,
            });
        }

        function renderResults(data) {
            // --- Calculate totals (separate comparable vs exclusive) ---
            let comparableAls = 0,
                comparableGoogle = 0,
                exclusiveAls = 0;
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

            // Katalog GrabMaps tidak punya item ALS-only, jadi kartu "ALS exclusive"
            // hanya ikut tampil kalau datanya memang ada.
            const hasExclusive = data.results.some(cat => cat.items.some(item => item.als_only));

            // --- Summary cards ---
            lastHeadTotals = [comparableAls, comparableGoogle, savings];
            updateHeadStats(comparableAls, comparableGoogle, savings);

            const summaryContainer = document.getElementById('calculatorSummary');
            summaryContainer.innerHTML = `<div class="result-summary">
                <div class="summary-card summary-als">
                    <div class="summary-label">${pricingT.summary_als}</div>
                    <div class="summary-value">${formatUSD(comparableAls)}</div>
                    <div class="summary-sub">${pricingT.summary_comparable}</div>
                </div>
                <div class="summary-card summary-google">
                    <div class="summary-label">${pricingT.summary_google}</div>
                    <div class="summary-value">${formatUSD(comparableGoogle)}</div>
                    <div class="summary-sub">${pricingT.summary_comparable}</div>
                </div>
                <div class="summary-card summary-savings">
                    <div class="summary-label">${pricingT.you_save}</div>
                    <div class="summary-value">${savings}%</div>
                    <div class="summary-sub">${formatUSD(savedAmount)}${pricingT.per_month}</div>
                </div>
                ${hasExclusive ? `
                <div class="summary-card summary-exclusive">
                    <div class="summary-label">${pricingT.als_exclusive}</div>
                    <div class="summary-value">${formatUSD(exclusiveAls)}</div>
                    <div class="summary-sub">${pricingT.trackers_geofences}</div>
                </div>` : ''}
            </div>`;

            // --- Update table cells per item ---
            data.results.forEach(cat => {
                let catComparableAls = 0,
                    catComparableGoogle = 0;
                let catAlsAll = 0,
                    catGoogleAll = 0;

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
                            row.querySelector('.td-savings').innerHTML = '<span class="als-only-badge">' + pricingT.als_only_badge + '</span>';
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
                alsData.push(parseFloat((alsTotal * moneyFactor()).toFixed(2)));
                googleData.push(parseFloat((googleTotal * moneyFactor()).toFixed(2)));
            });

            if (barChart) barChart.destroy();

            barChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                            label: pricingT.summary_als,
                            data: alsData,
                            backgroundColor: 'rgba(0, 177, 79, 0.8)',
                            borderColor: '#00B14F',
                            borderWidth: 1,
                            borderRadius: 6,
                        },
                        {
                            label: pricingT.summary_google,
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
                    maintainAspectRatio: false,
                    layout: {
                        padding: { top: 8, right: 8, bottom: 0, left: 8 }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.dataset.label}: ${chartMoney(ctx.raw)}`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: v => axisMoney(v)
                            }
                        }
                    }
                }
            });
        }

        // ---- LINE CHART ----
        let activeLineChartItem = null;
        let comparableLineItems = [];

        // Chip kurva mengikuti daftar harga: satu chip per API (bukan per tier),
        // memakai tier yang sedang dipilih di baris itu, dan hanya API yang
        // dicentang. Dibangun ulang setiap centang atau tier berubah.
        function shortLabel(item) {
            const full = item.api_name_translated || item.api_name;
            const head = full.split(' / ')[0].trim();
            return head + (item.tier ? ` (${item.tier})` : '');
        }

        function buildLineChartControls() {
            const container = document.getElementById('lineChartControls');
            const previous = activeLineChartItem ? activeLineChartItem.key : null;
            container.innerHTML = '';
            comparableLineItems = [];

            document.querySelectorAll('.item-check:checked').forEach(cb => {
                const item = itemById.get(parseInt(cb.dataset.itemId));
                if (!item || item.als_price === null || item.google_price === null) return;
                comparableLineItems.push({
                    key: (item.api_name_translated || item.api_name),
                    label: shortLabel(item),
                    title: (item.api_name_translated || item.api_name) + (item.tier ? ` — ${item.tier}` : ''),
                    alsRate: parseFloat(item.als_price),
                    googleRate: parseFloat(item.google_price),
                });
            });

            if (comparableLineItems.length === 0) {
                activeLineChartItem = null;
                if (lineChart) { lineChart.destroy(); lineChart = null; }
                return;
            }

            const keep = comparableLineItems.find(i => i.key === previous);
            activeLineChartItem = keep || comparableLineItems[0];

            comparableLineItems.forEach(item => {
                const btn = document.createElement('button');
                btn.className = 'btn-chip' + (item === activeLineChartItem ? ' active' : '');
                btn.textContent = item.label;
                btn.title = item.title;
                btn.onclick = () => {
                    container.querySelectorAll('.btn-chip').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    activeLineChartItem = item;
                    renderLineChart(item);
                };
                container.appendChild(btn);
            });

            renderLineChart(activeLineChartItem);
        }

        function updateLineChartVolume() {
            if (activeLineChartItem) renderLineChart(activeLineChartItem);
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
                alsLine.push(parseFloat(((v / 1000) * item.alsRate * moneyFactor()).toFixed(2)));
                googleLine.push(parseFloat(((v / 1000) * item.googleRate * moneyFactor()).toFixed(2)));
            }

            if (lineChart) lineChart.destroy();

            lineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: volumes.map(v => v >= 1000 ? (v / 1000) + 'K' : v),
                    datasets: [{
                            label: pricingT.summary_als,
                            data: alsLine,
                            borderColor: '#00B14F',
                            backgroundColor: 'rgba(0,177,79,0.08)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                        },
                        {
                            label: pricingT.summary_google,
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
                    maintainAspectRatio: false,
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
                                label: ctx => `${ctx.dataset.label}: ${chartMoney(ctx.raw)}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: pricingT.monthly_requests
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: pricingT.cost_usd
                            },
                            ticks: {
                                callback: v => axisMoney(v)
                            }
                        }
                    }
                }
            });
        }

        // ---- KEY INSIGHTS (dynamic from pricingData) ----
        function renderKeyInsights() {
            let mapTilesItem = null;
            let exclusiveCount = 0;
            let savingsSum = 0;
            let savingsCount = 0;

            pricingData.forEach(cat => cat.items.forEach(item => {
                if (item.als_only) exclusiveCount++;
                if (item.api_name === 'Map Tiles' && item.als_price != null && item.google_price != null) {
                    mapTilesItem = item;
                }
            }));

            // Rata-rata dihitung dari API yang sedang dicentang beserta tier yang
            // dipilih, jadi angkanya mengikuti apa yang terlihat di daftar.
            document.querySelectorAll('.item-check:checked').forEach(cb => {
                const item = itemById.get(parseInt(cb.dataset.itemId));
                if (!item || item.als_price == null || item.google_price == null) return;
                const als = parseFloat(item.als_price);
                const google = parseFloat(item.google_price);
                if (google <= 0) return;
                savingsSum += (1 - als / google) * 100;
                savingsCount++;
            });

            const avgEl = document.getElementById('insightAvgSavings');
            const avgDescEl = document.getElementById('insightAvgSavingsDesc');
            if (avgEl) {
                avgEl.textContent = savingsCount > 0 ? (savingsSum / savingsCount).toFixed(1) + '%' : '-';
                avgDescEl.textContent = pricingT.avg_savings_desc.replace(':count', savingsCount);
            }

            const mapTilesEl = document.getElementById('insightMapTiles');
            const mapTilesDescEl = document.getElementById('insightMapTilesDesc');
            if (mapTilesItem && mapTilesEl) {
                const als = parseFloat(mapTilesItem.als_price);
                const google = parseFloat(mapTilesItem.google_price);
                const pct = google > 0 ? Math.round((1 - als / google) * 100) : 0;
                mapTilesEl.textContent = pct + '%';
                mapTilesDescEl.textContent = pricingT.map_tiles_desc.replace(':als', als.toFixed(2)).replace(':google', google.toFixed(2));
            }

            const exclusiveEl = document.getElementById('insightExclusive');
            const exclusiveCard = document.getElementById('insightExclusiveCard');
            if (exclusiveCount > 0) {
                if (exclusiveEl) exclusiveEl.textContent = exclusiveCount + '+';
                if (exclusiveCard) exclusiveCard.hidden = false;
            } else if (exclusiveCard) {
                exclusiveCard.hidden = true;
            }
        }

        function syncCheckAllState() {
            document.querySelectorAll('.pricing-card').forEach(card => {
                const allBoxes = card.querySelectorAll('.item-check');
                const checkAll = card.querySelector('.check-all');
                const allChecked = [...allBoxes].every(b => b.checked);
                const someChecked = [...allBoxes].some(b => b.checked);
                checkAll.checked = allChecked;
                checkAll.indeterminate = !allChecked && someChecked;
            });
        }

        // ---- KEADAAN YANG BERTAHAN SAAT GANTI BAHASA ----
        // Ganti bahasa tetap memuat ulang halaman (semua teksnya dirender server),
        // jadi yang sudah diisi pengguna disimpan dulu lalu dipasang kembali:
        // volume, tier yang dipilih, dan posisi gulir.
        const STATE_KEY = 'gm-pricing-state';

        function savePageState() {
            try {
                sessionStorage.setItem(STATE_KEY, JSON.stringify({
                    volume: volumeInput.value,
                    scroll: Math.round(window.scrollY),
                    // Id item tetap sama di semua bahasa, jadi pilihan tier aman dibawa.
                    tiers: [...document.querySelectorAll('.tier-opt.active')].map(b => b.dataset.itemId),
                    unchecked: [...document.querySelectorAll('.item-check:not(:checked)')].map(cb => cb.dataset.itemId),
                }));
            } catch (e) { /* mode privat */ }
        }

        function restorePageState() {
            let state = null;
            try {
                const raw = sessionStorage.getItem(STATE_KEY);
                sessionStorage.removeItem(STATE_KEY);
                state = raw ? JSON.parse(raw) : null;
            } catch (e) { return; }
            if (!state) return;

            if (state.volume) {
                volumeInput.value = state.volume;
                volumeSlider.value = Math.min(500000, getVolume());
            }

            (state.tiers || []).forEach(id => {
                const btn = document.querySelector(`.tier-opt[data-item-id="${id}"]`);
                if (btn && !btn.classList.contains('active')) applyTierChoice(btn, true);
            });

            (state.unchecked || []).forEach(id => {
                const cb = document.querySelector(`.item-check[data-item-id="${id}"]`);
                if (cb && cb.checked) {
                    cb.checked = false;
                    cb.closest('tr').classList.add('row-disabled');
                }
            });

            if (state.scroll) {
                requestAnimationFrame(() => window.scrollTo({ top: state.scroll, behavior: 'instant' }));
            }
        }

        // ---- RODA BAHASA ----
        // Kode yang berada di tengah diberi penekanan; sisanya meredup ke tepi.
        // Gulir roda tetikus dipetakan ke gulir mendatar supaya terasa seperti
        // pemilih angka, bukan daftar yang harus digeser dengan bilah gulir.
        (function () {
            const track = document.getElementById('langTrack');
            const wheel = document.getElementById('langWheel');
            if (!track || !wheel) return;

            const items = [...track.querySelectorAll('.lang-item')];

            // Tepi yang sudah mentok tidak diberi gradien pudar — kalau tidak, pil
            // terlihat seperti terpotong padahal memang tidak bisa digeser lagi.
            function markEdges() {
                const max = track.scrollWidth - track.clientWidth;
                wheel.classList.toggle('at-start', track.scrollLeft <= 1);
                wheel.classList.toggle('at-end', track.scrollLeft >= max - 1);
            }

            function markCenter() {
                const mid = track.scrollLeft + track.clientWidth / 2;
                let nearest = items[0];
                let best = Infinity;
                items.forEach(item => {
                    const c = item.offsetLeft + item.offsetWidth / 2;
                    const d = Math.abs(c - mid);
                    if (d < best) { best = d; nearest = item; }
                });
                items.forEach(i => i.classList.toggle('is-center', i === nearest));
                markEdges();
            }

            const centerActive = () => {
                const active = track.querySelector('.lang-item.active') || items[0];
                const prev = track.style.scrollBehavior;
                track.style.scrollBehavior = 'auto';
                track.scrollLeft = active.offsetLeft + active.offsetWidth / 2 - track.clientWidth / 2;
                track.style.scrollBehavior = prev;
                markCenter();
            };

            // Berhenti di antara dua kode terasa seperti macet, jadi setiap gulir
            // yang mereda dikunci ke kode terdekat.
            let snapTimer = null;
            let goTimer = null;
            let userMoved = false;

            // Roda ini pemilih, bukan sekadar sorotan: begitu berhenti di kode
            // lain, bahasanya benar-benar berpindah. Ditunda sesaat supaya kode
            // yang cuma terlewat saat menggeser tidak ikut membuka halaman.
            const goToCentered = () => {
                const nearest = track.querySelector('.lang-item.is-center');
                if (!nearest || !userMoved) return;
                if (nearest.classList.contains('active')) return;
                wheel.classList.add('is-loading');
                savePageState();
                window.location.href = nearest.href;
            };

            const snapToNearest = () => {
                const nearest = track.querySelector('.lang-item.is-center');
                if (!nearest) return;
                const target = nearest.offsetLeft + nearest.offsetWidth / 2 - track.clientWidth / 2;
                clearTimeout(goTimer);
                if (Math.abs(target - track.scrollLeft) >= 1) {
                    track.scrollTo({ left: target, behavior: 'smooth' });
                }
                goTimer = setTimeout(goToCentered, 420);
            };
            const scheduleSnap = () => {
                clearTimeout(snapTimer);
                snapTimer = setTimeout(snapToNearest, 140);
            };

            track.addEventListener('scroll', () => {
                markCenter();
                clearTimeout(goTimer);
                if (!dragging) scheduleSnap();
            }, { passive: true });

            track.addEventListener('wheel', (e) => {
                if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) return;
                e.preventDefault();
                userMoved = true;
                track.scrollLeft += e.deltaY;
            }, { passive: false });

            // Geser dengan tetikus atau jari. Kalau jaraknya cukup jauh, klik
            // pada kode yang kebetulan ada di bawah kursor tidak ikut jalan.
            let dragging = false;
            let startX = 0;
            let startScroll = 0;
            let moved = 0;

            track.addEventListener('pointerdown', (e) => {
                if (e.pointerType === 'mouse' && e.button !== 0) return;
                dragging = true;
                userMoved = true;
                moved = 0;
                startX = e.clientX;
                startScroll = track.scrollLeft;
                track.setPointerCapture(e.pointerId);
                track.classList.add('is-dragging');
            });

            track.addEventListener('pointermove', (e) => {
                if (!dragging) return;
                const dx = e.clientX - startX;
                moved = Math.max(moved, Math.abs(dx));
                track.scrollLeft = startScroll - dx;
            });

            const endDrag = (e) => {
                if (!dragging) return;
                dragging = false;
                track.classList.remove('is-dragging');
                try { track.releasePointerCapture(e.pointerId); } catch (err) { /* sudah lepas */ }
                snapToNearest();
            };
            track.addEventListener('pointerup', endDrag);
            track.addEventListener('pointercancel', endDrag);

            track.addEventListener('click', (e) => {
                if (moved > 6) {
                    e.preventDefault();
                    moved = 0;
                    return;
                }
                if (e.target.closest('.lang-item')) savePageState();
            });

            window.addEventListener('resize', centerActive);
            centerActive();
        })();

        // ---- SAKELAR MATA UANG ----
        // Pilihan mata uang dan kurs yang disunting disimpan per browser; kursnya
        // hanya lapisan tampilan, hitungan tetap berjalan dalam USD.
        (function () {
            const group = document.querySelector('.cur-switch');
            const rateBox = document.getElementById('rateBox');
            const rateInput = document.getElementById('rateInput');
            if (!group || !currencyConf) return;

            const RATE_KEY = 'gm-rate-' + currencyConf.code;
            const MODE_KEY = 'gm-currency';

            const store = (k, v) => { try { localStorage.setItem(k, v); } catch (e) { /* mode privat */ } };
            const read = (k) => { try { return localStorage.getItem(k); } catch (e) { return null; } };

            const savedRate = parseFloat(read(RATE_KEY));
            if (savedRate > 0) currencyState.rate = savedRate;

            function repaintAll() {
                repaintUnitPrices();
                reRender();
                updateLineChartVolume();
                if (lastCalcData) updateHeadStats(...lastHeadTotals);
            }

            function setMode(mode) {
                currencyState.mode = mode === 'LOCAL' ? 'LOCAL' : 'USD';
                group.querySelectorAll('.cur-opt').forEach(b => {
                    b.classList.toggle('active', b.dataset.cur === currencyState.mode);
                });
                rateBox.hidden = currencyState.mode !== 'LOCAL';
                const tag = document.getElementById('curTag');
                if (tag) tag.textContent = currencyState.mode === 'LOCAL' ? currencyConf.code : 'USD';
                store(MODE_KEY, currencyState.mode);
                repaintAll();
            }

            group.addEventListener('click', (e) => {
                const btn = e.target.closest('.cur-opt');
                if (btn) setMode(btn.dataset.cur);
            });

            // Angka kurs ditulis dengan pemisah ribuan sesuai negaranya
            // (17.900, bukan 17900). Pemisah dibersihkan saat dibaca, dan
            // penulisan ulang dilakukan saat lepas fokus supaya kursor tidak
            // meloncat waktu mengetik.
            const parseRate = (text) => {
                const cleaned = String(text).replace(/[^0-9.,]/g, '');
                const lastDot = cleaned.lastIndexOf('.');
                const lastComma = cleaned.lastIndexOf(',');
                const sep = Math.max(lastDot, lastComma);
                // Pemisah desimal = tanda terakhir yang diikuti kurang dari 3 angka.
                if (sep > -1 && cleaned.length - sep - 1 < 3 && cleaned.length - sep - 1 > 0) {
                    const int = cleaned.slice(0, sep).replace(/[.,]/g, '');
                    return parseFloat(int + '.' + cleaned.slice(sep + 1).replace(/[.,]/g, ''));
                }
                return parseFloat(cleaned.replace(/[.,]/g, ''));
            };

            const showRate = (value) => {
                rateInput.value = Number(value).toLocaleString(currencyConf.js_locale, {
                    maximumFractionDigits: 4,
                });
            };

            // Pemisah ribuan dipasang sambil mengetik. Posisi kursor dikembalikan
            // dengan menghitung selisih panjang teks, jadi tidak meloncat ke ujung
            // setiap kali satu titik bertambah.
            const sepParts = new Intl.NumberFormat(currencyConf.js_locale).formatToParts(12345.6);
            const groupSep = (sepParts.find(x => x.type === 'group') || { value: ',' }).value;
            const decSep = (sepParts.find(x => x.type === 'decimal') || { value: '.' }).value;

            const splitRate = (text) => {
                const cleaned = String(text).replace(/[^0-9.,]/g, '');
                const sep = Math.max(cleaned.lastIndexOf('.'), cleaned.lastIndexOf(','));
                const tail = sep > -1 ? cleaned.length - sep - 1 : -1;
                if (sep > -1 && tail >= 0 && tail < 3) {
                    return [cleaned.slice(0, sep).replace(/[.,]/g, ''), cleaned.slice(sep + 1).replace(/[.,]/g, '')];
                }
                return [cleaned.replace(/[.,]/g, ''), null];
            };

            const groupDigits = (digits) => digits.replace(/\B(?=(\d{3})+(?!\d))/g, groupSep);

            rateInput.addEventListener('input', () => {
                const caret = rateInput.selectionStart;
                const beforeLen = rateInput.value.length;

                const [intPart, decPart] = splitRate(rateInput.value);
                rateInput.value = groupDigits(intPart) + (decPart === null ? '' : decSep + decPart);

                const shift = rateInput.value.length - beforeLen;
                const next = Math.max(0, caret + shift);
                rateInput.setSelectionRange(next, next);

                const value = parseRate(rateInput.value);
                if (!(value > 0)) return;
                currencyState.rate = value;
                store(RATE_KEY, String(value));
                repaintAll();
            });

            rateInput.addEventListener('blur', () => showRate(currencyState.rate));
            rateInput.addEventListener('focus', () => rateInput.select());
            showRate(currencyState.rate);

            if (read(MODE_KEY) === 'LOCAL') setMode('LOCAL');
        })();

        // Menekan angka volume di kepala membawa kembali ke kolom isian.
        (function () {
            const btn = document.getElementById('headVolume');
            if (!btn) return;
            btn.addEventListener('click', () => {
                document.querySelector('.calculator-card').scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => volumeInput.focus({ preventScroll: true }), 320);
            });
        })();

        // Kepala halaman: melebar penuh saat di puncak, menyusut jadi kartu
        // mengambang begitu digulir. Tingginya dibagikan lewat --head-h supaya
        // kolom grafik yang sticky berhenti tepat di bawahnya.
        (function () {
            const head = document.getElementById('pageHead');
            const shell = document.getElementById('headShell');
            if (!head || !shell) return;

            const sync = () => {
                const stuck = window.scrollY > 12;
                head.classList.toggle('is-stuck', stuck);
                shell.classList.toggle('is-stuck', stuck);
                document.documentElement.style.setProperty('--head-h', shell.offsetHeight + 'px');
            };

            window.addEventListener('scroll', sync, { passive: true });
            window.addEventListener('resize', sync);
            sync();
        })();

        // ---- TEMA: terang / gelap / ikut sistem ----
        // Warna teks & garis grafik diambil dari token CSS supaya ikut tema.
        function applyChartTheme() {
            if (typeof Chart === 'undefined') return;
            const cs = getComputedStyle(document.documentElement);
            Chart.defaults.color = cs.getPropertyValue('--muted').trim() || '#8a938f';
            Chart.defaults.borderColor = cs.getPropertyValue('--line').trim() || '#ebeeee';
        }

        function repaintCharts() {
            applyChartTheme();
            reRender();
            updateLineChartVolume();
        }

        (function () {
            const group = document.getElementById('themeToggle');
            if (!group) return;

            const current = () => {
                try { return localStorage.getItem('gm-theme') || 'system'; } catch (e) { return 'system'; }
            };
            const paint = () => group.querySelectorAll('[data-theme-set]').forEach(
                b => b.classList.toggle('active', b.dataset.themeSet === current())
            );

            group.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-theme-set]');
                if (!btn) return;

                const root = document.documentElement;
                root.classList.add('theme-anim');
                root.setAttribute('data-theme', btn.dataset.themeSet);
                try { localStorage.setItem('gm-theme', btn.dataset.themeSet); } catch (err) { /* mode privat */ }

                paint();
                btn.classList.add('pop');
                setTimeout(() => btn.classList.remove('pop'), 450);
                setTimeout(() => root.classList.remove('theme-anim'), 350);
                repaintCharts();
                btn.blur();
            });

            // Saat pilihan "ikut sistem", tema OS yang berubah langsung diikuti.
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                    if (current() === 'system') repaintCharts();
                });
            }

            paint();
        })();

        document.addEventListener('DOMContentLoaded', () => {
            applyChartTheme();
            restorePageState();
            renderKeyInsights();
            syncCheckAllState();
            buildLineChartControls();
            calculateCosts();

            /* ═══ Provider tabs (Compare / AWS / Google) ═══ */
            const savedView = (() => {
                try { return localStorage.getItem('pricing_provider_view') || 'compare'; } catch (_) { return 'compare'; }
            })();
            function setProviderView(view) {
                document.body.setAttribute('data-provider-view', view);
                document.querySelectorAll('.provider-tab').forEach(t => {
                    const active = t.dataset.view === view;
                    t.classList.toggle('active', active);
                    t.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                try { localStorage.setItem('pricing_provider_view', view); } catch (_) {}
                // Charts re-render so bars reflect visible providers
                if (typeof reRender === 'function') reRender();
            }
            document.querySelectorAll('.provider-tab').forEach(tab => {
                tab.addEventListener('click', () => setProviderView(tab.dataset.view));
            });
            setProviderView(savedView);

            /* ═══ Reading progress bar ═══ */
            const bar = document.getElementById('priceProgressFill');
            if (bar) {
                const updateBar = () => {
                    const doc = document.documentElement;
                    const scrollTop = window.scrollY || doc.scrollTop;
                    const scrollHeight = doc.scrollHeight - doc.clientHeight;
                    const pct = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
                    bar.style.width = pct + '%';
                };
                window.addEventListener('scroll', updateBar, { passive: true });
                updateBar();
            }

            /* ═══ Reveal-on-scroll for key sections ═══ */
            const revealTargets = document.querySelectorAll(
                '.pricing-card, .insight-card, .chart-card, .calculator-card'
            );
            revealTargets.forEach((el, i) => {
                el.classList.add('reveal');
                el.style.transitionDelay = Math.min(i * 45, 260) + 'ms';
            });
            const revealObs = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        e.target.classList.add('visible');
                        revealObs.unobserve(e.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
            revealTargets.forEach(el => revealObs.observe(el));
        });
    </script>
</body>

</html>