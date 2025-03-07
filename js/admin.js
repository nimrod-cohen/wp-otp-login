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

    document.querySelector("input[name='wpotp_custom_login_page']").addEventListener('input', e => {
      let val = e.target.value;
      const siteurl = e.target.dataset.siteUrl;
      //if val doesn't start with http or https, add the site url
      if (!val.match(/^(http|https):\/\//)) {
        //check if site url ends with a slash, and val starts with a slash, then remove the slash from val
        if (val.startsWith('/') && siteurl.endsWith('/')) {
          val = siteurl + val.substring(1);
        } else if (!val.startsWith('/') && !siteurl.endsWith('/')) {
          val = `${siteurl}/${val}`;
        } else {
          val = siteurl + val;
        }
      }
      document.querySelector('#wpotp_custom_login_page_actual').innerText = val;
    });
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
