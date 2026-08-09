(function () {
  function qs(selector, root) { return (root || document).querySelector(selector); }
  function qsa(selector, root) { return Array.prototype.slice.call((root || document).querySelectorAll(selector)); }
  function openConfirm(message, onConfirm) {
    var existing = qs('.erp-confirm-backdrop');
    if (existing) existing.remove();

    var backdrop = document.createElement('div');
    backdrop.className = 'erp-confirm-backdrop';
    backdrop.innerHTML = [
      '<div class="erp-confirm-dialog" role="dialog" aria-modal="true">',
      '<div class="erp-confirm-icon"><i class="bi bi-exclamation-triangle"></i></div>',
      '<div><h3>Xác nhận thao tác</h3><p>' + message + '</p></div>',
      '<div class="erp-confirm-actions">',
      '<button type="button" class="erp-btn" data-confirm-cancel>Hủy</button>',
      '<button type="button" class="erp-btn erp-btn-danger" data-confirm-ok>Đồng ý</button>',
      '</div>',
      '</div>'
    ].join('');
    document.body.appendChild(backdrop);

    qs('[data-confirm-cancel]', backdrop).addEventListener('click', function () { backdrop.remove(); });
    backdrop.addEventListener('click', function (event) {
      if (event.target === backdrop) backdrop.remove();
    });
    qs('[data-confirm-ok]', backdrop).addEventListener('click', function () {
      backdrop.remove();
      onConfirm();
    });
  }

  document.addEventListener('click', function (event) {
    var sidebarToggle = event.target.closest('[data-sidebar-toggle]');
    if (sidebarToggle) {
      document.body.classList.toggle('erp-sidebar-collapsed');
      localStorage.setItem('erp-sidebar-collapsed', document.body.classList.contains('erp-sidebar-collapsed') ? '1' : '0');
    }

    var mobileToggle = event.target.closest('[data-mobile-sidebar]');
    if (mobileToggle) {
      document.body.classList.toggle('erp-mobile-sidebar-open');
    }

    var drawerOpen = event.target.closest('[data-drawer-open]');
    if (drawerOpen) {
      var drawer = qs(drawerOpen.getAttribute('data-drawer-open'));
      if (drawer) {
        var template = drawerOpen.getAttribute('data-drawer-template');
        var body = qs('.erp-drawer-body', drawer);
        if (template && body) {
          var source = qs(template);
          if (source) body.innerHTML = source.innerHTML;
        }
        var url = drawerOpen.getAttribute('data-drawer-url');
        if (url && body) {
          body.innerHTML = '<div class="erp-empty"><div class="erp-skeleton mb-2"></div><div class="erp-skeleton mb-2"></div><div class="erp-skeleton"></div></div>';
          fetch(url)
            .then(function (response) { return response.json(); })
            .then(function (payload) {
              body.innerHTML = payload.success ? payload.html : '<div class="erp-empty">' + (payload.message || 'Không tải được dữ liệu.') + '</div>';
            })
            .catch(function () {
              body.innerHTML = '<div class="erp-empty">Không tải được dữ liệu.</div>';
            });
        }
        drawer.classList.add('open');
      }
    }

    var drawerClose = event.target.closest('[data-drawer-close]');
    if (drawerClose) {
      var parent = drawerClose.closest('.erp-drawer');
      if (parent) parent.classList.remove('open');
    }

    var confirmAction = event.target.closest('[data-confirm]');
    if (confirmAction && confirmAction.getAttribute('data-confirmed') !== '1') {
      event.preventDefault();
      openConfirm(confirmAction.getAttribute('data-confirm'), function () {
        confirmAction.setAttribute('data-confirmed', '1');
        if (confirmAction.tagName === 'A') {
          window.location.href = confirmAction.href;
          return;
        }
        var form = confirmAction.closest('form');
        if (form) {
          if (form.requestSubmit) form.requestSubmit(confirmAction);
          else form.submit();
        } else {
          confirmAction.click();
        }
      });
    }

    var filterToggle = event.target.closest('[data-filter-toggle]');
    if (filterToggle) {
      var target = qs(filterToggle.getAttribute('data-filter-toggle'));
      if (target) target.classList.toggle('d-none');
    }

    var tab = event.target.closest('[data-erp-tab]');
    if (tab) {
      event.preventDefault();
      var group = tab.closest('[data-erp-tabs]');
      if (!group) return;
      qsa('[data-erp-tab]', group).forEach(function (item) { item.classList.remove('active'); });
      qsa('[data-erp-panel]', document).forEach(function (panel) { panel.classList.add('d-none'); });
      tab.classList.add('active');
      var targetPanel = qs(tab.getAttribute('href'));
      if (targetPanel) targetPanel.classList.remove('d-none');
    }
  });

  document.addEventListener('change', function (event) {
    var master = event.target.closest('[data-bulk-master]');
    if (master) {
      qsa('[data-bulk-item]').forEach(function (item) { item.checked = master.checked; });
    }
  });

  if (localStorage.getItem('erp-sidebar-collapsed') === '1') {
    document.body.classList.add('erp-sidebar-collapsed');
  }

  qsa('[data-toast]').forEach(function (node) {
    window.erpToast(node.getAttribute('data-toast'), node.getAttribute('data-toast-type') || 'info');
  });
})();
