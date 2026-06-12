<?php
/**
 * Tambahkan blok 'ml' ini ke dalam array di config/services.php (proyek Laravel),
 * sejajar dengan entri 'postmark', 'ses', dst.
 */

return [
    // ... entri lain yang sudah ada ...

    'ml' => [
        'url' => env('ML_URL', 'http://ml:8000'),
    ],
];
