<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'Live Scores') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= esc(asset_url('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Live Scores</h1>
            <div class="text-muted">Bracket mobile · bảng điểm · kết quả trận</div>
        </div>
        <a href="/live-scores/tv" class="btn btn-outline-primary">TV</a>
    </div>

    <section class="mb-4">
        <h2 class="h5">Bracket</h2>
        <div id="live-score-list" class="row g-3">
            <?php foreach (($data['matches'] ?? []) as $match): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-3 h-100">
                        <div class="d-flex justify-content-between"><strong>Trận #<?= (int) $match->id ?></strong><span><?= esc($match->status ?? 'scheduled') ?></span></div>
                        <div class="mt-3 d-flex justify-content-between"><span><?= esc($match->team_a_name ?? ('Team #' . ($match->team_a_id ?? '-'))) ?></span><strong><?= esc($match->score_text ?? '-') ?></strong></div>
                        <div class="d-flex justify-content-between"><span><?= esc($match->team_b_name ?? ('Team #' . ($match->team_b_id ?? '-'))) ?></span><span><?= ! empty($match->winner_team_id) ? 'Winner #' . (int) $match->winner_team_id : '' ?></span></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($data['matches'])): ?>
            <div class="alert alert-light border">Chưa có dữ liệu trận đấu live.</div>
        <?php endif; ?>
    </section>

    <section>
        <h2 class="h5">Bảng điểm</h2>
        <?php if (empty($data['standings'])): ?>
            <div class="alert alert-light border">Chưa có bảng điểm vòng bảng.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Team</th><th>Played</th><th>Wins</th><th>Losses</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['standings'] as $row): ?>
                        <tr><td>#<?= (int) $row->team_id ?></td><td><?= (int) $row->played ?></td><td><?= (int) $row->wins ?></td><td><?= (int) $row->losses ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<script>
setInterval(() => fetch('/api/v1/live-scores/bracket').then(() => location.reload()), 5000);
</script>
</body>
</html>
