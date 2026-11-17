
<style>
/* =========================================================
   PALUGADA TENUN - LAPORAN ADMIN
   Seluruh aturan dibatasi di dalam .pl-report
   ========================================================= */

.pl-report {
    --pl-green: #163f32;
    --pl-green-dark: #103328;
    --pl-green-soft: #edf4ef;
    --pl-text: #29463b;
    --pl-muted: #718078;
    --pl-border: #e1e8e2;
    --pl-surface: #ffffff;
    --pl-page: #f8faf8;
    --pl-radius: 16px;

    width: min(100%, 1440px);
    margin: 0 auto;
    padding: 28px 30px 54px;
    color: var(--pl-text);
    font-family: 'DM Sans', sans-serif;
    font-size: 16px;
    line-height: 1.6;
    box-sizing: border-box;
}

.pl-report *,
.pl-report *::before,
.pl-report *::after {
    box-sizing: border-box;
}

/* =========================
   HEADER
   ========================= */

.pl-report-head {
    margin-bottom: 28px;
}

.pl-report-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 22px;
    color: var(--pl-green);
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: color .2s ease;
}

.pl-report-back:hover {
    color: #39765b;
}

.pl-report-eyebrow {
    margin: 0 0 7px;
    color: #6c8a78;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
}

.pl-report h1 {
    margin: 0;
    color: var(--pl-green);
    font-family: 'DM Serif Display', serif;
    font-size: clamp(30px, 3vw, 38px);
    font-weight: 400;
    line-height: 1.2;
    letter-spacing: -.4px;
}

.pl-report-subtitle {
    max-width: 850px;
    margin: 11px 0 0;
    color: var(--pl-muted);
    font-size: 15px;
    line-height: 1.75;
}

/* =========================
   TOOLBAR, SEARCH, FILTER
   ========================= */

.pl-report-toolbar {
    position: relative;
    z-index: 5;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

.pl-report-search {
    position: relative;
    display: block;
    flex: 1 1 360px;
    max-width: 560px;
    min-width: 250px;
}

.pl-report-search::before {
    content: '⌕';
    position: absolute;
    top: 50%;
    left: 16px;
    z-index: 1;
    color: #84948b;
    font-size: 23px;
    line-height: 1;
    transform: translateY(-55%);
    pointer-events: none;
}

.pl-report-search input {
    display: block;
    width: 100%;
    height: 48px;
    padding: 0 16px 0 45px;
    border: 1px solid var(--pl-border);
    border-radius: 11px;
    outline: none;
    background: #fff;
    color: var(--pl-text);
    font-family: inherit;
    font-size: 15px;
    transition: border-color .2s ease, box-shadow .2s ease;
}

.pl-report-search input::placeholder {
    color: #9aa69f;
}

.pl-report-search input:focus {
    border-color: #7da18a;
    box-shadow: 0 0 0 3px rgba(54, 111, 78, .10);
}

.pl-report-filter {
    position: relative;
    flex: 0 0 auto;
}

.pl-report-filter summary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-width: 112px;
    height: 48px;
    padding: 0 17px;
    border: 1px solid var(--pl-border);
    border-radius: 11px;
    background: #fff;
    color: var(--pl-green);
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    list-style: none;
    user-select: none;
    transition: background .2s ease, border-color .2s ease;
}

.pl-report-filter summary::-webkit-details-marker {
    display: none;
}

.pl-report-filter summary::after {
    content: '⌄';
    margin-left: 3px;
    color: #718078;
    font-size: 16px;
}

.pl-report-filter[open] summary {
    border-color: #9ab5a2;
    background: var(--pl-green-soft);
}

.pl-report-filter-panel {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    z-index: 20;
    width: min(680px, calc(100vw - 48px));
    padding: 22px;
    border: 1px solid var(--pl-border);
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 16px 42px rgba(24, 55, 42, .14);
}

.pl-report-filter-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 17px;
}

.pl-report-field {
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-width: 0;
}

.pl-report-field label {
    color: #53675c;
    font-size: 13px;
    font-weight: 700;
}

.pl-report-field input,
.pl-report-field select {
    width: 100%;
    min-width: 0;
    height: 44px;
    padding: 0 12px;
    border: 1px solid var(--pl-border);
    border-radius: 9px;
    outline: none;
    background: #fff;
    color: var(--pl-text);
    font-family: inherit;
    font-size: 14px;
}

.pl-report-field input:focus,
.pl-report-field select:focus {
    border-color: #7da18a;
    box-shadow: 0 0 0 3px rgba(54, 111, 78, .09);
}

.pl-report-filter-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 21px;
    padding-top: 17px;
    border-top: 1px solid #edf1ed;
}

.pl-report-toolbar-spacer {
    flex: 1 1 auto;
}

/* =========================
   BUTTONS
   ========================= */

