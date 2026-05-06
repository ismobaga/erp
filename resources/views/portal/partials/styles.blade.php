*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7fb; color: #002045; min-height: 100vh; }

/* ── Topbar ── */
.topbar {
    background: linear-gradient(135deg, #002045 0%, #1a365d 100%);
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.topbar-brand { color: #fff; font-size: 17px; font-weight: 800; letter-spacing: -0.02em; }
.topbar-sub   { color: rgba(255,255,255,0.6); font-size: 11px; margin-top: 2px; }
.lang-btn {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    white-space: nowrap;
}
.lang-btn:hover { background: rgba(255,255,255,0.25); }

/* ── Navigation ── */
.portal-nav {
    background: #fff;
    border-bottom: 1px solid #e5eaf2;
    overflow-x: auto;
}
.portal-nav-inner {
    display: flex;
    align-items: center;
    gap: 2px;
    max-width: 1080px;
    margin: 0 auto;
    padding: 0 16px;
}
.nav-client {
    font-size: 12px;
    font-weight: 800;
    color: #002045;
    white-space: nowrap;
    padding: 0 12px 0 0;
    margin-right: 4px;
    border-right: 1px solid #e5eaf2;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 14px 14px;
    font-size: 13px;
    font-weight: 600;
    color: #57657a;
    text-decoration: none;
    border-bottom: 3px solid transparent;
    white-space: nowrap;
    transition: color 0.15s, border-color 0.15s;
}
.nav-item:hover { color: #002045; }
.nav-item--active { color: #002045; border-bottom-color: #002045; font-weight: 800; }
.nav-icon { font-size: 14px; }

/* ── Container ── */
.container { max-width: 920px; margin: 0 auto; padding: 32px 16px; }

/* ── Cards ── */
.card {
    background: #fff;
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,32,69,0.07);
    border: 1px solid #e5eaf2;
}
.card-title {
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #57657a;
    margin-bottom: 16px;
}

/* ── Flash messages ── */
.flash-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 12px 18px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 600;
}
.flash-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 12px 18px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 600;
}

/* ── Tables ── */
table.portal-table { width: 100%; border-collapse: collapse; }
table.portal-table thead tr { background: #f8faff; }
table.portal-table th {
    padding: 10px 14px;
    text-align: left;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #57657a;
}
table.portal-table td {
    padding: 12px 14px;
    font-size: 14px;
    border-top: 1px solid #f0f4f9;
    vertical-align: middle;
}
table.portal-table tr:hover td { background: #f8faff; }

/* ── Badges ── */
.badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.badge-draft      { background: #f1f5f9; color: #57657a; }
.badge-sent       { background: #eff6ff; color: #1d4ed8; }
.badge-paid       { background: #dcfce7; color: #166534; }
.badge-overdue    { background: #fee2e2; color: #991b1b; }
.badge-partially_paid { background: #e0f2fe; color: #0369a1; }
.badge-cancelled  { background: #f1f5f9; color: #6b7280; }
.badge-accepted   { background: #dcfce7; color: #166534; }
.badge-rejected   { background: #fee2e2; color: #991b1b; }
.badge-expired    { background: #fef3c7; color: #92400e; }
.badge-planned    { background: #f0fdf4; color: #15803d; }
.badge-in_progress { background: #eff6ff; color: #1d4ed8; }
.badge-on_hold    { background: #fef3c7; color: #92400e; }
.badge-completed  { background: #dcfce7; color: #166534; }
.badge-open       { background: #eff6ff; color: #1d4ed8; }
.badge-replied    { background: #dcfce7; color: #166534; }
.badge-closed     { background: #f1f5f9; color: #6b7280; }
.badge-normal     { background: #f1f5f9; color: #57657a; }
.badge-urgent     { background: #fee2e2; color: #991b1b; }

/* ── Buttons ── */
a.btn, button.btn {
    display: inline-block;
    padding: 7px 16px;
    background: #002045;
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    border: none;
    cursor: pointer;
}
a.btn:hover, button.btn:hover { opacity: 0.88; }
a.btn-sm, button.btn-sm { padding: 5px 12px; font-size: 11px; }
a.btn-outline {
    background: transparent;
    color: #002045;
    border: 2px solid #002045;
}
a.btn-danger, button.btn-danger {
    background: #dc2626;
}
a.btn-success, button.btn-success {
    background: #166534;
}
a.btn-white {
    background: #fff;
    color: #002045;
}
a.btn-ghost {
    background: transparent;
    color: rgba(255,255,255,0.8);
    border: 2px solid rgba(255,255,255,0.4);
}

/* ── Empty state ── */
.empty {
    text-align: center;
    padding: 60px 20px;
    color: #57657a;
}
.empty-icon { font-size: 44px; margin-bottom: 12px; }

/* ── Form ── */
.form-group { margin-bottom: 16px; }
.form-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #57657a;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 6px;
}
.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #d1d9e6;
    border-radius: 8px;
    font-size: 14px;
    color: #002045;
    font-family: inherit;
    background: #fff;
    transition: border-color 0.15s;
}
.form-control:focus {
    outline: none;
    border-color: #002045;
}
select.form-control { cursor: pointer; }

/* ── Footer ── */
footer {
    text-align: center;
    padding: 28px 16px;
    font-size: 12px;
    color: #aab0bc;
}

/* ── Meta grid ── */
.meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 600px) {
    .meta-grid { grid-template-columns: 1fr; }
    .portal-nav-inner { gap: 0; }
    .nav-client { display: none; }
}
.meta-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #57657a;
    margin-bottom: 4px;
}
.meta-value { font-size: 15px; font-weight: 700; color: #002045; }

/* ── Timeline ── */
.timeline { list-style: none; }
.timeline li {
    display: flex;
    gap: 14px;
    padding: 10px 0;
    border-bottom: 1px solid #f0f4f9;
    font-size: 14px;
    align-items: flex-start;
}
.timeline li:last-child { border-bottom: none; }
.timeline-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #002045;
    margin-top: 5px;
    flex-shrink: 0;
}
.timeline-date { font-size: 12px; color: #57657a; white-space: nowrap; min-width: 120px; }
.timeline-text { color: #374151; }

/* ── Chat bubble ── */
.messages-list { display: flex; flex-direction: column; gap: 10px; }
.bubble {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 14px;
    line-height: 1.5;
}
.bubble-inbound  { background: #f0f4f9; color: #002045; align-self: flex-start; border-bottom-left-radius: 4px; }
.bubble-outbound { background: #002045; color: #fff;    align-self: flex-end;   border-bottom-right-radius: 4px; }
.bubble-meta { font-size: 10px; opacity: 0.6; margin-top: 4px; }
