<?php

namespace App\Support\Sheets;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

/**
 * Google Sheets driver: every table is a worksheet, the first row holds the
 * column headers and the first column is the primary key (`id`).
 *
 * Values are written with the RAW input option and strings everywhere so the
 * spreadsheet never silently reformats ids, dates or amounts.
 */
class GoogleSheetStore implements SheetStore
{
    private Client $client;

    private Sheets $sheets;

    private ?Drive $drive = null;

    private ?string $spreadsheetId;

    /**
     * @var array<string, array{id: int, title: string}>
     */
    private ?array $sheetsCache = null;

    /**
     * @var array<string, array<string, mixed>|null>
     */
    private array $tablesCache = [];

    /**
     * @var array<string, true>
     */
    private array $dirtyTables = [];

    public function __construct()
    {
        $this->client = $this->buildClient();
        $this->sheets = new Sheets($this->client);
        $this->spreadsheetId = config('gsheet.spreadsheet_id');
    }

    public function all(string $table): array
    {
        if (array_key_exists($table, $this->tablesCache) && $this->tablesCache[$table] !== null) {
            return $this->tablesCache[$table];
        }

        $this->ensureTable($table);
        $range = $this->title($table) . '!A:Z';
        $response = $this->sheets->spreadsheets_values->get($this->spreadsheetId, $range);

        $values = $response->getValues() ?: [];
        $headers = $values[0] ?? [];
        $rows = [];

        foreach (array_slice($values, 1) as $row) {
            $record = [];
            foreach ($headers as $index => $header) {
                $record[$header] = strval($row[$index] ?? '');
            }

            if (!isset($record['id']) || $record['id'] === '') {
                continue;
            }

            $rows[] = $record;
        }

        $this->tablesCache[$table] = $rows;

        return $rows;
    }

    public function find(string $table, string|int $id): ?array
    {
        $id = (string) $id;

        foreach ($this->all($table) as $row) {
            if ((string) $row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }

    public function insert(string $table, array $row): array
    {
        $this->ensureTable($table);

        $id = isset($row['id']) && $row['id'] !== ''
            ? (string) $row['id']
            : (string) $this->nextId($table);

        $row['id'] = $id;
        $row = $this->normalizeRow($table, $row);

        $range = $this->title($table) . '!A' . ($this->nextRowNumber($table)) . ':' . $this->lastColumn($row);
        $valueRange = new ValueRange();
        $valueRange->setValues([$this->rowToValues($table, $row)]);

        $this->sheets->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $valueRange,
            ['valueInputOption' => 'RAW']
        );

        $this->invalidate($table);

        return $this->find($table, $id) ?? $row;
    }

    public function update(string $table, string|int $id, array $row): array
    {
        $this->ensureTable($table);

        $existing = $this->find($table, $id);

        if (!$existing) {
            return $this->insert($table, array_merge($row, ['id' => $id]));
        }

        $merged = array_merge($existing, $row, ['id' => (string) $id]);
        $merged = $this->normalizeRow($table, $merged);
        $rowNumber = $this->rowNumberForId($table, (string) $id);

        if ($rowNumber === null) {
            return $this->insert($table, $merged);
        }

        $headers = $this->headers($table);
        $start = $this->columnLetter(1);
        $end = $this->columnLetter(count($headers));
        $range = $this->title($table) . '!' . $start . $rowNumber . ':' . $end . $rowNumber;

        $valueRange = new ValueRange();
        $valueRange->setValues([$this->rowToValues($table, $merged)]);

        $this->sheets->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $valueRange,
            ['valueInputOption' => 'RAW']
        );

        $this->invalidate($table);

        return $this->find($table, $id) ?? $merged;
    }

