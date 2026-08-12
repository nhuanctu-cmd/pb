(function () {
    const navToggle = document.querySelector('[data-nav-toggle]');
    const menu = document.querySelector('#portal-menu');
    if (navToggle && menu) {
        navToggle.addEventListener('click', function () {
            const open = menu.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    const rankingShell = document.querySelector('[data-ranking-shell]');
    const historyPanel = document.querySelector('[data-rating-history]');
    const historyChart = historyPanel && historyPanel.querySelector('[data-history-chart]');
    const historyCaption = historyPanel && historyPanel.querySelector('[data-history-caption]');
    const historyDiscipline = historyPanel && historyPanel.querySelector('[data-history-discipline]');
    const esc = function (value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
        });
    };
    const format = function (value, digits) {
        if (value == null || value === '') return '—';
        return Number(value).toLocaleString('vi-VN', {minimumFractionDigits: digits || 0, maximumFractionDigits: digits || 0});
    };
    const renderRanking = function (items) {
        if (!rankingShell) return;
        const podium = rankingShell.querySelector('.top-three');
        const body = rankingShell.querySelector('.ranking-table tbody');
        if (!items || !items.length) {
            if (podium) podium.innerHTML = '<div class="module-empty"><span class="empty-icon">◎</span><strong>Chưa có dữ liệu discipline này</strong><p>Rating chính thức sẽ xuất hiện sau khi có official match.</p></div>';
            if (body) body.innerHTML = '<tr><td colspan="9" class="table-empty">Chưa có dữ liệu.</td></tr>';
            return;
        }
        if (podium) podium.innerHTML = items.slice(0, 3).map(function (player, index) {
            return '<a class="podium-card podium-' + (index + 1) + '" href="/players/' + encodeURIComponent(player.player_code || player.player_id) + '"><span class="podium-rank">#' + esc(player.rank) + '</span><span class="avatar avatar-' + (index + 1) + '">' + esc((player.name || '?').charAt(0).toUpperCase()) + '</span><strong>' + esc(player.name) + '</strong><small>' + esc(player.province || '—') + ' · ' + esc(player.skill_band || 'NR') + ' · Reliability ' + format(player.reliability, 0) + '%</small><b>' + format(player.rating, 3) + ' <small>RATING</small></b></a>';
        }).join('');
        if (body) body.innerHTML = items.map(function (player) {
            return '<tr><td><b class="rank-number">#' + esc(player.rank) + '</b></td><td><a class="player-cell" href="/players/' + encodeURIComponent(player.player_code || player.player_id) + '"><span class="mini-avatar">' + esc((player.name || '?').charAt(0).toUpperCase()) + '</span><span><strong>' + esc(player.name) + '</strong><small>' + esc(player.national_player_id || player.player_code || 'National Player') + '</small></span></a></td><td>' + esc(player.province || '—') + '</td><td>' + esc(player.club || '—') + '</td><td class="mono strong">' + format(player.rating, 3) + '</td><td><span class="skill-chip">' + esc(player.skill_band || 'NR') + '</span></td><td class="mono">' + format(player.reliability, 0) + '%</td><td class="mono">' + format(player.match_count, 0) + '</td><td class="mono strong">' + (Number(player.points || 0) > 0 ? format(player.points, 0) : '—') + '</td></tr>';
        }).join('');
    };
    const drawHistory = function (items, playerId, discipline) {
        if (!historyPanel || !historyChart) return;
        historyPanel.hidden = false;
        historyDiscipline.textContent = discipline === 'mixed_doubles' ? 'Mixed doubles' : discipline.charAt(0).toUpperCase() + discipline.slice(1);
        if (!items || !items.length) {
            historyCaption.textContent = 'Chưa có lịch sử rating công khai cho VĐV này.';
            historyChart.innerHTML = '';
            return;
        }
        const ordered = items.slice().reverse();
        const values = ordered.map(function (item) { return Number(item.after_rating || 0); });
        const min = Math.min.apply(Math, values) - 0.05;
        const max = Math.max.apply(Math, values) + 0.05;
        const width = 680, height = 180, left = 24, top = 20;
        const points = values.map(function (value, index) {
            const x = left + (ordered.length === 1 ? width / 2 : index * width / (ordered.length - 1));
            const y = top + height - ((value - min) / Math.max(0.001, max - min)) * height;
            return x.toFixed(1) + ',' + y.toFixed(1);
        }).join(' ');
        const types = ordered.map(function (item) { return esc(item.transaction_type || item.reason || 'transaction'); }).join(' · ');
        historyCaption.textContent = 'VĐV #' + playerId + ' · ' + ordered.length + ' bản ghi (' + types + ') · điểm cuối ' + format(values[values.length - 1], 3);
        historyChart.innerHTML = '<line x1="24" y1="200" x2="704" y2="200" class="history-axis"></line><polyline points="' + points + '" class="history-line"></polyline>' + ordered.map(function (item, index) { const pair = points.split(' ')[index].split(','); const label = format(item.after_rating, 3) + ' · ' + esc(item.processed_at || '') + ' · ' + esc(item.reason || item.transaction_type || 'transaction'); return '<circle cx="' + pair[0] + '" cy="' + pair[1] + '" r="4" class="history-point"><title>' + label + '</title></circle>'; }).join('');
    };
    const loadHistory = function (playerId) {
        const discipline = rankingShell ? rankingShell.dataset.discipline || 'singles' : 'singles';
        if (historyPanel) { historyPanel.hidden = false; historyCaption.textContent = 'Đang tải lịch sử Rating…'; }
        fetch('/api/public/v1/players/' + encodeURIComponent(playerId) + '/rating-history?discipline=' + encodeURIComponent(discipline), {headers: {Accept: 'application/json'}})
            .then(function (response) { return response.ok ? response.json() : Promise.reject(new Error('history_failed')); })
            .then(function (payload) { drawHistory((payload.data || {}).items || [], playerId, discipline); })
            .catch(function () { if (historyCaption) historyCaption.textContent = 'Không thể tải lịch sử Rating lúc này.'; });
    };
    const loadRanking = function (discipline) {
        if (!rankingShell) return;
        rankingShell.classList.add('is-loading');
        fetch('/api/public/v1/home?discipline=' + encodeURIComponent(discipline), {headers: {Accept: 'application/json'}})
            .then(function (response) { return response.ok ? response.json() : Promise.reject(new Error('ranking_failed')); })
            .then(function (payload) { renderRanking((payload.data || {}).top_rankings || []); rankingShell.dataset.discipline = discipline; })
            .catch(function () { if (rankingShell.querySelector('.ranking-table tbody')) rankingShell.querySelector('.ranking-table tbody').innerHTML = '<tr><td colspan="9" class="table-empty">Không thể tải bảng xếp hạng.</td></tr>'; })
            .finally(function () { rankingShell.classList.remove('is-loading'); });
    };
    document.querySelectorAll('.discipline-tabs button').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('.discipline-tabs button').forEach(function (item) {
                item.classList.remove('is-active');
                item.setAttribute('aria-selected', 'false');
            });
            button.classList.add('is-active');
            button.setAttribute('aria-selected', 'true');
            loadRanking(button.dataset.discipline || 'singles');
        });
    });
    if (rankingShell) rankingShell.addEventListener('click', function (event) {
        const target = event.target.closest('[data-rating-player]');
        if (! target) return;
        event.preventDefault();
        loadHistory(target.dataset.ratingPlayer);
        if (historyPanel) historyPanel.scrollIntoView({behavior: 'smooth', block: 'center'});
    });

    const form = document.querySelector('[data-player-search]');
    const input = document.querySelector('[data-search-input]');
    const results = document.querySelector('[data-search-results]');
    let timer;
    if (form && input && results) {
        const renderGroup = function (title, items, renderer) {
            if (!items || !items.length) return '';
            return '<div class="search-result-group"><span>' + title + '</span>' + items.map(renderer).join('') + '</div>';
        };
        const render = function (data) {
            const html = renderGroup('Vận động viên', data.players, function (player) {
                return '<a class="search-result" href="/players/' + encodeURIComponent(player.player_code || player.id) + '"><span class="mini-avatar">' + esc((player.full_name || '?').charAt(0).toUpperCase()) + '</span><span><b>' + esc(player.full_name || 'VĐV') + '</b><small>' + esc(player.national_player_id || player.player_code || 'National Player') + ' · ' + esc(player.region || '—') + (player.rating != null ? ' · Rating ' + format(player.rating, 3) : '') + '</small></span></a>';
            }) + renderGroup('Câu lạc bộ', data.clubs, function (club) {
                return '<a class="search-result" href="/clubs"><span class="mini-avatar">' + (club.name || 'C').charAt(0).toUpperCase() + '</span><span><b>' + (club.name || 'CLB') + '</b><small>' + (club.province || 'Việt Nam') + '</small></span></a>';
            }) + renderGroup('Giải đấu', data.tournaments, function (event) {
                return '<a class="search-result" href="/tournaments/' + encodeURIComponent(event.slug || event.id) + '"><span class="mini-avatar">↗</span><span><b>' + (event.name || 'Giải đấu') + '</b><small>' + (event.start_date || '—') + '</small></span></a>';
            });
            results.innerHTML = html || '<div class="search-result-group"><small>Không tìm thấy dữ liệu công khai phù hợp.</small></div>';
            results.hidden = false;
        };
        const search = function () {
            const query = input.value.trim();
            if (query.length < 2) { results.hidden = true; return; }
            fetch('/api/public/v1/search?q=' + encodeURIComponent(query), { headers: { Accept: 'application/json' } })
                .then(function (response) { return response.ok ? response.json() : Promise.reject(new Error('search_failed')); })
                .then(function (payload) { render(payload.data || {}); })
                .catch(function () { results.innerHTML = '<div class="search-result-group"><small>Không thể tải kết quả tìm kiếm lúc này.</small></div>'; results.hidden = false; });
        };
        input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(search, 220); });
        form.addEventListener('submit', function (event) { event.preventDefault(); search(); });
        document.addEventListener('click', function (event) { if (!form.contains(event.target)) results.hidden = true; });
    }
}());
