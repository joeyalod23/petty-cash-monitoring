<?php

namespace App\Console\Commands;

use App\Services\PettyCashService;
use App\Support\Sheets\GoogleSheetStore;
use App\Support\Sheets\SheetDatabase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class GSheetSetup extends Command
{
    protected $signature = 'gsheet:setup
        {--create : Create a brand new Google spreadsheet and provision it}
        {--share= : Email to grant Editor access when --create is passed}
        {--seed : Seed the default users and an initial fund}
        {--title=Petty Cash Monitoring Database : Title used when --create is passed}';

    protected $description = 'Provision the Google Sheets database (worksheets + headers) and optionally seed it';

    public function handle(SheetDatabase $database): int
    {
        $driver = $database->driver();

        if ($driver === 'google') {
            $store = $database->store();
            $spreadsheetId = config('gsheet.spreadsheet_id');

            if (!$this->hasCredentials()) {
                $this->error('Google API credentials are not configured.');
                $this->line('Set GSHEET_APPLICATION_CREDENTIALS (or GSHEET_CLIENT_ID / GSHEET_CLIENT_SECRET) in your .env — see docs/GSHEET_SETUP.md.');

                return self::FAILURE;
            }

            if (!$spreadsheetId && $this->option('create') && $store instanceof GoogleSheetStore) {
                $spreadsheetId = $store->createSpreadsheet($this->option('title'));
                $this->info('Created new spreadsheet: ' . $spreadsheetId);
                $this->writeEnv('GSHEET_SPREADSHEET_ID', $spreadsheetId);
            }

            if (!$spreadsheetId) {
                $this->error('No Google Sheets spreadsheet configured.');
                $this->line('Set GSHEET_SPREADSHEET_ID in your .env or re-run with --create to create one.');

                return self::FAILURE;
            }

            $store->setSpreadsheetId($spreadsheetId);
            $store->provisionSchema(config('gsheet.tables'));
            $this->info('Google Sheets database provisioned. Worksheets ready: ' . implode(', ', array_keys(config('gsheet.tables'))));

            $shareEmail = $this->option('share');

            if ($shareEmail && $store instanceof GoogleSheetStore) {
                $store->shareWith($shareEmail);
                $this->info('Shared spreadsheet with ' . $shareEmail . ' (Editor).');
            }
        } else {
            $this->warn('Running with the local fallback driver (GSHEET_DRIVER not set to google).');
            $database->provisionSchema(config('gsheet.tables'));
        }

        if ($this->option('seed')) {
            $this->seedDefaults($database);
        }

        return self::SUCCESS;
    }

    private function seedDefaults(SheetDatabase $database): void
    {
        $now = now()->toDateTimeString();

        if ($database->count('users') === 0) {
            $database->insert('users', [
                'name' => 'Admin',
                'role' => 'admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('password'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $database->insert('users', [
                'name' => 'User',
                'role' => 'user',
                'email' => 'user@user.com',
                'password' => Hash::make('password'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->info('Seeded default users (admin@admin.com / user@user.com, password: "password").');
        } else {
            $this->line('Users already present — skipping user seed.');
        }

        if ($database->count('petty_cash_funds') === 0) {
            $database->insert('petty_cash_funds', [
                'total_amount' => number_format(PettyCashService::fundTarget(), 2, '.', ''),
                'current_balance' => number_format(PettyCashService::fundTarget(), 2, '.', ''),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->info('Seeded initial petty cash fund.');
        } else {
            $this->line('Fund already present — skipping fund seed.');
        }
    }

    private function hasCredentials(): bool
    {
        return (bool) (config('gsheet.application_credentials')
            ?? config('gsheet.client_id')
            ?? config('gsheet.refresh_token'));
    }

    private function writeEnv(string $key, string $value): void
    {
        $path = base_path('.env');

        if (!is_file($path)) {
            return;
        }

        $contents = file_get_contents($path);
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
        $line = $key . '=' . $value;

        if (preg_match($pattern, $contents)) {
            $contents = preg_replace($pattern, $line, $contents);
        } else {
            $contents = rtrim($contents) . PHP_EOL . $line . PHP_EOL;
        }

        file_put_contents($path, $contents);
        $this->info('Updated .env with ' . $key . '.');
    }
}