<?php

/**
 * Teks milik kerangka admin baru (layouts/admin-v2 + partial-nya):
 * rail, topbar, command palette, pemilih tanggal, dan tombol umum.
 * Teks khusus per halaman ada di file lain (mis. users.php).
 */
return [
    // Rail & topbar
    'aws_accounts'      => 'AWS Accounts',
    'cost_settings'     => 'Rates & Tax',
    'simulator'         => 'Simulator',
    'roles'             => 'Roles & Access',
    'users'             => 'Users',
    'open_homepage'     => 'Open homepage',
    'api_tester'        => 'API Tester',
    'sign_out'          => 'Sign out',
    'homepage'          => 'Homepage',
    'language'          => 'Language',
    'theme'             => 'Theme',
    'theme_light'       => 'Light mode',
    'theme_dark'        => 'Dark mode',
    'theme_system'      => 'Follow system',
    'role_admin'        => 'Administrator',
    'role_operator'     => 'Operator',
    'verified'          => 'Verified',
    'unverified'        => 'Unverified',

    // Pemilih akun AWS
    'aws_scope_title'   => 'Data shown',
    'aws_scope_hint'    => 'AWS account being displayed',
    'aws_env_creds'     => '.env credentials',
    'aws_no_account'    => 'No account saved in the database yet',
    'aws_no_creds'      => 'no credentials yet',
    'aws_active'        => 'Active',
    'aws_view'          => 'View',
    'aws_manage'        => 'Manage accounts & default',
    'loading'           => 'Loading…',

    // Command palette
    'search'            => 'Search',
    'search_hint'       => 'Search (⌘K)',
    'search_placeholder' => 'Search companies, AWS accounts, pages…',
    'search_pages'      => 'Pages',
    'search_actions'    => 'Quick actions',
    'search_page_sub'   => 'Admin page',
    'search_no_result'  => 'No results for “:query”',
    'search_pick'       => 'select',
    'search_open'       => 'open',
    'search_close'      => 'close',
    'action_add_company'    => 'Add company',
    'action_add_company_d'  => 'Create a new company',
    'action_add_key'        => 'Add API key',
    'action_add_key_d'      => 'Create an API key on AWS',
    'action_refresh'        => 'Refresh AWS data',
    'action_refresh_d'      => 'Pull CloudWatch again',
    'action_homepage_d'     => 'Public map',
    'action_tester_d'       => 'Test endpoints',
    'companies'         => 'Companies',

    // Pemilih rentang tanggal
    'range_this_month'  => 'This month',
    'range_days'        => ':count days',
    'range_last_days'   => ':count days',
    'range_prev_month'  => 'Last month',
    'range_note'        => 'A new range pulls data from CloudWatch.',
    'range_max'         => '(maximum)',

    // Umum
    'hour'              => 'Hour',
    'minute'            => 'Minute',
    'pick_date'         => 'Pick a date',
    'no_limit'          => 'No limit',
    'per_month'         => 'per month',
    'no_access'         => 'You do not have access to this section.',
    'cancel'            => 'Cancel',
    'apply'             => 'Apply',
    'save'              => 'Save changes',
    'prev'              => 'Previous',
    'next'              => 'Next',
    'close'             => 'Close',
    'show_password'     => 'Show password',
    'hide_password'     => 'Hide password',
    'generate'          => 'Generate random',
];
