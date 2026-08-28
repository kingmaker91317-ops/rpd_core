<html lang="vi"><head>
    <meta charset="UTF-8">
    <title>Fish ID Data</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --bg-page: #020617;
            --bg-card: #020617;
            --border-subtle: #e5e7eb;
            --text-main: #0f172a;
            --text-muted: #6b7280;
            --accent: #0ea5e9;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 0;
            padding: 0;
            background: radial-gradient(circle at top left, #e0f2fe, #f9fafb 40%, #eff6ff);
            color: var(--text-main);
        }

        .page {
            min-height: 100vh;
            padding: 8px 0 16px;
        }

        .shell {
            width: 100%;
            max-width: 100%;
            background: white;
            border-radius: 0;
            border: none;
            box-shadow: none;
            padding: 12px 8px 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .header {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            gap: 12px;
            flex-wrap: wrap;
        }

        h1 {
            font-size: 1.4rem;
            margin: 0 0 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        h1 span.badge-main {
            font-size: 0.70rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 3px 7px;
            border-radius: 999px;
            background: rgba(14, 165, 233, 0.06);
            color: #0369a1;
            border: 1px solid rgba(125, 211, 252, 0.7);
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.86rem;
        }

        .layout-main {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 16px;
            align-items: start;
        }

        .card {
            background: #f9fafb;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            padding: 14px 12px 14px;
            overflow: hidden;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .status {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .status strong {
            color: #111827;
        }

        .search-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .search-input {
            min-width: 180px;
            padding: 5px 8px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            font-size: 0.8rem;
            outline: none;
        }

        .search-input:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 1px rgba(14, 165, 233, 0.4);
        }

        .copy-btn {
            padding: 5px 10px;
            border-radius: 999px;
            border: 1px solid #0ea5e9;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 0.78rem;
            cursor: pointer;
            white-space: nowrap;
        }

        .copy-btn:hover {
            background: #bae6fd;
        }

        .version-select {
            padding: 5px 8px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            font-size: 0.8rem;
            background: #ffffff;
            outline: none;
        }

        .version-select:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 1px rgba(14, 165, 233, 0.4);
        }

        .legend {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 6px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.74rem;
            color: #4b5563;
        }

        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 6px;
            border: 1px solid rgba(15, 23, 42, 0.12);
        }

        .legend-color.common {
            background: #f9fafb;
        }
        .legend-color.uncommon {
            background: #dcfce7;
        }
        .legend-color.rare {
            background: #dbeafe;
        }
        .legend-color.legendary {
            background: #ede9fe;
        }
        .legend-color.special {
            background: linear-gradient(135deg, #f9a8d4, #a5b4fc, #6ee7b7);
        }

        .table-wrap {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: white;
            overflow-x: auto;
            overflow-y: hidden;
        }

        table {
            width: 100%;
            min-width: 500px;
            border-collapse: collapse;
            font-size: 0.86rem;
        }

        thead {
            background: #f1f5f9;
        }

        th, td {
            padding: 7px 8px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .cell-name {
            white-space: normal;
        }

        .name-cell {
            white-space: nowrap;      /* Không cho phép xuống dòng */
            overflow: hidden;         /* Ẩn phần tràn ra ngoài ô */
            text-overflow: ellipsis;  /* Hiển thị "..." nếu quá dài */
        }

        .g-common {
            background: #f9fafb;
            border-color: #e5e7eb;
            color: #374151;
        }

        .g-uncommon {
            background: #dcfce7;
            border-color: #4ade80;
            color: #166534;
        }

        .g-rare {
            background: #dbeafe;
            border-color: #60a5fa;
            color: #1d4ed8;
        }

        .g-legendary {
            background: #ede9fe;
            border-color: #a855f7;
            color: #6b21a8;
        }

        .g-special {
            background: linear-gradient(135deg, #f9a8d4, #a5b4fc, #6ee7b7);
            border-color: rgba(244, 114, 182, 0.6);
            color: #111827;
        }

        .error {
            color: #b91c1c;
            background: #fee2e2;
            border-radius: 8px;
            border: 1px solid #fecaca;
            padding: 7px 8px;
            margin-top: 6px;
            display: none;
            font-size: 0.8rem;
        }

        @media (max-width: 640px) {
            h1 {
                font-size: 1.25rem;
            }

            .layout-main {
                gap: 12px;
            }

            .table-wrap {
                border-radius: 10px;
            }

            table {
                font-size: 0.8rem;
            }

            th,
            td {
                padding: 6px 6px;
            }

            .legend {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
<style>.--savior-overlay-transform-reset {
  transform: none !important;
}
.--savior-overlay-z-index-top {
  z-index: 2147483643 !important;
}
.--savior-overlay-position-relative {
  position: relative;
}
.--savior-overlay-position-static {
  position: static !important;
}
.--savior-overlay-overflow-hidden {
  overflow: hidden !important;
}
.--savior-overlay-overflow-x-visible {
  overflow-x: visible !important;
}
.--savior-overlay-overflow-y-visible {
  overflow-y: visible !important;
}
.--savior-overlay-z-index-reset {
  z-index: auto !important;
}
.--savior-overlay-display-none {
  display: none !important;
}
.--savior-overlay-clearfix {
  clear: both;
}
.--savior-overlay-reset-filter {
  filter: none !important;
  backdrop-filter: none !important;
}
.--savior-tooltip-host {
  z-index: 9999;
  position: absolute;
  top: 0;
}
/*Override css styles for Twitch.tv*/
main.--savior-overlay-z-index-reset {
  z-index: auto !important;
}
.modal__backdrop.--savior-overlay-z-index-reset {
  position: static !important;
}
main.--savior-overlay-z-index-top {
  z-index: auto !important;
}
main.--savior-overlay-z-index-top .channel-root__player-container + div,
main.--savior-overlay-z-index-top .video-player-hosting-ui__container + div {
  opacity: 0.1;
}
.--savior-backdrop {
  position: fixed !important;
  z-index: 2147483642 !important;
  top: 0;
  left: 0;
  height: 100vh;
  width: 100vw !important;
  background-color: rgba(0,0,0,0.9);
}
.--savior-overlay-twitter-video-player {
  position: fixed;
  width: 80%;
  height: 80%;
  top: 10%;
  left: 10%;
}
.--savior-overlay-z-index-reset [class*="DivSideNavContainer"],
.--savior-overlay-z-index-reset [class*="DivHeaderContainer"],
.--savior-overlay-z-index-reset [class*="DivBottomContainer"],
.--savior-overlay-z-index-reset [class*="DivCategoryListWrapper"],
.--savior-overlay-z-index-reset [data-testid="sidebarColumn"],
.--savior-overlay-z-index-reset header[role="banner"],
.--savior-overlay-z-index-reset [data-testid="cellInnerDiv"]:not(.--savior-overlay-z-index-reset),
.--savior-overlay-z-index-reset [aria-label="Home timeline"]>div:first-child,
.--savior-overlay-z-index-reset [aria-label="Home timeline"]>div:nth-child(3) {
  z-index: -1 !important;
}
.--savior-overlay-z-index-reset [data-testid="cellInnerDiv"] .--savior-backdrop+div {
  z-index: 2147483643 !important;
}
.--savior-overlay-z-index-reset [data-testid="primaryColumn"]>[aria-label="Home timeline"] {
  z-index: 0 !important;
}
.--savior-overlay-z-index-reset#mtLayer,
.--savior-overlay-z-index-reset.media-layer {
  z-index: 3000 !important;
}
.--savior-overlay-position-relative [class*="SecBar_secBar_"],
.--savior-overlay-position-relative .woo-box-flex [class*="Frame_top_"] {
  z-index: 0 !important;
}
.--savior-overlay-position-relative .vue-recycle-scroller__item-view:not(.--savior-overlay-z-index-reset),
.--savior-overlay-position-relative .woo-panel-main[class*="BackTop_main_"],
.--savior-overlay-position-relative [class*="Main_side_"] {
  z-index: -1 !important;
}
/* Fix conflict css with zingmp3 */
.zm-video-modal.--savior-overlay-z-index-reset {
  position: absolute;
}
/* Dirty hack for xvideos99 */
#page #main.--savior-overlay-z-index-reset {
  z-index: auto !important;
}
/* Overlay for ok.ru */
#vp_w.--savior-overlay-z-index-reset.media-layer.media-layer__video {
  overflow-y: hidden;
  z-index: 2147483643 !important;
}
/* Fix missing controller for tv.naver.com */
.--savior-overlay-z-index-top.rmc_controller,
.--savior-overlay-z-index-top.rmc_setting_intro,
.--savior-overlay-z-index-top.rmc_highlight,
.--savior-overlay-z-index-top.rmc_control_settings {
  z-index: 2147483644 !important;
}
/* Dirty hack for douyi.com */
.swiper-wrapper.--savior-overlay-z-index-reset .swiper-slide:not(.swiper-slide-active),
.swiper-wrapper.--savior-overlay-transform-reset .swiper-slide:not(.swiper-slide-active) {
  display: none;
}
.videoWrap + div > div {
  pointer-events: unset;
}
/* Dirty hack for fpt.ai */
.mfp-wrap.--savior-overlay-z-index-top {
  position: relative;
}
.mfp-wrap.--savior-overlay-z-index-top .mfp-close {
  display: none;
}
.mfp-wrap.--savior-overlay-z-index-top .mfp-content {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
section.--savior-overlay-z-index-reset>main[role="main"].--savior-overlay-z-index-reset + nav {
  z-index: -1 !important;
}
section.--savior-overlay-z-index-reset>main[role="main"].--savior-overlay-z-index-reset section.--savior-overlay-z-index-reset div.--savior-overlay-z-index-reset ~ div {
  position: relative;
}
.watching-movie #video-player.--savior-overlay-z-index-top {
  z-index: 2147483644 !important;
}
div[class^="tiktok"].--savior-overlay-z-index-reset {
  z-index: 2147483644 !important;
}
.--savior-lightoff-fix section:not(:has([class*="--savior-overlay-"])),
.--savior-lightoff-fix section.section_video ~ section {
  z-index: -1;
  position: relative;
}
.--savior-lightoff-fix header,
.--savior-lightoff-fix footer,
.--savior-lightoff-fix .top-header,
.--savior-lightoff-fix .swiper-container,
.--savior-lightoff-fix #to_top,
.--savior-lightoff-fix #button-adblock {
  z-index: -1 !important;
}
@-moz-keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
@-webkit-keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
@-o-keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style></head>
<body>
    <div class="page">
        <div class="shell">
            <div class="header">
                <div class="title-wrap">
                    <h1>
                        Fish ID Data
                        <span class="badge-main">Public Dataset</span>
                    </h1>
                    <div class="subtitle">
                        Bảng tra cứu cấp độ cá theo ID và độ hiếm, đang dùng dữ liệu nguồn từ game thực tế.
                    </div>
                </div>
            </div>

            <div class="layout-main">
                <div class="card">
                    <div class="card-header">
                        <div class="status">
                            <strong id="totalItems">0</strong> cá trong bộ sưu tập
                            <span id="lastUpdated"></span>
                        </div>
                        <div class="search-wrap">
                            <select id="versionSelect" class="version-select">
                                <option value="local" selected>Phiên bản hiện tại: 2.22.0</option>
                            </select>
                            <span>Tìm kiếm:</span>
                            <input id="fishSearch" class="search-input" type="text" placeholder="Theo ID hoặc tên cá...">
                            <button id="copySelected" class="copy-btn" type="button">Sao chép ID ?? chọn</button>
                        </div>
                    </div>

                    <div class="legend">
                        <div class="legend-item">
                            <span class="legend-color common"></span>
                            <span>Nền Trắng – Common: Cá thường, dễ câu, giá thấp.</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color uncommon"></span>
                            <span>Nền Xanh Dương – Rare: Cá khó câu, xuất hiện theo vị trí/khung giờ.</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color rare"></span>
                            <span>Nền Xanh Dương – Rare: Cá khó câu, xuất hiện theo vị trí/khung giờ.</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color legendary"></span>
                            <span>Nền Hồng/Cầu vồng – Special: Bộ sưu tập cá đặc biệt, khó câu nhất.</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color special"></span>
                            <span>Nền Hồng/Cầu vồng – Special: Bộ sưu tập cá đặc biệt, khó câu nhất.</span>
                        </div>
                    </div>

                    <div id="errorBox" class="error" style="display: none;"></div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr id="fishHeaderRow">
                                    <th style="width: 40px;"><input type="checkbox" id="checkAllRows"></th>
                                    <th style="width: 80px;">ID</th>
                                </tr>
                            </thead>
                            <tbody id="fishTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
<script>
        function getGradeClass(grade) {
            if (grade === 1) return 'g-common';
            if (grade === 2) return 'g-uncommon';
            if (grade === 3) return 'g-rare';
            if (grade === 4) return 'g-legendary';
            if (grade >= 5) return 'g-special';
            return 'g-common';
        }

        function renderHeader(maxNames) {
            const headerRow = document.getElementById('fishHeaderRow');
            if (!headerRow) {
                return;
            }

            let headerHtml = '<th style="width: 40px;"><input type="checkbox" id="checkAllRows"></th><th style="width: 80px;">ID</th>';
            for (let i = 0; i < (maxNames || 0); i++) {
                headerHtml += `<th>Tên ${i + 1}</th>`;
            }
            headerRow.innerHTML = headerHtml;
        }

        function getGradeFromCell(cell) {
            if (!cell) return 1;
            if (cell.classList.contains('g-special')) return 5;
            if (cell.classList.contains('g-legendary')) return 4;
            if (cell.classList.contains('g-rare')) return 3;
            if (cell.classList.contains('g-uncommon')) return 2;
            return 1;
        }

        function parseRowsFromDom() {
            const tbody = document.getElementById('fishTableBody');
            if (!tbody) {
                return [];
            }

            const rows = [];
            tbody.querySelectorAll('tr').forEach((tr) => {
                const cells = tr.querySelectorAll('td');
                if (cells.length < 2) return;
                const id = parseInt(cells[1].textContent.trim(), 10);
                if (!Number.isFinite(id)) return;

                const fishes = [];
                for (let i = 2; i < cells.length; i++) {
                    const name = cells[i].textContent.trim();
                    if (!name) continue;
                    fishes.push({
                        name,
                        grade: getGradeFromCell(cells[i]),
                    });
                }

                rows.push({ id, fishes });
            });

            return rows;
        }

        function getApiBasePath() {
            const path = window.location.pathname || '/';
            const parts = path.split('/');
            parts.pop();
            const base = parts.join('/') || '';
            return base.endsWith('/') ? base.slice(0, -1) : base;
        }

        function attachStaticHandlers() {
            const searchInput = document.getElementById('fishSearch');
            if (searchInput && searchInput.dataset.bound !== '1') {
                searchInput.dataset.bound = '1';
                searchInput.addEventListener('input', () => {
                    const term = searchInput.value.trim().toLowerCase();
                    const config = window._fishConfig || {};
                    const rows = config.allRows || [];

                    if (!term) {
                        renderTable(rows, config.maxNames);
                        return;
                    }

                    const filtered = rows.filter((row) => {
                        const idStr = String(row.id ?? '').toLowerCase();
                        if (idStr.includes(term)) {
                            return true;
                        }

                        const fishes = Array.isArray(row.fishes) ? row.fishes : [];
                        return fishes.some((fish) => (fish.name ?? '').toString().toLowerCase().includes(term));
                    });

                    renderTable(filtered, config.maxNames);
                });
            }

            const versionSelect = document.getElementById('versionSelect');
            if (versionSelect && versionSelect.dataset.bound !== '1') {
                versionSelect.dataset.bound = '1';
                versionSelect.addEventListener('change', async () => {
                    const errorBox = document.getElementById('errorBox');
                    const lastUpdated = document.getElementById('lastUpdated');
                    const apiBase = getApiBasePath();

                    try {
                        const newVersion = versionSelect.value;
                        const res2 = await fetch(apiBase + '/api/fish?version=' + encodeURIComponent(newVersion));
                        if (!res2.ok) {
                            throw new Error('HTTP ' + res2.status + ' khi tải dữ liệu version ' + newVersion);
                        }
                        const data2 = await res2.json();
                        if (!Array.isArray(data2)) {
                            throw new Error('Dữ liệu trả về không phải mảng');
                        }

                        const grouped2 = new Map();
                        for (const item of data2) {
                            const rawId = Number.isFinite(item.id) ? item.id : parseInt(item.id ?? '0', 10);
                            const id = rawId || 0;
                            if (!grouped2.has(id)) {
                                grouped2.set(id, []);
                            }
                            grouped2.get(id).push(item);
                        }

                        let maxNames2 = 0;
                        grouped2.forEach((items) => {
                            if (Array.isArray(items) && items.length > maxNames2) {
                                maxNames2 = items.length;
                            }
                        });

                        renderHeader(maxNames2);

                        const sortedIds2 = Array.from(grouped2.keys()).sort((a, b) => a - b);
                        const allRows2 = sortedIds2.map((id) => ({
                            id,
                            fishes: grouped2.get(id) || [],
                        }));

                        const lastText2 = sortedIds2.length ? ' · Phiên bản hiện tại: ' + newVersion : '';
                        if (lastUpdated) {
                            lastUpdated.textContent = lastText2;
                        }

                        window._fishConfig = {
                            maxNames: maxNames2,
                            allRows: allRows2,
                            baseCount: grouped2.size,
                            lastText: lastText2,
                            selected: new Set(),
                            currentVersion: newVersion,
                        };

                        const term2 = (document.getElementById('fishSearch')?.value || '').trim().toLowerCase();
                        let rowsToRender2 = allRows2;
                        if (term2) {
                            rowsToRender2 = allRows2.filter((row) => {
                                const idStr = String(row.id ?? '').toLowerCase();
                                if (idStr.includes(term2)) {
                                    return true;
                                }
                                const fishes = Array.isArray(row.fishes) ? row.fishes : [];
                                return fishes.some((fish) => (fish.name ?? '').toString().toLowerCase().includes(term2));
                            });
                        }

                        renderTable(rowsToRender2, maxNames2);

                        const headerCheckAll2 = document.getElementById('checkAllRows');
                        if (headerCheckAll2) {
                            headerCheckAll2.dataset.bound = '0';
                        }
                        attachStaticHandlers();
                    } catch (err) {
                        console.error(err);
                        if (errorBox) {
                            errorBox.textContent = 'Không thể tải dữ liệu cho version mới: ' + (err && err.message ? err.message : '');
                            errorBox.style.display = 'block';
                        }
                    }
                });
            }

            const copyBtn = document.getElementById('copySelected');
            if (copyBtn && copyBtn.dataset.bound !== '1') {
                copyBtn.dataset.bound = '1';
                copyBtn.addEventListener('click', async () => {
                    const config = window._fishConfig || {};
                    const selected = config.selected instanceof Set ? Array.from(config.selected) : [];

                    if (!selected.length) {
                        alert('Vui lòng chọn ít nhất 1 ID để sao chép.');
                        return;
                    }

                    const sortedIds = selected.slice().sort((a, b) => a - b);
                    const text = sortedIds.join(',');

                    try {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(text);
                        } else {
                            const temp = document.createElement('textarea');
                            temp.value = text;
                            document.body.appendChild(temp);
                            temp.select();
                            document.execCommand('copy');
                            document.body.removeChild(temp);
                        }
                        alert('Đã sao chép: ' + text);
                    } catch (err) {
                        console.error(err);
                        alert('Không thể sao chép vào clipboard, vui lòng thử lại.');
                    }
                });
            }

            const headerCheckAll = document.getElementById('checkAllRows');
            if (headerCheckAll && headerCheckAll.dataset.bound !== '1') {
                headerCheckAll.dataset.bound = '1';
                headerCheckAll.addEventListener('change', () => {
                    const config = window._fishConfig || {};
                    if (!(config.selected instanceof Set)) {
                        config.selected = new Set();
                    }

                    const rows = config.allRows || [];
                    if (headerCheckAll.checked) {
                        for (const row of rows) {
                            if (row && Number.isFinite(row.id)) {
                                config.selected.add(row.id);
                            }
                        }
                    } else {
                        config.selected.clear();
                    }

                    window._fishConfig = config;
                    const term = (document.getElementById('fishSearch')?.value || '').trim().toLowerCase();
                    const baseRows = config.allRows || [];
                    let rowsToRender = baseRows;
                    if (term) {
                        rowsToRender = baseRows.filter((row) => {
                            const idStr = String(row.id ?? '').toLowerCase();
                            if (idStr.includes(term)) {
                                return true;
                            }
                            const fishes = Array.isArray(row.fishes) ? row.fishes : [];
                            return fishes.some((fish) => (fish.name ?? '').toString().toLowerCase().includes(term));
                        });
                    }
                    renderTable(rowsToRender, config.maxNames);
                });
            }
        }

        function renderTable(rows, maxNames) {
            const tbody = document.getElementById('fishTableBody');
            const totalItems = document.getElementById('totalItems');
            const config = window._fishConfig || {};

            if (!tbody) {
                return;
            }

            tbody.innerHTML = '';
            totalItems.textContent = (rows || []).length.toString();

            const selected = config.selected instanceof Set ? config.selected : new Set();

            for (const row of rows || []) {
                const id = row.id;
                const fishes = Array.isArray(row.fishes) ? row.fishes.slice() : [];
                const tr = document.createElement('tr');

                fishes.sort((a, b) => {
                    const nameA = (a.name ?? '').toString().toLowerCase();
                    const nameB = (b.name ?? '').toString().toLowerCase();
                    return nameA.localeCompare(nameB);
                });

                let cellsHtml = '';
                for (let i = 0; i < (maxNames || 0); i++) {
                    const fish = fishes[i];
                    if (fish) {
                        const gradeValue = Number.isFinite(fish.grade) ? fish.grade : parseInt(fish.grade ?? '0', 10);
                        const gradeClass = getGradeClass(gradeValue || 0);
                        const safeName = (fish.name ?? '').toString();
                        cellsHtml += `<td class="name-cell ${gradeClass}">${safeName}</td>`;
                    } else {
                        cellsHtml += '<td class="name-cell"></td>';
                    }
                }

                const isChecked = selected.has(id);

                tr.innerHTML = `
                    <td>
                        <input type="checkbox" class="row-check" data-id="${id}" ${isChecked ? 'checked' : ''}>
                    </td>
                    <td>${id}</td>
                    ${cellsHtml}
                `;

                tbody.appendChild(tr);
            }

            tbody.querySelectorAll('.row-check').forEach((checkbox) => {
                checkbox.addEventListener('change', (event) => {
                    const target = event.target;
                    const rawId = target.getAttribute('data-id');
                    if (!rawId) return;
                    const id = parseInt(rawId, 10);
                    if (!Number.isFinite(id)) return;

                    const cfg = window._fishConfig || {};
                    if (!(cfg.selected instanceof Set)) {
                        cfg.selected = new Set();
                    }

                    if (target.checked) {
                        cfg.selected.add(id);
                    } else {
                        cfg.selected.delete(id);
                    }

                    window._fishConfig = cfg;
                });
            });
        }

        async function loadFishData() {
            const lastUpdated = document.getElementById('lastUpdated');
            const errorBox = document.getElementById('errorBox');
            const versionSelect = document.getElementById('versionSelect');

            errorBox.style.display = 'none';
            const fallbackRows = parseRowsFromDom();
            const fallbackVersion = (versionSelect && versionSelect.value) || 'local';

            try {
                const dataRes = await fetch('version');
                if (!dataRes.ok) {
                    throw new Error('HTTP ' + dataRes.status + ' khi đọc file version');
                }
                const data = await dataRes.json();

                if (!Array.isArray(data)) {
                    throw new Error('Dữ liệu trả về không phải mảng');
                }

                if (versionSelect) {
                    versionSelect.innerHTML = '<option value="local">Local file</option>';
                    versionSelect.value = 'local';
                    versionSelect.disabled = true;
                    versionSelect.title = 'Dữ liệu đang đọc từ file version trong thư mục';
                }

                const currentVersion = 'local-file';

                const grouped = new Map();
                for (const item of data) {
                    const rawId = Number.isFinite(item.id) ? item.id : parseInt(item.id ?? '0', 10);
                    const id = rawId || 0;
                    if (!grouped.has(id)) {
                        grouped.set(id, []);
                    }
                    grouped.get(id).push(item);
                }

                let maxNames = 0;
                grouped.forEach((items) => {
                    if (Array.isArray(items) && items.length > maxNames) {
                        maxNames = items.length;
                    }
                });

                renderHeader(maxNames);

                const sortedIds = Array.from(grouped.keys()).sort((a, b) => a - b);

                const allRows = sortedIds.map((id) => ({
                    id,
                    fishes: grouped.get(id) || [],
                }));

                const baseCount = grouped.size;
                const lastText = sortedIds.length ? ' · Dữ liệu từ file version (local)' : '';

                window._fishConfig = {
                    maxNames,
                    allRows,
                    baseCount,
                    lastText,
                    selected: new Set(),
                    currentVersion,
                };

                lastUpdated.textContent = lastText;
                renderTable(allRows, maxNames);

                attachStaticHandlers();
            } catch (e) {
                console.error(e);
                if (fallbackRows.length) {
                    const maxNames = fallbackRows.reduce((max, row) => Math.max(max, Array.isArray(row.fishes) ? row.fishes.length : 0), 0);
                    renderHeader(maxNames);
                    const lastText = ' · Phiên bản hiện tại: ' + fallbackVersion;

                    window._fishConfig = {
                        maxNames,
                        allRows: fallbackRows,
                        baseCount: fallbackRows.length,
                        lastText,
                        selected: new Set(),
                        currentVersion: fallbackVersion,
                    };

                    lastUpdated.textContent = lastText;
                    renderTable(fallbackRows, maxNames);
                    attachStaticHandlers();

                    errorBox.textContent = 'Không thể tải dữ liệu từ /api/fish: ' + (e && e.message ? e.message : '') + ' (đang dùng dữ liệu có sẵn trong trang).';
                } else {
                    errorBox.textContent = 'Không thể tải dữ liệu từ /api/fish: ' + (e && e.message ? e.message : '');
                }
                errorBox.style.display = 'block';
            }
        }

        loadFishData();
    </script>

        </div>
    </div>
</body>
</html>