    public function delete(string $table, string|int $id): bool
    {
        $rowNumber = $this->rowNumberForId($table, (string) $id);

        if ($rowNumber === null) {
            return false;
        }

        $sheetId = $this->sheetIdForTitle($this->title($table));

        if ($sheetId === null) {
            return false;
        }

        $batchUpdate = new Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => [
                [
                    'deleteDimension' => [
                        'range' => [
                            'sheetId' => $sheetId,
                            'dimension' => 'ROWS',
                            'startIndex' => $rowNumber - 1,
                            'endIndex' => $rowNumber,
                        ],
                    ],
                ],
            ],
        ]);

        $this->sheets->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdate);

        $this->invalidate($table);

        return true;
    }

    public function count(string $table): int
    {
        return count($this->all($table));
    }

    public function nextId(string $table): int
    {
        $max = 0;

        foreach ($this->all($table) as $row) {
            $max = max($max, (int) $row['id']);
        }

        return $max + 1;
    }

    public function provisionSchema(array $tables): void
    {
        foreach ($tables as $table => $headers) {
            $this->ensureTable($table, is_string($headers) ? $headers : implode(',', $headers));
        }

        foreach (array_keys($tables) as $table) {
            $this->ensureHeaders($table);
        }

        $this->sheetsCache = null;
    }

    public function syncRows(string $table, array $rows): void
    {
        $this->ensureTable($table);

        $headers = $this->headers($table);
        $values = [$headers];

        foreach ($rows as $row) {
            $values[] = $this->rowToValues($table, $row);
        }

        $range = $this->title($table) . '!A1:' . $this->columnLetter(count($headers)) . max(count($values), 1);
        $valueRange = new ValueRange();
        $valueRange->setValues($values);

        $this->sheets->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $valueRange,
            ['valueInputOption' => 'RAW']
        );

        $this->invalidate($table);
    }

    public function driver(): string
    {
        return 'google';
    }

    public function setSpreadsheetId(?string $spreadsheetId): void
    {
        $this->spreadsheetId = $spreadsheetId;
        $this->tablesCache = [];
        $this->sheetsCache = null;
    }

    /**
     * Create a brand new spreadsheet via the Drive API and return its id.
     */
    public function createSpreadsheet(string $title): string
    {
        $drive = $this->drive();
        $metadata = new Drive\DriveFile(['name' => $title, 'mimeType' => 'application/vnd.google-apps.spreadsheet']);
        $file = $drive->files->create($metadata, ['fields' => 'id']);

        $this->spreadsheetId = $file->getId();

        return $this->spreadsheetId;
    }

    private function buildClient(): Client
    {
        $client = new Client();
        $client->setApplicationName(config('app.name', 'Petty Cash Monitor'));
        $client->setScopes([
            Sheets::SPREADSHEETS,
            Drive::DRIVE_FILE,
        ]);

        $credentials = config('gsheet.application_credentials');
        $subject = config('gsheet.subject_email');

        if (is_string($credentials) && str_starts_with(trim($credentials), '{')) {
            $client->setAuthConfig(json_decode($credentials, true));
        } elseif (is_string($credentials) && $credentials !== '') {
            $client->setAuthConfig($credentials);
        } else {
            // Web-installer style OAuth credentials provided inline.
            $client->setClientId(config('gsheet.client_id'));
            $client->setClientSecret(config('gsheet.client_secret'));

            if (config('gsheet.refresh_token')) {
                $client->setRefreshToken(config('gsheet.refresh_token'));
                return $client;
            }
        }

        if ($subject) {
            $client->setSubject($subject);
        }

        return $client;
    }

    private function drive(): Drive
    {
        if ($this->drive === null) {
            $this->drive = new Drive($this->client);
        }

        return $this->drive;
    }

    private function headers(string $table): array
    {
        $headers = config('gsheet.tables.' . $table, '');

        if (is_string($headers)) {
            return array_values(array_filter(array_map('trim', explode(',', $headers))));
        }

        if (is_array($headers)) {
            return array_values($headers);
        }

        return ['id'];
    }

    private function title(string $table): string
    {
        return $table;
    }

    private function ensureTable(string $table, ?string $headers = null): void
    {
        if ($headers === null) {
            $headers = implode(',', $this->headers($table));
        }

        $titles = $this->sheetTitles();

        if (isset($titles[$table])) {
            return;
        }

        $requests = [
            [
                'addSheet' => [
                    'properties' => [
                        'title' => $table,
                        'gridProperties' => ['rowCount' => 1000, 'columnCount' => 30, 'frozenRowCount' => 1],
                    ],
                ],
            ],
        ];

        $batchUpdate = new Sheets\BatchUpdateSpreadsheetRequest(['requests' => $requests]);

        $this->sheets->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdate);

        $this->sheetsCache = null;
        $this->ensureHeaders($table);
    }

    private function ensureHeaders(string $table): void
    {
        $headers = $this->headers($table);
        $range = $this->title($table) . '!A1';

        $valueRange = new ValueRange();
        $valueRange->setValues([$headers]);

        $this->sheets->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $valueRange,
            ['valueInputOption' => 'RAW']
        );

        $this->invalidate($table);
    }

    private function normalizeRow(string $table, array $row): array
    {
        $headers = $this->headers($table);
        $normalized = [];

        foreach ($headers as $header) {
            $value = $row[$header] ?? '';

            if ($value === null) {
                $value = '';
            }

            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif (is_float($value) || is_int($value)) {
                $value = (string) $value;
            }

            $normalized[$header] = $value;
        }

        return $normalized;
    }

    private function rowToValues(string $table, array $row): array
    {
        $values = [];

        foreach ($this->headers($table) as $header) {
            $values[] = $row[$header] ?? '';
        }

        return $values;
    }

    private function lastColumn(array $row): string
    {
        return $this->columnLetter(max(count($row), 1));
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter === '' ? 'A' : $letter;
    }

    private function rowNumberForId(string $table, string $id): ?int
    {
        $rowNumber = 2;

        foreach ($this->all($table) as $row) {
            if ((string) $row['id'] === $id) {
                return $rowNumber;
            }

            $rowNumber++;
        }

        return null;
    }

    private function nextRowNumber(string $table): int
    {
        return count($this->all($table)) + 2;
    }

    private function invalidate(string $table): void
    {
        $this->tablesCache[$table] = null;
        $this->dirtyTables[$table] = true;
    }

    /**
     * @return array<string, int> title => sheetId
     */
    private function sheetTitles(): array
    {
        if ($this->sheetsCache !== null) {
            return array_column($this->sheetsCache, 'id', 'title');
        }

        $spreadsheet = $this->sheets->spreadsheets->get($this->spreadsheetId, ['fields' => 'sheets.properties']);

        $this->sheetsCache = [];

        foreach ($spreadsheet->getSheets() as $sheet) {
            $title = $sheet->getProperties()->getTitle();
            $this->sheetsCache[] = ['id' => $sheet->getProperties()->getSheetId(), 'title' => $title];
        }

        return array_column($this->sheetsCache, 'id', 'title');
    }

    private function sheetIdForTitle(string $title): ?int
    {
        $titles = $this->sheetTitles();

        if (!isset($titles[$title])) {
            $this->sheetsCache = null;

            $titles = $this->sheetTitles();
        }

        return $titles[$title] ?? null;
    }
}