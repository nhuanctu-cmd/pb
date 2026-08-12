<?= view('sales/partials/header', ['pageTitle' => $pageTitle, 'active' => 'api', 'salesLocale' => $salesLocale]) ?>
<section class="developer-hero sales-container"><div><span class="sales-eyebrow"><?= esc(lang('Sales.developers_eyebrow')) ?></span><h1><?= nl2br(esc(lang('Sales.developers_title'))) ?></h1><p><?= esc(lang('Sales.developers_lede')) ?></p><div class="sales-hero-actions"><a class="sales-button" href="<?= esc(site_url('demo')) ?>"><?= esc(lang('Sales.start_trial')) ?> <span>↗</span></a><a class="sales-text-link" href="#api-access"><?= esc(lang('Sales.developers_docs')) ?> <span>↘</span></a></div></div><div class="code-card"><div class="code-dots"><i></i><i></i><i></i><span>request.json</span></div><pre><code><b>GET</b> <?= esc(lang('Sales.developers_endpoint')) ?>

{
  <em>"access"</em>: "scoped",
  <em>"dataset"</em>: "player",
  <em>"status"</em>: "verified"
}</code></pre></div></section>
<section class="developer-points sales-container" id="api-access"><article><span>01</span><h2><?= esc(lang('Sales.developers_auth')) ?></h2><p><?= esc(lang('Sales.developers_auth_desc')) ?></p></article><article><span>02</span><h2><?= esc(lang('Sales.developers_usage')) ?></h2><p><?= esc(lang('Sales.developers_usage_desc')) ?></p></article><article><span>03</span><h2><?= esc(lang('Sales.product_api_name')) ?></h2><p><?= esc(lang('Sales.product_api_message')) ?></p></article></section>
<section class="api-groups sales-container"><span class="sales-eyebrow">API / DATA PRODUCTS</span><h2><?= esc(lang('Sales.solutions_section_title')) ?></h2><div class="api-chip-grid"><?php foreach (['player','ranking','rating','tournament','match','club','event'] as $api): ?><span><?= strtoupper($api) ?> API <b>↗</b></span><?php endforeach; ?></div></section>
<?= view('sales/partials/footer') ?>
