<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seed Demo Data
    |--------------------------------------------------------------------------
    |
    | Sample employees, partners and inventory records for demos and local
    | development. Keep this off for a customer install: the demo employee
    | seeder replaces the employees table and the sample people would show up
    | in every employee dropdown.
    |
    */

    'seed_demo_data' => (bool) env('ERP_SEED_DEMO_DATA', false),

    /*
    |--------------------------------------------------------------------------
    | Standard VAT Rates
    |--------------------------------------------------------------------------
    |
    | ISO country code => standard VAT/sales tax percentage. The default taxes
    | the installer seeds are set to this rate for the company's country. Add
    | a country here to support it; countries not listed keep the seeded taxes.
    |
    */

    'vat_rates' => [
        'OM' => 5,
        'AE' => 5,
        'SA' => 15,
        'BH' => 10,
    ],

];
