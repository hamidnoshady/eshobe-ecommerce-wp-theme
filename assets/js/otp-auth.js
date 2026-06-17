(function () {
  var loginTabs = document.querySelectorAll('[data-wm-login-tab]');
  if (loginTabs.length) {
    var loginPanels = document.querySelectorAll('[data-wm-login-panel]');
    loginTabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        var target = tab.getAttribute('data-wm-login-tab');

        loginTabs.forEach(function (t) {
          t.classList.toggle('is-active', t === tab);
        });
        loginPanels.forEach(function (panel) {
          panel.hidden = panel.getAttribute('data-wm-login-panel') !== target;
        });
      });
    });
  }

  var modal = document.getElementById('wm-otp-modal');
  var triggers = document.querySelectorAll('[data-wm-otp-trigger]');

  if (!modal || !triggers.length || typeof window.wmOtpData === 'undefined') {
    return;
  }

  var modalTabs = modal.querySelectorAll('[data-otp-modal-tab]');
  var modalPanels = modal.querySelectorAll('[data-otp-modal-panel]');
  var passwordLoginForm = modal.querySelector('[data-otp-password-login-form]');
  var loginPhoneInput = modal.querySelector('#wm-login-phone');
  var loginPasswordInput = modal.querySelector('#wm-login-password');

  var phoneStep = modal.querySelector('[data-otp-step="phone"]');
  var codeStep = modal.querySelector('[data-otp-step="code"]');
  var passwordStep = modal.querySelector('[data-otp-step="password"]');
  var phoneForm = modal.querySelector('[data-otp-phone-form]');
  var codeForm = modal.querySelector('[data-otp-code-form]');
  var passwordForm = modal.querySelector('[data-otp-password-form]');
  var phoneInput = modal.querySelector('#wm-otp-phone');
  var codeInput = modal.querySelector('#wm-otp-code');
  var newPasswordInput = modal.querySelector('#wm-otp-new-password');
  var phoneDisplay = modal.querySelector('[data-otp-phone-display]');
  var resendButton = modal.querySelector('[data-otp-resend]');
  var resendTimer = modal.querySelector('[data-otp-resend-timer]');
  var backButton = modal.querySelector('[data-otp-back]');
  var closers = modal.querySelectorAll('[data-otp-modal-close]');

  var closeTimer = 0;
  var countdownTimer = 0;
  var redirectTo = '';
  var resendSeconds = parseInt(window.wmOtpData.resendSeconds, 10) || 60;
  var CLOSE_ANIMATION_MS = 240;

  function showError(scope, message) {
    var error = scope.querySelector('[data-otp-error]');
    if (!error) {
      return;
    }
    error.textContent = message;
    error.hidden = !message;
  }

  function setModalTab(tab) {
    modalTabs.forEach(function (button) {
      button.classList.toggle('is-active', button.getAttribute('data-otp-modal-tab') === tab);
    });
    modalPanels.forEach(function (panel) {
      panel.hidden = panel.getAttribute('data-otp-modal-panel') !== tab;
    });

    if (tab === 'password') {
      showError(modal.querySelector('[data-otp-modal-panel="password"]'), '');
      window.setTimeout(function () {
        if (loginPhoneInput) {
          loginPhoneInput.focus();
        }
      }, 30);
    } else {
      setStep('phone');
    }
  }

  function setStep(step) {
    if (!phoneStep) {
      return;
    }

    phoneStep.hidden = step !== 'phone';
    codeStep.hidden = step !== 'code';
    passwordStep.hidden = step !== 'password';
    showError(phoneStep, '');
    showError(codeStep, '');
    showError(passwordStep, '');

    if (step === 'phone') {
      window.setTimeout(function () {
        phoneInput.focus();
      }, 30);
    } else if (step === 'code') {
      codeInput.value = '';
      window.setTimeout(function () {
        codeInput.focus();
      }, 30);
    } else if (step === 'password') {
      newPasswordInput.value = '';
      window.setTimeout(function () {
        newPasswordInput.focus();
      }, 30);
    }
  }

  function openModal() {
    window.clearTimeout(closeTimer);
    modal.hidden = false;
    document.body.classList.add('wm-otp-modal-open');
    window.requestAnimationFrame(function () {
      modal.classList.add('is-open');
    });
    setModalTab('password');
  }

  function closeModal() {
    if (modal.hidden) {
      return;
    }

    modal.classList.remove('is-open');
    document.body.classList.remove('wm-otp-modal-open');

    window.clearTimeout(closeTimer);
    closeTimer = window.setTimeout(function () {
      modal.hidden = true;
    }, CLOSE_ANIMATION_MS);

    window.clearInterval(countdownTimer);
  }

  function startResendCountdown() {
    var remaining = resendSeconds;
    resendButton.disabled = true;
    resendTimer.textContent = remaining;

    window.clearInterval(countdownTimer);
    countdownTimer = window.setInterval(function () {
      remaining -= 1;
      resendTimer.textContent = Math.max(remaining, 0);

      if (remaining <= 0) {
        window.clearInterval(countdownTimer);
        resendButton.disabled = false;
      }
    }, 1000);
  }

  function requestCode(phone, scope) {
    var body = new window.URLSearchParams();
    body.set('action', 'wm_otp_request_code');
    body.set('nonce', window.wmOtpData.nonce);
    body.set('phone', phone);

    return window
      .fetch(window.wmOtpData.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data || !data.success) {
          var message = (data && data.data && data.data.message) || 'ارسال کد یکبارمصرف ناموفق بود.';
          showError(scope, message);
          return false;
        }
        return true;
      })
      .catch(function () {
        showError(scope, 'خطا در ارتباط با سرور. دوباره تلاش کنید.');
        return false;
      });
  }

  triggers.forEach(function (trigger) {
    trigger.addEventListener('click', function (event) {
      event.preventDefault();
      redirectTo = trigger.getAttribute('data-wm-otp-redirect') || '';
      openModal();
    });
  });

  closers.forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });

  modalTabs.forEach(function (button) {
    button.addEventListener('click', function () {
      setModalTab(button.getAttribute('data-otp-modal-tab'));
    });
  });

  if (passwordLoginForm) {
    passwordLoginForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var phone = loginPhoneInput.value.trim();
      var password = loginPasswordInput.value;
      var scope = modal.querySelector('[data-otp-modal-panel="password"]');

      if (!phone || !password) {
        return;
      }

      var submitButton = passwordLoginForm.querySelector('button[type="submit"]');
      submitButton.disabled = true;

      var body = new window.URLSearchParams();
      body.set('action', 'wm_otp_password_login');
      body.set('nonce', window.wmOtpData.nonce);
      body.set('phone', phone);
      body.set('password', password);
      body.set('redirect_to', redirectTo);

      window
        .fetch(window.wmOtpData.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString(),
        })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (data && data.success && data.data && data.data.redirect) {
            window.location.href = data.data.redirect;
            return;
          }

          submitButton.disabled = false;
          var message = (data && data.data && data.data.message) || 'ورود ناموفق بود.';
          showError(scope, message);
        })
        .catch(function () {
          submitButton.disabled = false;
          showError(scope, 'خطا در ارتباط با سرور. دوباره تلاش کنید.');
        });
    });
  }

  if (!phoneForm) {
    return;
  }

  phoneForm.addEventListener('submit', function (event) {
    event.preventDefault();

    var phone = phoneInput.value.trim();
    if (!phone) {
      return;
    }

    var submitButton = phoneForm.querySelector('button[type="submit"]');
    submitButton.disabled = true;

    requestCode(phone, phoneStep).then(function (success) {
      submitButton.disabled = false;

      if (success) {
        phoneDisplay.textContent = phone;
        setStep('code');
        startResendCountdown();
      }
    });
  });

  resendButton.addEventListener('click', function () {
    var phone = phoneInput.value.trim();
    if (!phone || resendButton.disabled) {
      return;
    }

    requestCode(phone, codeStep).then(function (success) {
      if (success) {
        startResendCountdown();
      }
    });
  });

  backButton.addEventListener('click', function () {
    window.clearInterval(countdownTimer);
    setStep('phone');
  });

  codeForm.addEventListener('submit', function (event) {
    event.preventDefault();

    var phone = phoneInput.value.trim();
    var code = codeInput.value.trim();

    if (!phone || !code) {
      return;
    }

    var submitButton = codeForm.querySelector('button[type="submit"]');
    submitButton.disabled = true;

    var body = new window.URLSearchParams();
    body.set('action', 'wm_otp_verify_code');
    body.set('nonce', window.wmOtpData.nonce);
    body.set('phone', phone);
    body.set('code', code);
    body.set('redirect_to', redirectTo);

    window
      .fetch(window.wmOtpData.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data && data.success && data.data && data.data.needsPassword) {
          submitButton.disabled = false;
          setStep('password');
          return;
        }

        if (data && data.success && data.data && data.data.redirect) {
          window.location.href = data.data.redirect;
          return;
        }

        submitButton.disabled = false;
        var message = (data && data.data && data.data.message) || 'کد وارد شده نادرست یا منقضی شده است.';
        showError(codeStep, message);
      })
      .catch(function () {
        submitButton.disabled = false;
        showError(codeStep, 'خطا در ارتباط با سرور. دوباره تلاش کنید.');
      });
  });

  passwordForm.addEventListener('submit', function (event) {
    event.preventDefault();

    var password = newPasswordInput.value;
    if (!password || password.length < 6) {
      showError(passwordStep, 'رمز عبور باید حداقل ۶ کاراکتر باشد.');
      return;
    }

    var submitButton = passwordForm.querySelector('button[type="submit"]');
    submitButton.disabled = true;

    var body = new window.URLSearchParams();
    body.set('action', 'wm_otp_set_password');
    body.set('nonce', window.wmOtpData.nonce);
    body.set('password', password);
    body.set('redirect_to', redirectTo);

    window
      .fetch(window.wmOtpData.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data && data.success && data.data && data.data.redirect) {
          window.location.href = data.data.redirect;
          return;
        }

        submitButton.disabled = false;
        var message = (data && data.data && data.data.message) || 'ثبت رمز عبور ناموفق بود.';
        showError(passwordStep, message);
      })
      .catch(function () {
        submitButton.disabled = false;
        showError(passwordStep, 'خطا در ارتباط با سرور. دوباره تلاش کنید.');
      });
  });
})();
