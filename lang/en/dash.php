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
    'attention_title'   => 'Needs attention',
    'attention_sub'     => 'Things worth fixing before the client notices',
    'attention_none'    => 'Nothing needs attention right now.',
    'att_over_budget'   => ':count key is over its cost limit|:count keys are over their cost limit',
    'att_near_budget'   => ':count key is close to its cost limit|:count keys are close to their cost limit',
    'att_expiring'      => ':count key expires within 14 days|:count keys expire within 14 days',
    'att_expiring_one'  => '":name" expires :when',
    'att_disabled'      => ':count key is deactivated|:count keys are deactivated',
    'att_no_key'        => ':count active company has no API key yet|:count active companies have no API key yet',
    'att_never_pulled'  => ':count key has never had its usage pulled|:count keys have never had their usage pulled',
    'att_stale'         => 'Usage data was last pulled :when',
    'att_fix'           => 'Open',

    'client_title'      => 'Client reports',
    'client_sub'        => 'Shared links, last 7 days',
    'client_links'      => 'Active links',
    'client_opens'      => 'Opens',
    'client_readers'    => 'Readers',
    'client_last'       => 'Last opened :when',
    'client_never'      => 'No one has opened them yet',
    'client_none'       => 'No report link has been shared yet.',
    'client_manage'     => 'Companies',
    'per_day'  => 'req/day',
    'peak_day' => 'Peak :day · :count',
];