.pl-report-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 46px;
    padding: 0 17px;
    border: 1px solid transparent;
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    line-height: 1;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: transform .18s ease, background .18s ease, box-shadow .18s ease;
}

.pl-report-btn:hover {
    transform: translateY(-1px);
}

.pl-report-btn-primary {
    border-color: var(--pl-green);
    background: var(--pl-green);
    color: #fff;
}

.pl-report-btn-primary:hover {
    background: var(--pl-green-dark);
    box-shadow: 0 5px 14px rgba(22, 63, 50, .16);
}

.pl-report-btn-light {
    border-color: var(--pl-border);
    background: #fff;
    color: #52665a;
}

.pl-report-btn-light:hover {
    background: #f5f8f5;
}

.pl-report-btn-pdf {
    border-color: #ead7d4;
    background: #fff8f7;
    color: #a64d43;
}

.pl-report-btn-pdf:hover {
    background: #fff0ed;
}

.pl-report-btn-excel {
    border-color: #d4e6d8;
    background: #f3faf4;
    color: #287044;
}

.pl-report-btn-excel:hover {
    background: #e8f5eb;
}

/* =========================
   SUMMARY
   ========================= */

.pl-report-summary {
    display: flex;
    align-items: stretch;
    flex-wrap: wrap;
    gap: 10px;
    margin: 0 0 22px;
}

.pl-report-summary span {
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
    min-height: 44px;
    padding: 10px 15px;
    border: 1px solid var(--pl-border);
    border-radius: 10px;
    background: #fff;
    color: #64756b;
    font-size: 13px;
    line-height: 1.5;
}

.pl-report-summary strong {
    color: var(--pl-green);
    font-size: 14px;
    font-weight: 700;
}

/* =========================
   ERROR
   ========================= */

.pl-report-error {
    margin: 0 0 18px;
    padding: 13px 16px;
    border: 1px solid #f0c9c5;
    border-radius: 10px;
    background: #fff5f4;
    color: #a43f36;
    font-size: 14px;
    font-weight: 600;
}

/* =========================
   REPORT CARD
   ========================= */

.pl-report-card {
    overflow: hidden;
    border: 1px solid var(--pl-border);
    border-radius: var(--pl-radius);
    background: var(--pl-surface);
    box-shadow: 0 5px 18px rgba(29, 60, 44, .035);
}

.pl-report-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    min-height: 82px;
    padding: 19px 23px;
    border-bottom: 1px solid #edf1ed;
    background: #fff;
}

.pl-report-card-head h2 {
    margin: 0;
    color: var(--pl-green);
    font-size: 18px;
    font-weight: 700;
    line-height: 1.4;
}

.pl-report-card-head p {
    margin: 5px 0 0;
    color: var(--pl-muted);
    font-size: 13px;
    line-height: 1.6;
}

/* =========================
   TABLE
   ========================= */

.pl-report-table-wrap {
    width: 100%;
    overflow-x: auto;
    overscroll-behavior-x: contain;
}

.pl-report-table {
    width: 100%;
    min-width: 1120px;
    border-collapse: separate;
    border-spacing: 0;
    color: #40584b;
    font-size: 14px;
    text-align: left;
}

.pl-report-table thead th {
    padding: 16px 15px;
    border-bottom: 1px solid #dfe8e0;
    background: #f5f8f5;
    color: #536b5d;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.45;
    white-space: nowrap;
}

.pl-report-table tbody td {
    padding: 16px 15px;
    border-bottom: 1px solid #edf1ed;
    color: #40584b;
    font-size: 14px;
    line-height: 1.6;
    vertical-align: middle;
}

.pl-report-table tbody tr:last-child td {
    border-bottom: 0;
}

.pl-report-table tbody tr:hover td {
    background: #fbfdfb;
}

.pl-report-table td strong {
    color: #254a39;
    font-weight: 700;
}

.pl-report-table td small,
.pl-report-muted {
    color: #849188;
    font-size: 12px;
}

.pl-report-table:has(thead th:nth-child(12)) {
    min-width: 1550px;
}

.pl-report-empty {
    padding: 42px 20px !important;
    color: #87948c !important;
    font-size: 14px !important;
    text-align: center;
}

/* =========================
   STATUS BADGES
   ========================= */

.pl-report-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 27px;
    padding: 5px 11px;
    border: 1px solid transparent;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.2;
    white-space: nowrap;
}

.pl-report-badge.success {
    border-color: #cfe6d5;
    background: #edf8f0;
    color: #287344;
}

.pl-report-badge.pending {
    border-color: #f0e1bd;
    background: #fff8e9;
    color: #96702a;
}

.pl-report-badge.danger {
    border-color: #f0d1ce;
    background: #fff1ef;
    color: #ae4b43;
}

/* =========================
   PAGINATION
   ========================= */

.pl-report-pagination {
    display: flex;
    justify-content: flex-end;
    padding: 18px 22px;
    border-top: 1px solid #edf1ed;
    background: #fff;
}

