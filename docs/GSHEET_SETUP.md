# Google Sheets as the Primary Database

This application can run with **Google Sheets as its primary database**. Every
table is a worksheet inside a single spreadsheet, the first row of each sheet
is the column header row, and the first column holds the primary key (`id`).

| Worksheet              | Contents                                    |
| ---------------------- | ------------------------------------------- |
| `users`                | Application accounts                        |
| `petty_cash_funds`     | Petty cash fund balance / status            |
| `expenses`             | Expense ledger                              |
| `replenishment_requests` | Replenishment request lifecycle           |
| `replenishment_reports`  | Liquidation reports                        |
| `replenishment_items`    | Report line items                          |

Sessions, the queue and the cache still live locally (a spreadsheet is not a
session store) — only the application's data is stored in the spreadsheet.

There are two drivers, controlled by `GSHEET_DRIVER`:

- `google` — reads/writes the spreadsheet via the Google Sheets API.
- `local` (default) — a development fallback that mirrors the same interface
  against the local SQLite database so you can run the app before setting up
  Google credentials.

---

## 1. Create a Google Cloud service account

1. Go to https://console.cloud.google.com/ and create (or pick) a project.
2. Enable the **Google Sheets API** and the **Google Drive API**.
3. **APIs & Services → Credentials → Create Credentials → Service Account**.
4. Name it (e.g. `petty-cash-monitor`) and click *Create and Continue*.
5. On the service account detail page, go to **Keys → Add Key → Create new
   key → JSON** and download the JSON file.
6. Note the service account **email** shown on the detail page (it ends in
   `@<project>.iam.gserviceaccount.com`).

> Alternative: a web-app OAuth client can also be used by setting
> `GSHEET_CLIENT_ID`, `GSHEET_CLIENT_SECRET` and `GSHEET_REFRESH_TOKEN`
> instead of the service account JSON.

## 2. Configure the application

Set these in your `.env` file:

```ini
GSHEET_DRIVER=google
GSHEET_SPREADSHEET_ID=
GSHEET_APPLICATION_CREDENTIALS="C:\path\to\petty-cash-monitor-xxxxxxxx.json"
GSHEET_SUBJECT_EMAIL=
```

`GSHEET_APPLICATION_CREDENTIALS` can be a file path **or** the raw JSON content
itself (paste it between double quotes). `GSHEET_SUBJECT_EMAIL` is optional and
is only needed when impersonating a Google Workspace user.

## 3. Create & provision the spreadsheet

From the project directory run:

```bash
php artisan gsheet:setup --create --seed
```

- `--create` creates a brand new spreadsheet named
  `Petty Cash Monitoring Database`, writes `GSHEET_SPREADSHEET_ID` into your
  `.env`, and creates all six worksheets with their header rows.
- `--seed` inserts the default accounts
  (`admin@admin.com` / `user@user.com`, password `password`) and the initial
  ₱30,000 fund only when the spreadsheet is empty.

If you prefer to reuse a spreadsheet you created manually:

1. Create a new spreadsheet in Google Sheets.
2. Share it with your service account email (as **Editor**).
3. Copy the spreadsheet URL id into `GSHEET_SPREADSHEET_ID`.
4. Run `php artisan gsheet:setup --seed` to provision the worksheets and seed
   the defaults.

> Share permissions matter — if the service account can only view the sheet,
> writes (recording expenses, creating reports, …) will fail with a 403.

## 4. Verify

Start the servers and log in with the seeded admin account:

```bash
php artisan serve
npm run dev
```

Every expense, fund update, replenishment request and liquidation report is
now written straight into the spreadsheet — open it in your browser and the
rows appear in near real time.

## Local driver (no Google account)

Without any configuration the app runs on the `local` driver so development is
uninterrupted:

```bash
php artisan gsheet:setup --seed   # seeds the SQLite mirror
php artisan serve
npm run dev
```

Log in with `admin@admin.com` / `password`.

---

## Notes & limits

- Google Sheets has no transactions or row locks. The application serializes
  balance-critical operations with a local lock file; for multi-worker
  deployments (e.g. several web processes) consider a shared lock or keeping
  writes in a single worker.
- Values are written with the Sheets `RAW` input option and stored as
  text/number literals exactly as submitted — the spreadsheet never
  reformats ids, dates or amounts.
- Migrations still create the local tables for the `local` driver and for
  sessions/cache/queue. They are the mirror, not the source of truth, when
  `GSHEET_DRIVER=google`.