class OTPManager {
  state = StateManagerFactory();

  init = () => {
    this.loadState();
    this.state.set('step', 1);
    this.state.set('locktime', null);

    this.state.listen('step', this.changeStep);
    this.state.listen('locktime', this.suspendSend);

    //load state into fields
    document.querySelector('#otp_identifier').value = this.state.get('identifier') || '';
    document
      .querySelector('#otp_identifier')
      .addEventListener('change', e => this.state.set('identifier', e.target.value));

    document.querySelector('#request-otp').addEventListener('click', this.requestOtp);
    document.querySelector('#submit-otp-code').addEventListener('click', this.submitOtpCode);

    document.querySelector('#back-to-step-1').addEventListener('click', e => {
      this.state.set('step', 1);
    });
    document.querySelector('#go-to-step-2').addEventListener('click', () => {
      this.state.set('step', 2);
    });
  };

  suspendSend = locktime => {
    const step1 = document.querySelector(`[data-step-id='1']`);
    var messageBox = step1.querySelector('p.waittime');
    const submit = step1.querySelector("input[type='button']");

    if (locktime !== null) {
      submit.classList.add('disabled');

      if (!messageBox) {
        step1.insertAdjacentHTML('beforeend', `<p class='waittime'></p>`);
        messageBox = step1.querySelector('p.waittime');
      }
      messageBox.textContent = otpInfo.messages.please_wait_time.replace('%s', 60 - locktime);
    } else if (messageBox) {
      submit.classList.remove('disabled');
      step1.removeChild(messageBox);
    }
  };

  changeStep = step => {
    console.log(`step is ${step}`);
    const errBox = document.querySelector('#login_error');
    errBox.classList.add('hidden');

    document.querySelector('#go-to-step-2').classList.remove('hidden'); //it is only invisible at the begining.

    document.querySelectorAll('[data-step-id]').forEach(stp => stp.classList.add('hidden'));
    document.querySelector(`[data-step-id='${step}']`).classList.remove('hidden');
    document.querySelector('#otp_code').value = '';

    if (this.state.get('locktime') !== null) {
      document.querySelector("[data-step-id='1'] input[type='button']").classList.add('disabled');
    }
  };

  unlockScreen = () => {
    document.querySelectorAll('[data-step-id]').forEach(stp => {
      stp.querySelectorAll('input').forEach(inp => inp.removeAttribute('disabled'));
      stp.querySelector(".actions input[type='button']")?.classList.remove('disabled');
      stp.querySelector('.actions .secondary-area')?.classList.remove('hidden');
      stp.querySelector('.actions .step-loader').classList.add('hidden');
    });
  };

  lockScreen = () => {
    document.querySelectorAll('[data-step-id]').forEach(stp => {
      stp.querySelectorAll('input').forEach(inp => (inp.disabled = true));
      stp.querySelector(".actions input[type='button']")?.classList.add('disabled');
      stp.querySelector('.actions .secondary-area')?.classList.add('hidden');
      stp.querySelector('.actions .step-loader').classList.remove('hidden');
    });
  };

  submitOtpCode = async e => {
    e.preventDefault();
    e.stopPropagation();
    try {
      this.lockScreen();

      const errBox = document.querySelector('#login_error');
      errBox.classList.add('hidden');

      var result = await JSUtils.fetch(otpInfo.ajax_url, {
        action: 'verify_otp_code',
        user_id: this.state.get('user_id'),
        otp_code: document.querySelector('#otp_code').value
      });

      if (result.error) {
        errBox.classList.remove('hidden');
        errBox.textContent = result.message;
        errBox.classList.add('notice-error');
      } else {
        this.state.set('step', 3);
        document.querySelector("[data-step-id='3'] p.content").textContent = result.message;
        document.location.href = result.redirect;
      }
    } finally {
      this.unlockScreen();
    }
  };

  requestOtp = async e => {
    if (e.target.classList.contains('disabled')) return;

    e.preventDefault();
    e.stopPropagation();
    try {
      this.lockScreen();

      const errBox = document.querySelector('#login_error');
      errBox.classList.add('hidden');

      this.saveState();

      let result = await JSUtils.fetch(otpInfo.ajax_url, {
        action: 'send_otp',
        identifier: encodeURIComponent(this.state.get('identifier'))
      });

      if (result.error) {
        errBox.classList.add('notice-error');
      } else {
        this.state.set('locktime', 60); //locking screen for 1min for sending SMS/email
        const start = Date.now();
        var interval = null;
        interval = setInterval(() => {
          //clear locktime
          const diff = (Date.now() - start) / 1000;
          if (diff > 60) {
            clearInterval(interval);
            this.state.set('locktime', null);
          } else {
            this.state.set('locktime', Math.floor(diff));
          }
        }, 400);

        this.state.set('user_id', result.user_id);
        this.state.set('step', 2);
        errBox.classList.remove('notice-error');
      }
      errBox.classList.remove('hidden');
      errBox.textContent = result.message;
    } finally {
      this.unlockScreen();
    }
  };

  saveState = () => {
    var state = JSUtils.clone(this.state.state);
    for (var prop in state) {
      state[prop] = state[prop].value;
    }

    localStorage.setItem('otp-state', JSON.stringify(state));
  };

  loadState = () => {
    var savedState = JSON.parse(localStorage.getItem('otp-state') || '{}');
    for (var prop in savedState) {
      this.state.set(prop, savedState[prop]);
    }
  };
}

JSUtils.domReady(() => {
  const otpManager = new OTPManager();
  otpManager.init();
});
