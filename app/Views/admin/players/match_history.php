<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold">Record Match</div>
            <div class="card-body">
                <?php if (session()->has('errors')): ?>
                    <div class="alert alert-danger">
                        <?php foreach (session('errors') as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="/admin/players/match-history/<?= $player->id ?>" class="vstack gap-3">
                    <div>
                        <label class="form-label">Opponent</label>
                        <select name="opponent_player_id" class="form-select">
                            <option value="">Guest / unknown</option>
                            <?php foreach (($players ?? []) as $opponent): ?>
                            <option value="<?= $opponent->id ?>"><?= esc($opponent->full_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach (($branches ?? []) as $branch): ?>
                            <option value="<?= $branch->id ?>"><?= esc($branch->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Result</label>
                        <select name="result" class="form-select" required>
                            <option value="win">Win</option>
                            <option value="loss">Loss</option>
                            <option value="draw">Draw</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Match date</label>
                        <input type="datetime-local" name="match_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div>
                        <label class="form-label">Score</label>
                        <input name="score" class="form-control" placeholder="11-7, 11-9">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_mvp" value="1" id="is_mvp">
                        <label class="form-check-label" for="is_mvp">MVP</label>
                    </div>
                    <div>
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Save match</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header fw-semibold"><?= esc($player->full_name) ?> - Match History</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Date</th><th>Opponent</th><th>Branch</th><th>Result</th><th>Score</th><th>ELO</th><th>MVP</th></tr></thead>
                    <tbody>
                    <?php foreach (($matches ?? []) as $match): ?>
                        <tr>
                            <td><?= format_datetime($match->match_date) ?></td>
                            <td><?= esc($match->opponent_name ?? '-') ?></td>
                            <td><?= esc($match->branch_name ?? '-') ?></td>
                            <td><span class="badge bg-<?= $match->result === 'win' ? 'success' : ($match->result === 'loss' ? 'danger' : 'secondary') ?>"><?= esc($match->result) ?></span></td>
                            <td><?= esc($match->score ?? '-') ?></td>
                            <td><?= (int) $match->rating_before ?> -> <?= (int) $match->rating_after ?> <small class="<?= $match->rating_delta >= 0 ? 'text-success' : 'text-danger' ?>"><?= $match->rating_delta >= 0 ? '+' : '' ?><?= (int) $match->rating_delta ?></small></td>
                            <td><?= $match->is_mvp ? '<span class="badge bg-warning text-dark">MVP</span>' : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($matches)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4"><?= lang('App.no_data') ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
