(function () {
  window.erpToast = function (message, type) {
    var container = document.querySelector('[data-toast-container]');
    if (!container) return;
    var toast = document.createElement('div');
    toast.className = 'alert alert-' + (type || 'info') + ' shadow-sm mb-2';
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(function () { toast.remove(); }, 4200);
  };

  window.erpConfirm = function (message, callback) {
    if (window.confirm(message || 'Bạn chắc chắn muốn thực hiện thao tác này?')) {
      callback && callback();
      return true;
    }
    return false;
  };
})();
