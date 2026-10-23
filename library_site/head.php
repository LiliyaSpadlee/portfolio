<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --ink:    #1a1a2e;
    --paper:  #f4f1eb;
    --warm:   #e8e2d6;
    --gold:   #b8892e;
    --gold2:  #d4a843;
    --red:    #8b2635;
    --teal:   #2a6b6b;
    --muted:  #7a7060;
    --card:   #ffffff;
    --border: #d8d0c0;
    --radius: 8px;
    --shadow: 0 1px 6px rgba(0,0,0,.07);
    --green:  #1e6b3a;
}
body { font-family: 'Inter', sans-serif; background: var(--paper); color: var(--ink); min-height: 100vh; font-size: 14px; }

/* NAV */
nav { background: var(--ink); padding: 0 28px; display: flex; align-items: center; position: sticky; top: 0; z-index: 100; border-bottom: 2px solid var(--gold); flex-wrap: wrap; }
.nav-logo { color: var(--gold2); font-size: 17px; font-weight: 600; padding: 14px 18px 14px 0; border-right: 1px solid rgba(255,255,255,.12); margin-right: 4px; white-space: nowrap; }
nav a { color: rgba(255,255,255,.68); text-decoration: none; padding: 16px 12px; font-size: 13px; font-weight: 500; transition: color .15s; white-space: nowrap; position: relative; }
nav a:hover { color: #fff; }
nav a.active { color: #fff; }
nav a.active::after { content: ''; position: absolute; bottom: 0; left: 12px; right: 12px; height: 2px; background: var(--gold2); border-radius: 2px 2px 0 0; }
.nav-actions { margin-left: auto; display: flex; gap: 6px; padding: 8px 0; }
.nav-btn-add { background: rgba(180,140,50,.18) !important; color: var(--gold2) !important; border: 1px solid rgba(180,140,50,.3); border-radius: 5px; padding: 6px 12px !important; font-size: 12px !important; transition: background .15s !important; }
.nav-btn-add:hover { background: var(--gold) !important; color: white !important; }
.nav-btn-add.active::after { display: none; }
.nav-btn-add.active { background: var(--gold) !important; color: white !important; }

/* BREADCRUMB */
.breadcrumb { background: var(--warm); border-bottom: 1px solid var(--border); padding: 7px 36px; font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 5px; }
.breadcrumb a { color: var(--muted); text-decoration: none; }
.breadcrumb a:hover { color: var(--ink); }
.bc-sep { color: var(--border); }
.bc-current { color: var(--ink); font-weight: 500; }

/* LAYOUT */
.container { max-width: 1100px; margin: 0 auto; padding: 0 36px 56px; }
.section-title { font-size: 20px; font-weight: 600; color: var(--ink); margin: 36px 0 18px; padding-bottom: 10px; border-bottom: 2px solid var(--gold); display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

/* TABLE */
.table-wrap { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
thead th { background: var(--ink); color: var(--gold2); padding: 9px 13px; text-align: left; font-weight: 500; font-size: 12px; letter-spacing: .03em; }
tbody tr { border-bottom: 1px solid var(--warm); }
tbody td { padding: 9px 13px; }
/* Только кликабельные строки получают hover */
tbody tr.clickable { cursor: pointer; transition: background .12s; }
tbody tr.clickable:hover { background: var(--warm); }

/* FORMS */
.search-bar { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px; margin-bottom: 18px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; box-shadow: var(--shadow); }
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; font-weight: 500; }
.form-group input, .form-group select { border: 1px solid var(--border); border-radius: 5px; padding: 7px 11px; font-family: 'Inter', sans-serif; font-size: 13px; background: white; color: var(--ink); min-width: 150px; }
.form-group input:focus, .form-group select:focus { outline: 2px solid var(--gold); border-color: var(--gold); }

/* BUTTONS */
.btn { background: var(--ink); color: var(--gold2); border: none; border-radius: 5px; padding: 8px 20px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 500; cursor: pointer; transition: background .15s; text-decoration: none; display: inline-block; }
.btn:hover { background: #2e2e4a; color: var(--gold2); }
.btn-red { background: var(--red); color: white; }
.btn-red:hover { background: #9e2f3e; color: white; }
.btn-teal { background: var(--teal); color: white; }
.btn-teal:hover { background: #1e5252; color: white; }
.btn-green { background: var(--green); color: white; }
.btn-green:hover { background: #155028; color: white; }
.btn-sm { padding: 5px 13px; font-size: 12px; }
.btn-ghost { background: transparent; border: 1px solid var(--border); color: var(--ink); }
.btn-ghost:hover { background: var(--warm); color: var(--ink); }

/* BADGES */
.badge { display: inline-block; padding: 2px 9px; border-radius: 12px; font-size: 11px; font-weight: 500; }
.badge-new  { background: #d4edda; color: #155724; }
.badge-ok   { background: #d1ecf1; color: #0c5460; }
.badge-arch { background: #f8d7da; color: #721c24; }

/* TABS */
.tabs { display: flex; gap: 3px; margin-bottom: 18px; flex-wrap: wrap; border-bottom: 2px solid var(--border); }
.tab { padding: 8px 18px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; color: var(--muted); border-radius: 6px 6px 0 0; transition: .12s; margin-bottom: -2px; border: 1px solid transparent; border-bottom: none; }
.tab.active { background: var(--card); color: var(--ink); border-color: var(--border); border-bottom-color: var(--card); }
.tab:hover:not(.active) { color: var(--ink); }

/* STATS */
.stat-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; text-align: center; box-shadow: var(--shadow); }
.stat-num { font-size: 32px; font-weight: 600; color: var(--gold); line-height: 1; }
.stat-label { font-size: 11px; color: var(--muted); margin-top: 5px; text-transform: uppercase; letter-spacing: .06em; }

/* MISC */
.result-count { font-size: 12px; color: var(--muted); margin-bottom: 8px; }
.empty { text-align: center; padding: 36px; color: var(--muted); font-size: 14px; }
.msg { padding: 12px 16px; border-radius: 7px; margin-bottom: 18px; font-size: 13px; }
.msg.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.msg.error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
footer { background: var(--ink); color: rgba(255,255,255,.35); text-align: center; padding: 18px; font-size: 12px; border-top: 2px solid var(--gold); margin-top: 56px; }

/* FORM CARD */
.form-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px; max-width: 680px; box-shadow: var(--shadow); }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
.form-full { margin-bottom: 14px; }
.form-group-v label { display: block; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing:.05em; margin-bottom: 4px; font-weight: 500; }
.form-group-v input, .form-group-v select, .form-group-v textarea { width: 100%; border: 1px solid var(--border); border-radius: 5px; padding: 8px 11px; font-family: 'Inter', sans-serif; font-size: 13px; background: white; color: var(--ink); }
.form-group-v input:focus, .form-group-v select:focus, .form-group-v textarea:focus { outline: 2px solid var(--gold); border-color: var(--gold); }

/* LIVE SEARCH */
.live-search-wrap { position: relative; }
.live-results { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--border); border-top: none; border-radius: 0 0 7px 7px; box-shadow: 0 6px 20px rgba(0,0,0,.1); z-index: 200; max-height: 300px; overflow-y: auto; display: none; }
.live-results.open { display: block; }
.live-item { padding: 9px 13px; font-size: 13px; cursor: pointer; border-bottom: 1px solid var(--warm); display: flex; gap: 8px; align-items: center; }
.live-item:hover { background: var(--warm); }
.live-item-title { font-weight: 500; }
.live-item-meta { font-size: 11px; color: var(--muted); }

/* MODAL */
.modal-bg { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:500; align-items:center; justify-content:center; }
.modal-bg.open { display:flex; }
.modal { background:white; border-radius: var(--radius); padding:28px; max-width:540px; width:90%; box-shadow:0 8px 32px rgba(0,0,0,.2); }
.modal h3 { font-size:16px; font-weight:600; margin-bottom:18px; }
.modal-actions { display:flex; gap:10px; margin-top:20px; }

@media (max-width: 768px) {
    .container { padding: 0 14px 40px; }
    nav { padding: 0 12px; }
    .nav-actions { gap: 3px; }
    .nav-btn-add { padding: 4px 8px !important; font-size: 11px !important; }
    .form-row { grid-template-columns: 1fr; }
    .breadcrumb { padding: 6px 14px; }
}
</style>
