<?php

return [
    'title'          => 'Service charge',
    'sub_account'    => 'Default rate for every company on this account',
    'sub_company'    => 'Fee charged to this company on top of the AWS cost',
    'percent'        => 'Percent of AWS cost',
    'min'            => 'Monthly minimum',
    'effective'      => 'Effective from',
    'effective_hint' => 'Reports before this date keep the old rate.',
    'how'            => 'The higher of the percentage and the minimum is charged. The minimum is prorated when a report covers less than a full month. VAT is charged on AWS cost + service charge.',
    'zero_hint'      => 'Enter 0 in both for no service charge.',
    'mode_inherit'   => 'Use the AWS account rate',
    'mode_custom'    => 'Custom rate for this company',
    'inherit_none'   => 'account has no rate yet',
    'no_account'     => 'This company is not linked to an AWS account — following the account rate means no service charge.',
    'history'        => 'Rate history',
    'history_inherit'=> 'account rate',
    'none'           => 'No service charge',
    'by'             => 'by :name',
    'summary'        => ':pct · min :min / month',
    'err_percent'    => 'Percent must be between 0 and 100.',
    'err_min'        => 'Minimum cannot be negative.',

    'incl'           => 'incl. service charge & :pct% VAT',
    'line'           => 'Service charge',
    'basis_min'      => 'minimum :amount/month, prorated',
];
