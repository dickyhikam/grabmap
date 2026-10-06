@php
    use App\Services\AwsLocationService;

    // Harga resmi AWS per 1.000 request ditempel di bawah nama operasi supaya
    // pembaca bisa mengurut sendiri jumlah x harga = biaya, tanpa kolom tambahan
    // yang membuat tabel melebar.
    $rate = AwsLocationService::PRICING[$op] ?? 0;
    $cost = ($count / 1000) * $rate;
@endphp

<tr @class(['cat-item' => $child])>
    <td class="op-cell">
        <div class="op-name">
            <span class="q-dot"></span>
            <span class="fw-semibold">{{ $op }}</span>
            <span class="op-rate">${{ number_format($rate, 2) }} / 1k</span>
        </div>
        <div class="op-track"><div class="op-fill" style="width: {{ ($count / $opMax) * 100 }}%;"></div></div>
    </td>
    <td class="num" data-label="{{ __('apikeys.requests') }}">{{ number_format($count) }}</td>
    <td class="num" data-label="{{ __('apikeys.est_cost') }}">{{ $usd($cost) }}</td>
    <td class="num idr-cell" data-label="{{ __('apikeys.cost_idr') }}"
        data-idr="{{ $cost }}" data-idr-prefix="Rp ">{{ $rp($cost) }}</td>
</tr>
