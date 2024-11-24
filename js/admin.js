JSUtils.domReady(() => {
  const initTabs = () => {
    document.querySelectorAll('.otp-settings-screen .nav-tab').forEach(nav =>
      nav.addEventListener('click', e => {
        const tabId = e.target.dataset.tabId;
        document.querySelectorAll('.otp-settings-screen .settings-tab').forEach(tab => (tab.style.display = 'none'));
        document.querySelector(`.otp-settings-screen #${tabId}`).style.display = 'block';
        document.querySelectorAll('.otp-settings-screen .nav-tab').forEach(nv => nv.classList.remove('nav-tab-active'));
        e.target.classList.add('nav-tab-active');
      })
    );
  };

  const saveSettings = async e => {
    var data = { action: 'save_otp_settings' };

    const tab = e.target.closest('.settings-tab');
    tab.querySelectorAll('.otp-settings .input').forEach(inp => {
      switch (inp.tagName) {
        case 'TEXTAREA':
          data[inp.getAttribute('name')] = inp.value;
          break;
        case 'INPUT':
          if (inp.type === 'checkbox') data[inp.getAttribute('name')] = inp.checked;
          else data[inp.getAttribute('name')] = inp.value;
          break;
      }
    });

    const result = await JSUtils.fetch(wpjsutils_data.ajax_url, data);
    window.notifications.show(result.message, result.error ? 'error' : 'success');
  };

  initTabs();

  document
    .querySelectorAll('.otp-settings-screen .save-settings')
    .forEach(btn => btn.addEventListener('click', saveSettings));
});
