<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Arabic PDF font family
    |--------------------------------------------------------------------------
    |
    | Dompdf cannot use system "local()" fonts. Bundled TTFs under resources/fonts
    | are registered automatically. Supported keys: Amiri, DejaVu Sans.
    |
    | Switch via .env: PDF_ARABIC_FONT=Amiri
    |
    */

    'arabic_font' => env('PDF_ARABIC_FONT', 'Amiri'),

    'fonts' => [
        'Amiri' => [
            'normal' => resource_path('fonts/amiri/Amiri-Regular.ttf'),
            'bold'   => resource_path('fonts/amiri/Amiri-Bold.ttf'),
        ],

        // Ships with Dompdf — used as automatic fallback if Amiri files are missing.
        'DejaVu Sans' => [
            'normal' => null,
            'bold'   => null,
        ],
    ],

];
