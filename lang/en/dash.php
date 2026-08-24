<?php

/** Teks dashboard admin (template baru). */
return [
    'welcome'          => 'Welcome back,',
    'refresh'          => 'Refresh data',
    'refresh_hint'     => 'Pull this range again from CloudWatch',
    'this_month'       => 'this month',

    // Alert keadaan
    'aws_error'        => 'Failed to fetch AWS data: :error',
    'check_account'    => 'Check account',
    'budget_over'      => 'AWS budget alert threshold exceeded.',
    'budget_near'      => 'Approaching the AWS budget alert threshold.',
    'budget_body'      => 'Estimated cost :range :amount (≈ Rp :idr) is already :pct% of the :threshold threshold.',
    'budget_edit'      => 'Change threshold',

    // Kartu biaya
    'cost_title'       => 'Cost :range',
    'cost_sub'         => 'Estimate including :pct% VAT',
    'cost_brand'       => 'AWS LOCATION',
    'cost_total'       => 'Total estimate',

    // Kartu request
    'requests_title'   => 'Requests :range',
    'delta_halves'     => 'Later half :late vs earlier half :early',
    'delta_none'       => 'Range too short to compare',
    'delta_new'        => 'new',

    // Grafik
    'chart_title'      => 'Daily Requests',
    'chart_sub'        => 'CloudWatch · :range',
    'chart_days'       => ':count Days',
    'no_data'          => 'No request data yet.',

    // Tabel operasi
    'ops_title'        => 'Breakdown by Operation',
    'ops_sub'          => 'Usage × official AWS price + :pct% VAT',
    'ops_op'           => 'Operation',
    'subtotal'         => 'Subtotal',
    'vat'              => 'VAT :pct%',
    'total_vat'        => 'Total + VAT',
    'ops_note'         => 'The estimate can be off by ~5% from the final invoice.',
    'fetched_at'       => 'Data as of :time WIB.',
    'no_snapshot'      => 'No data yet — hit Refresh.',

    // Budget
    'budget_title'     => 'Budget Status',
    'budget_threshold' => 'AWS Budgets threshold :amount',
    'budget_used'      => 'Used :range',

    // Kategori
    'cat_title'        => 'Cost by Category',
    'cat_sub'          => 'Before VAT',
    'cat_maps'         => 'Maps',
    'cat_places'       => 'Places / Search',
    'cat_routes'       => 'Routes',
    'requests_word'    => 'requests',

    // Top pemakai & akun
    'top_title'        => 'Most Used API Keys',
    'top_sub'          => 'Usage ranking :range',
    'no_data_short'    => 'No data',
    'by_account'       => 'Usage per AWS Account',
    'accounts_active'  => ':count active accounts',

    'key_budget_over' => 'Cost limit exceeded on :key',
    'key_budget_near' => 'Close to the cost limit on :key',
    'key_budget_body' => ':amount of :threshold (:pct%) in :range.',
    'key_budget_open' => 'Open key',
];
