<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Sheets "database" driver
    |--------------------------------------------------------------------------
    |
    | 'google' uses the Google Sheets API as the primary database (a
    | spreadsheet where every table is a worksheet).
    |
    | 'local' is a development/test fallback that mirrors the same interface
    | against the SQLite database so the application can run without Google
    | credentials configured.
    |
    */

    'driver' => env('GSHEET_DRIVER', 'local'),

    'spreadsheet_id' => env('GSHEET_SPREADSHEET_ID'),

    'application_credentials' => env('GSHEET_APPLICATION_CREDENTIALS'),

    'subject_email' => env('GSHEET_SUBJECT_EMAIL'),

    'client_id' => env('GSHEET_CLIENT_ID'),

    'client_secret' => env('GSHEET_CLIENT_SECRET'),

    'refresh_token' => env('GSHEET_REFRESH_TOKEN'),

    /*
    | Column used as the primary key in every worksheet. Keep in sync with the
    | spreadsheet headers provisioned by `php artisan gsheet:setup`.
    */
    'primary_key' => 'id',

    /*
    | Every table the application persists. Must match the worksheets.
    */
    'tables' => [
        'users' => 'id,name,role,email,email_verified_at,password,remember_token,created_at,updated_at',
        'petty_cash_funds' => 'id,total_amount,current_balance,status,created_at,updated_at',
        'expenses' => 'id,fund_id,payee,category,particular,cost_code,amount,receipt_number,expense_date,status,created_at,updated_at',
        'replenishment_requests' => 'id,fund_id,requested_amount,status,triggered_by,created_at,updated_at',
        'replenishment_reports' => 'id,project_name,location,subject,period_start,period_end,report_date,cash_received,prepared_by,reviewed_by,verified_by,created_at,updated_at',
        'replenishment_items' => 'id,replenishment_report_id,expense_id,expense_date,voucher_no,reference_no,payee,cost_code,particulars,amount,group_key,created_at,updated_at',
    ],

];