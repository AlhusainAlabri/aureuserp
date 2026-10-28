<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deferred Extension Migrations
    |--------------------------------------------------------------------------
    |
    | App-level migrations that extend tables owned by plugins. On a clean
    | install they run before the plugins exist, skip themselves and are still
    | recorded as ran. `erp:ensure-extension-schema` re-runs them (they are
    | idempotent) after each plugin install so the columns and tables appear.
    |
    */

    'deferred_migrations' => [
        '2026_05_23_113552_create_department_employee_table',
        '2026_05_23_120010_create_employee_trainings_table',
        '2026_05_23_120011_add_closure_fields_to_employees_employees_table',
        '2026_05_23_120012_create_employee_salary_raises_table',
        '2026_05_23_120013_add_substitute_to_time_off_leaves_table',
        '2026_05_23_120014_add_request_type_to_purchases_orders_table',
        '2026_05_24_102909_add_project_id_to_accounts_account_moves_table',
        '2026_05_24_120000_add_project_columns_to_analytic_records_table',
        '2026_05_30_114015_add_purchase_request_extension_columns_to_purchases_orders_table',
        '2026_05_31_061156_create_inventory_replenishment_preferences_table',
        '2026_05_31_061157_create_inventory_consumption_logs_table',
        '2026_05_31_120000_extend_projects_tasks_for_operations',
        '2026_05_31_120001_create_projects_task_categories_table',
        '2026_06_29_202907_ensure_purchase_request_extension_columns_on_purchases_orders_table',
        '2026_08_03_065757_add_performance_indexes',
        '2026_08_25_151941_ensure_projects_extension_schema',
    ],

    /*
    |--------------------------------------------------------------------------
    | Data-only Migrations
    |--------------------------------------------------------------------------
    |
    | Guarded migrations that rewrite data. They must never be re-run, because
    | they would overwrite values a customer has since edited.
    |
    */

    'data_only_migrations' => [
        '2026_06_29_202909_localize_department_names_to_arabic',
        '2026_06_30_100000_normalize_asset_category_values',
        '2026_10_29_100000_shift_default_calendar_weekend_to_friday_saturday',
    ],

];
