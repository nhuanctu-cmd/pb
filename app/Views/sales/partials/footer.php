</main>
<footer class="sales-footer">
    <div class="sales-container sales-footer-main">
        <div>
            <a class="sales-brand sales-brand-light" href="<?= esc(site_url('solutions')) ?>"><span class="sales-brand-mark">P</span><span><strong>PICKLEBALL</strong><small>NETWORK</small></span></a>
            <p><?= esc(lang('Sales.footer_copyright')) ?></p>
        </div>
        <div class="sales-footer-links">
            <div><b><?= esc(lang('Sales.footer_products')) ?></b><a href="<?= esc(site_url('solutions/court-management')) ?>"><?= esc(lang('Sales.product_court_name')) ?></a><a href="<?= esc(site_url('solutions/club')) ?>"><?= esc(lang('Sales.product_club_name')) ?></a><a href="<?= esc(site_url('solutions/tournament')) ?>"><?= esc(lang('Sales.product_tournament_name')) ?></a></div>
            <div><b><?= esc(lang('Sales.footer_resources')) ?></b><a href="<?= esc(site_url('pricing')) ?>"><?= esc(lang('Sales.nav_pricing')) ?></a><a href="<?= esc(site_url('developers')) ?>"><?= esc(lang('Sales.nav_api')) ?></a><a href="<?= esc(site_url('ranking/pro')) ?>"><?= esc(lang('Sales.product_ranking_name')) ?></a></div>
            <div><b><?= esc(lang('Sales.footer_company')) ?></b><a href="<?= esc(site_url('demo')) ?>"><?= esc(lang('Sales.contact_sales')) ?></a><a href="<?= esc(site_url('')) ?>"><?= esc(lang('Sales.back_to_portal')) ?></a></div>
        </div>
    </div>
    <div class="sales-container sales-footer-bottom"><span>© <?= date('Y') ?> Pickleball Network</span><span>VI / EN</span></div>
</footer>
<script>
document.querySelector('[data-sales-menu-toggle]')?.addEventListener('click', function () {
    const menu = document.getElementById('sales-menu');
    const open = menu.classList.toggle('is-open');
    this.setAttribute('aria-expanded', open ? 'true' : 'false');
});
</script>
</body>
</html>
