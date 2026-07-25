<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Petty Cash Monitor')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f6f9;
            --surface: #ffffff;
            --border: #e5e8ed;
            --text: #1b2559;
            --text-secondary: #68769f;
            --text-muted: #a3aed0;
            --primary: #4318ff;
            --primary-light: #e9e7ff;
            --primary-dark: #3311d9;
            --success: #05cd99;
            --success-bg: #e6f9f1;
            --warning: #ffb547;
            --warning-bg: #fff6e5;
            --danger: #ee5d50;
            --danger-bg: #fdecea;
            --info: #6ce6ff;
            --info-bg: #e8f9ff;
            --radius: 16px;
            --radius-sm: 10px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.04), 0 1px 2px rgba(0,0,0,.06);
            --shadow: 0 4px 12px rgba(0,0,0,.06);
            --shadow-lg: 0 10px 40px rgba(0,0,0,.08);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* ---- SIDEBAR ---- */
        .app-layout { display: flex; min-height: 100vh; }

        .sidebar {
            width: 260px;
            background: var(--text);
            color: #fff;
            padding: 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
        }
        .sidebar-brand {
            padding: 28px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-brand h1 {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.3px;
        }
        .sidebar-brand span {
            font-size: 0.72rem;
            color: var(--text-muted);
            display: block;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .sidebar-nav { padding: 16px 12px; flex: 1; }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 16px;
            color: rgba(255,255,255,.65);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            border-radius: var(--radius-sm);
            transition: all .15s;
            margin-bottom: 2px;
        }
        .sidebar-nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .sidebar-nav a.active { background: var(--primary); color: #fff; }
        .sidebar-nav a svg { width: 18px; height: 18px; flex-shrink: 0; }
        .sidebar-section {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(255,255,255,.25);
            padding: 18px 16px 6px;
            font-weight: 600;
        }

        /* ---- MAIN ---- */
        .main { margin-left: 260px; flex: 1; min-height: 100vh; }

        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 36px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar h2 { font-size: 1.1rem; font-weight: 700; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .topbar-time { font-size: 0.8rem; color: var(--text-secondary); }

        .content { padding: 32px 36px 48px; }

        /* ---- CARDS ---- */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
        }
        .card-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text);
        }
        .card-body { padding: 20px 24px 24px; }

        /* ---- STAT CARDS ---- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px 24px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon svg { width: 22px; height: 22px; }
        .stat-icon.purple { background: var(--primary-light); color: var(--primary); }
        .stat-icon.green { background: var(--success-bg); color: var(--success); }
        .stat-icon.orange { background: var(--warning-bg); color: var(--warning); }
        .stat-icon.red { background: var(--danger-bg); color: var(--danger); }
        .stat-info .label { font-size: 0.76rem; color: var(--text-secondary); font-weight: 500; text-transform: uppercase; letter-spacing: 0.4px; }
        .stat-info .value { font-size: 1.6rem; font-weight: 800; margin-top: 2px; line-height: 1.2; }
        .stat-info .sub { font-size: 0.76rem; color: var(--text-muted); margin-top: 3px; }

        /* ---- TABLES ---- */
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 11px 16px;
            font-size: 0.73rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-secondary);
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 14px 16px;
            font-size: 0.87rem;
            border-bottom: 1px solid var(--border);
            color: var(--text);
            vertical-align: middle;
        }
        tbody tr:hover { background: #f8fafc; }
        tbody tr:last-child td { border-bottom: none; }
        .text-mono { font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace; font-size: 0.82rem; }

        /* ---- BADGES ---- */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.73rem;
            font-weight: 600;
            letter-spacing: 0.2px;
            white-space: nowrap;
        }
        .badge::before {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .badge-active { background: var(--success-bg); color: #0a8c67; }
        .badge-active::before { background: var(--success); }
        .badge-low_balance { background: var(--danger-bg); color: #b82c23; }
        .badge-low_balance::before { background: var(--danger); }
        .badge-pending { background: var(--warning-bg); color: #b87d0a; }
        .badge-pending::before { background: var(--warning); }
        .badge-approved { background: var(--success-bg); color: #0a8c67; }
        .badge-approved::before { background: var(--success); }
        .badge-disbursed { background: var(--info-bg); color: #0a7bb5; }
        .badge-disbursed::before { background: #3db8e8; }
        .badge-rejected { background: var(--danger-bg); color: #b82c23; }
        .badge-rejected::before { background: var(--danger); }
        .badge-replenishment_pending { background: var(--warning-bg); color: #b87d0a; }
        .badge-replenishment_pending::before { background: var(--warning); }

        /* ---- BUTTONS ---- */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all .15s;
            white-space: nowrap;
        }
        .btn svg { width: 15px; height: 15px; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); box-shadow: 0 4px 14px rgba(67,24,255,.3); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-success:hover { background: #04b585; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: #d44940; }
        .btn-secondary { background: var(--bg); color: var(--text-secondary); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .btn-sm { padding: 5px 12px; font-size: 0.76rem; border-radius: 8px; }
        .btn-ghost { background: transparent; color: var(--primary); padding: 6px 10px; }
        .btn-ghost:hover { background: var(--primary-light); }

        /* ---- FORMS ---- */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            font-family: inherit;
            color: var(--text);
            background: var(--surface);
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67,24,255,.12); }
        .form-control::placeholder { color: var(--text-muted); }
        .form-row { display: flex; gap: 16px; }
        .form-row .form-group { flex: 1; }
        .form-inline { display: flex; gap: 10px; align-items: flex-end; }
        .form-inline .form-group { flex: 1; margin-bottom: 0; }
        select.form-control { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2368769f' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; }

        /* ---- ALERTS ---- */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert svg { width: 18px; height: 18px; flex-shrink: 0; }
        .alert-success { background: var(--success-bg); color: #0a8c67; border: 1px solid rgba(5,205,153,.2); }
        .alert-danger { background: var(--danger-bg); color: #b82c23; border: 1px solid rgba(238,93,80,.2); }
        .alert-warning { background: var(--warning-bg); color: #996a0a; border: 1px solid rgba(255,181,71,.2); }

        /* ---- PROGRESS BAR ---- */
        .progress-bar { height: 8px; background: var(--bg); border-radius: 10px; overflow: hidden; margin-top: 8px; }
        .progress-fill { height: 100%; border-radius: 10px; transition: width .4s ease; }
        .progress-fill.green { background: linear-gradient(90deg, #05cd99, #04b585); }
        .progress-fill.yellow { background: linear-gradient(90deg, #ffb547, #f5a623); }
        .progress-fill.red { background: linear-gradient(90deg, #ee5d50, #e03e30); }

        /* ---- BALANCE INDICATOR ---- */
        .balance-display {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }
        .balance-display .amount {
            font-size: 1rem;
            font-weight: 700;
            font-family: 'SF Mono', 'Cascadia Code', monospace;
        }

        /* ---- EMPTY STATE ---- */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-muted);
        }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: .4; }
        .empty-state p { font-size: 0.88rem; }

        /* ---- PAGINATION ---- */
        .pagination { display: flex; gap: 6px; margin-top: 20px; justify-content: center; }
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            transition: all .15s;
        }
        .pagination a { background: var(--surface); border: 1px solid var(--border); color: var(--text-secondary); }
        .pagination a:hover { background: var(--bg); border-color: var(--text-muted); }
        .pagination span.current { background: var(--primary); color: #fff; border: 1px solid var(--primary); }

        /* ---- RESPONSIVE ---- */
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .content { padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
            .form-row { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>
<div class="app-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h1>Petty Cash</h1>
            <span>Fund Monitor</span>
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <div class="sidebar-section">Fund Management</div>
            <a href="{{ route('replenishments') }}" class="{{ request()->routeIs('replenishments') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                Replenishments
            </a>
            <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Replenishment Reports
            </a>
        </nav>
    </aside>

    <div class="main">
        <div class="topbar">
            <h2>@yield('page-title', 'Dashboard')</h2>
            <div class="topbar-right">
                <span style="font-size:0.8rem;color:var(--text-secondary);font-weight:500;">{{ Auth::user()->name }} <span class="badge badge-{{ Auth::user()->isAdmin() ? 'active' : 'pending' }}" style="margin-left:4px;">{{ ucfirst(Auth::user()->role) }}</span></span>
                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Logout</button>
                </form>
                <span class="topbar-time" id="clock"></span>
            </div>
        </div>

        <div class="content">
            @if(session('success'))
                <div class="alert alert-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        document.getElementById('clock').textContent = now.toLocaleDateString('en-US', {
            weekday: 'short', year: 'numeric', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }
    updateClock();
    setInterval(updateClock, 30000);
</script>
@yield('scripts')
</body>
</html>