.pl-report-pagination nav {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
}

.pl-report-pagination nav > div:first-child {
    color: #7b8980;
    font-size: 13px;
}

.pl-report-pagination nav > div:last-child {
    display: flex;
    align-items: center;
    gap: 5px;
}

.pl-report-pagination a,
.pl-report-pagination span[aria-current="page"] span,
.pl-report-pagination nav button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    min-height: 36px;
    padding: 0 10px;
    border: 1px solid var(--pl-border);
    border-radius: 8px;
    background: #fff;
    color: #52675a;
    font-size: 13px;
    text-decoration: none;
}

.pl-report-pagination a:hover {
    border-color: #9db9a5;
    background: #f1f7f2;
}

.pl-report-pagination span[aria-current="page"] span {
    border-color: var(--pl-green);
    background: var(--pl-green);
    color: #fff;
}

.pl-report-pagination svg {
    width: 16px;
    height: 16px;
}

/* =========================
   INDEX - PILIHAN LAPORAN
   ========================= */

.pl-report-index-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
}

.pl-report-index-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    min-height: 250px;
    padding: 27px;
    overflow: hidden;
    border: 1px solid var(--pl-border);
    border-radius: 16px;
    background: #fff;
    color: var(--pl-text);
    text-decoration: none;
    box-shadow: 0 5px 18px rgba(29, 60, 44, .035);
    transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
}

.pl-report-index-card::after {
    content: '';
    position: absolute;
    right: -35px;
    bottom: -55px;
    width: 145px;
    height: 145px;
    border-radius: 50%;
    background: #f3f7f3;
    transition: transform .25s ease;
}

.pl-report-index-card:hover {
    transform: translateY(-4px);
    border-color: #c6d8ca;
    box-shadow: 0 13px 30px rgba(24, 55, 42, .09);
}

.pl-report-index-card:hover::after {
    transform: scale(1.18);
}

.pl-report-index-icon {
    position: relative;
    z-index: 1;
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    margin-bottom: 20px;
    border-radius: 14px;
    background: var(--pl-green-soft);
    color: var(--pl-green);
    font-size: 25px;
}

.pl-report-index-title {
    position: relative;
    z-index: 1;
    margin-bottom: 8px;
    color: var(--pl-green);
    font-size: 19px;
    font-weight: 700;
    line-height: 1.4;
}

.pl-report-index-desc {
    position: relative;
    z-index: 1;
    max-width: 360px;
    margin-bottom: 22px;
    color: var(--pl-muted);
    font-size: 14px;
    line-height: 1.75;
}

.pl-report-index-link {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: auto;
    color: #286044;
    font-size: 14px;
    font-weight: 700;
}

/* =========================
   RESPONSIVE
   ========================= */

@media (max-width: 1100px) {
    .pl-report {
        padding-right: 22px;
        padding-left: 22px;
    }

    .pl-report-index-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .pl-report {
        padding: 22px 16px 38px;
    }

    .pl-report-head {
        margin-bottom: 22px;
    }

    .pl-report-back {
        margin-bottom: 17px;
    }

    .pl-report-subtitle {
        font-size: 14px;
    }

    .pl-report-toolbar {
        align-items: stretch;
    }

    .pl-report-search {
        flex: 1 1 100%;
        max-width: none;
    }

    .pl-report-filter {
        flex: 1 1 auto;
    }

    .pl-report-filter summary {
        width: 100%;
    }

    .pl-report-filter-panel {
        left: 0;
        width: min(560px, calc(100vw - 32px));
    }

    .pl-report-toolbar-spacer {
        display: none;
    }

    .pl-report-btn-pdf,
    .pl-report-btn-excel {
        flex: 1 1 0;
    }

    .pl-report-summary {
        gap: 8px;
    }

    .pl-report-summary span {
        flex: 1 1 calc(50% - 8px);
        align-items: flex-start;
        min-width: 140px;
    }

    .pl-report-card-head {
        padding: 17px 18px;
    }

    .pl-report-index-grid {
        grid-template-columns: 1fr;
    }

    .pl-report-index-card {
        min-height: 220px;
    }
}

@media (max-width: 480px) {
    .pl-report h1 {
        font-size: 29px;
    }

    .pl-report-filter-grid {
        grid-template-columns: 1fr;
        gap: 13px;
    }

    .pl-report-filter-panel {
        padding: 17px;
    }

    .pl-report-filter-actions {
        flex-direction: column-reverse;
    }

    .pl-report-filter-actions .pl-report-btn {
        width: 100%;
    }

    .pl-report-summary span {
        flex-basis: 100%;
    }

    .pl-report-card-head h2 {
        font-size: 16px;
    }

    .pl-report-pagination {
        justify-content: center;
        padding: 15px 10px;
    }

    .pl-report-index-card {
        padding: 22px;
    }
}
</style>
