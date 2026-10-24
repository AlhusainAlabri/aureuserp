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

];
