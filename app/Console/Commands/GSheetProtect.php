<?php

namespace App\Console\Commands;

use App\Support\Sheets\GoogleSheetStore;
use App\Support\Sheets\SheetDatabase;
use Illuminate\Console\Command;

class GSheetProtect extends Command
{
    protected $signature = 'gsheet:protect
        {--lock : Protect every app worksheet so only the service account can edit}
        {--unlock : Remove all protected ranges from the spreadsheet}
        {--status : Show current protected ranges and sharing (default)}
        {--also-editor=* : Extra email(s) allowed to edit when --lock is used}';

    protected $description = 'Lock the Google Sheets database so the tables cannot be edited outside the application';

    public function handle(SheetDatabase $database): int
    {
        if ($database->driver() !== 'google') {
            $this->warn('Running with the local fallback driver (GSHEET_DRIVER not set to google). Nothing to protect.');

            return self::FAILURE;
        }

        $store = $database->store();

        if (!$store instanceof GoogleSheetStore) {
            $this->error('Google driver is active but the store is not a GoogleSheetStore instance.');

            return self::FAILURE;
        }

        if (!config('gsheet.spreadsheet_id')) {
            $this->error('No Google Sheets spreadsheet configured (GSHEET_SPREADSHEET_ID missing).');

            return self::FAILURE;
        }

        $lock = (bool) $this->option('lock');
        $unlock = (bool) $this->option('unlock');

        if ($lock && $unlock) {
            $this->error('Pass either --lock or --unlock, not both.');

            return self::FAILURE;
        }

        if ($lock) {
            $results = $store->protectSpreadsheet(config('gsheet.tables'), $this->option('also-editor'));

            foreach ($results as $title => $result) {
                $this->line('  - ' . $title . ': ' . $result);
            }

            $this->info('Worksheet protections applied.');

            return self::SUCCESS;
        }

        if ($unlock) {
            $removed = $store->unprotectSpreadsheet();

            $this->line('Removed ' . count($removed) . ' protected range(s).');

            foreach ($removed as $description) {
                $this->line('  - ' . $description);
            }

            return self::SUCCESS;
        }

        return $this->renderStatus($store);
    }

    private function renderStatus(GoogleSheetStore $store): int
    {
        $protected = $store->protectedRanges();

        if ($protected === []) {
            $this->line('No protected ranges in the spreadsheet — it is open to direct edits.');
            $this->line('Run `php artisan gsheet:protect --lock` to restrict editing to the app service account.');
        } else {
            $this->line('Protected ranges (' . count($protected) . '):');

            foreach ($protected as $item) {
                $this->line('  - ' . $item['sheet'] . ' [' . $item['range'] . ']'
                    . ($item['warningOnly'] ? ' (warning only)' : ' (restricted)')
                    . ($item['description'] ? ' — ' . $item['description'] : ''));
            }
        }

        return self::SUCCESS;
    }
}