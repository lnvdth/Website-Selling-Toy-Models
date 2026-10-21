<?php

return [
    'bank_bin' => getenv('PAYMENT_BANK_BIN') ?: '970422',
    'bank_name' => getenv('PAYMENT_BANK_NAME') ?: 'MB Bank',
    'account_number' => getenv('PAYMENT_ACCOUNT_NUMBER') ?: '8603042005',
    'account_name' => getenv('PAYMENT_ACCOUNT_NAME') ?: 'Hoàng Long Ngũ',
];
