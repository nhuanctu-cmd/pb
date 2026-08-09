<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="/admin/players" class="btn btn-outline-secondary"><i class="bi bi-people"></i> Players</a>
    <a href="/admin/players/ranking" class="btn btn-outline-warning"><i class="bi bi-trophy"></i> Ranking</a>
</div>

<div class="row g-3 mb-3">
    <?php
    $cards = [
        ['label' => 'Total players', 'value' => $dashboard['total_players'] ?? 0, 'icon' => 'bi-people', 'class' => 'primary'],
        ['label' => 'Active players', 'value' => $dashboard['active_players'] ?? 0, 'icon' => 'bi-person-check', 'class' => 'success'],
        ['label' => 'Active members', 'value' => $dashboard['active_members'] ?? 0, 'icon' => 'bi-award', 'class' => 'warning'],
        ['label' => 'Matches', 'value' => $dashboard['total_matches'] ?? 0, 'icon' => 'bi-controller', 'class' => 'info'],
        ['label' => 'Check-ins', 'value' => $dashboard['total_checkins'] ?? 0, 'icon' => 'bi-qr-code-scan', 'class' => 'secondary'],
        ['label' => 'MVP awards', 'value' => $dashboard['total_mvps'] ?? 0, 'icon' => 'bi-star-fill', 'class' => 'danger'],
    ];
    ?>
    <?php foreach ($cards as $card): ?>
    <div class="col-md-4 col-xl-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small"><?= esc($card['label']) ?></div>
                        <div class="fs-3 fw-semibold text-<?= esc($card['class']) ?>"><?= esc($card['value']) ?></div>
                    </div>
                    <i class="bi <?= esc($card['icon']) ?> fs-2 text-<?= esc($card['class']) ?> opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header fw-semibold">Top ELO Players</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>#</th><th>Player</th><th>Level</th><th>ELO</th><th>Win rate</th><th>MVP</th></tr></thead>
                    <tbody>
                    <?php foreach (($dashboard['top_players'] ?? []) as $index => $player): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><a href="/admin/players/profile/<?= $player->id ?>"><?= esc($player->full_name) ?></a><br><small class="text-muted"><?= esc($player->player_code) ?></small></td>
                            <td><?= $player->getLevelBadge() ?></td>
                            <td><?= (int) ($player->elo_rating ?? $player->rating_score ?? 1000) ?></td>
                            <td><?= number_format((float) ($player->win_rate ?? 0), 1) ?>%</td>
                            <td><?= (int) ($player->stat_mvp_count ?? $player->mvp_count ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($dashboard['top_players'])): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold">Regions</div>
            <div class="card-body">
                <?php foreach (($dashboard['regions'] ?? []) as $region): ?>
                    <a href="/admin/players?region=<?= urlencode($region) ?>" class="badge bg-light text-dark border me-1 mb-1"><?= esc($region) ?></a>
                <?php endforeach; ?>
                <?php if (empty($dashboard['regions'])): ?>
                    <div class="text-muted"><?= lang('App.no_data') ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header fw-semibold">Top Check-in Streaks</div>
            <div class="list-group list-group-flush">
                <?php foreach (($dashboard['top_streaks'] ?? []) as $player): ?>
                <a href="/admin/players/profile/<?= $player->id ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><?= esc($player->full_name) ?></span>
                    <span class="badge bg-primary"><?= (int) ($player->checkin_streak ?? 0) ?></span>
                </a>
                <?php endforeach; ?>
                <?php if (empty($dashboard['top_streaks'])): ?>
                <div class="list-group-item text-muted"><?= lang('App.no_data') ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header fw-semibold">Recent Achievements</div>
            <div class="list-group list-group-flush">
                <?php foreach (($dashboard['recent_achievements'] ?? []) as $achievement): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <strong><?= esc($achievement->name) ?></strong>
                        <span class="badge bg-success"><?= (int) $achievement->points ?> pts</span>
                    </div>
                    <small class="text-muted"><?= esc($achievement->full_name) ?></small>
                </div>
                <?php endforeach; ?>
                <?php if (empty($dashboard['recent_achievements'])): ?>
                <div class="list-group-item text-muted"><?= lang('App.no_data') ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
