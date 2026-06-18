(function () {
  var modal = document.getElementById('wm-otp-modal');
  var triggers = document.querySelectorAll('[data-wm-otp-trigger]');

  if (!modal || !triggers.length || typeof window.wmOtpData === 'undefined') {
    return;
  }

  var phoneStep            = modal.querySelector('[data-otp-step="phone"]');
  var existingPasswordStep = modal.querySelector('[data-otp-step="existing-password"]');
  var codeStep             = modal.querySelector('[data-otp-step="code"]');
  var passwordStep         = modal.querySelector('[data-otp-step="password"]');

  var phoneForm            = modal.querySelector('[data-otp-phone-form]');
  var existingPasswordForm = modal.querySelector('[data-otp-existing-password-form]');
  var codeForm             = modal.querySelector('[data-otp-code-form]');
  var passwordForm         = modal.querySelector('[data-otp-password-form]');

  var phoneInput            = modal.querySelector('#wm-otp-phone');
  var existingPasswordInput = modal.querySelector('#wm-otp-existing-password');
  var codeInput             = modal.querySelector('#wm-otp-code');
  var newPasswordInput      = modal.querySelector('#wm-otp-new-password');

  var phoneDisplays  = modal.querySelectorAll('[data-otp-phone-display]');
  var resendButton   = modal.querySelector('[data-otp-resend]');
  var resendTimer    = modal.querySelector('[data-otp-resend-timer]');
  var backButtons    = modal.querySelectorAll('[data-otp-back]');
  var loginWithCodeButton = modal.querySelector('[data-otp-login-with-code]');
  var closers        = modal.querySelectorAll('[data-otp-modal-close]');

  var allSteps = [phoneStep, existingPasswordStep, codeStep, passwordStep].filter(Boolean);

  var closeTimer     = 0;
  var countdownTimer = 0;
  var redirectTo     = '';
  var currentPhone   = '';
  var isForced       = false;
  var resendSeconds  = parseInt(window.wmOtpData.resendSeconds, 10) || 60;
  var CLOSE_ANIMATION_MS = 240;

  function showError(scope, message) {
    var error = scope && scope.querySelector('[data-otp-error]');
    if (!error) { return; }
    error.textContent = message;
    error.hidden = !message;
  }

  function setPhoneDisplay(phone) {
    phoneDisplays.forEach(function (el) { el.textContent = phone; });
  }

  function setStep(step) {
    allSteps.forEach(function (stepEl) {
      stepEl.hidden = stepEl.getAttribute('data-otp-step') !== step;
      showError(stepEl, '');
    });

    var focusMap = {
      'phone':             phoneInput,
      'existing-password': existingPasswordInput,
      'code':              codeInput,
      'password':          newPasswordInput,
    };

    var focusTarget = focusMap[step];
    if (focusTarget) {
      if (step !== 'phone') { focusTarget.value = ''; }
      window.setTimeout(function () { focusTarget.focus(); }, 30);
    }
  }

  function openModal(forced) {
    window.clearTimeout(closeTimer);
    isForced = !!forced;
    modal.classList.toggle('wm-otp-modal--forced', isForced);
    modal.hidden = false;
    document.body.classList.add('wm-otp-modal-open');
    window.requestAnimationFrame(function () { modal.classList.add('is-open'); });
    setStep('phone');
  }

  function closeModal() {
    if (modal.hidden || isForced) { return; }
    modal.classList.remove('is-open');
    document.body.classList.remove('wm-otp-modal-open');
    window.clearTimeout(closeTimer);
    closeTimer = window.setTimeout(function () { modal.hidden = true; }, CLOSE_ANIMATION_MS);
    window.clearInterval(countdownTimer);
  }

  function startResendCountdown() {
    if (!resendButton || !resendTimer) { return; }
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

    return window.fetch(window.wmOtpData.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.success) {
          showError(scope, (data && data.data && data.data.message) || 'ارسال کد یکبارمصرف ناموفق بود.');
          return false;
        }
        return true;
      })
      .catch(function () {
        showError(scope, 'خطا در ارتباط با سرور. دوباره تلاش کنید.');
        return false;
      });
  }

  /* ── Triggers / close ── */

  triggers.forEach(function (trigger) {
    trigger.addEventListener('click', function (event) {
      event.preventDefault();
      redirectTo = trigger.getAttribute('data-wm-otp-redirect') || '';
      openModal(trigger.hasAttribute('data-wm-otp-force'));
    });
  });

  closers.forEach(function (el) { el.addEventListener('click', closeModal); });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) { closeModal(); }
  });

  var autoTriggerEl = document.querySelector('[data-wm-otp-autotrigger]');
  if (autoTriggerEl) {
    redirectTo = autoTriggerEl.getAttribute('data-wm-otp-redirect') || '';
    openModal(autoTriggerEl.hasAttribute('data-wm-otp-force'));
  }

  /* ── Step 1: Phone check ── */

  if (phoneForm) {
    phoneForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var phone = phoneInput.value.trim();
      if (!phone) { return; }

      var btn = phoneForm.querySelector('button[type="submit"]');
      btn.disabled = true;

      var body = new window.URLSearchParams();
      body.set('action', 'wm_otp_check_phone');
      body.set('nonce', window.wmOtpData.nonce);
      body.set('phone', phone);

      window.fetch(window.wmOtpData.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          btn.disabled = false;

          if (!data || !data.success) {
            showError(phoneStep, (data && data.data && data.data.message) || 'بررسی شماره موبایل ناموفق بود.');
            return;
          }

          currentPhone = phone;
          setPhoneDisplay(phone);

          if (data.data && data.data.exists) {
            setStep('existing-password');
          } else {
            setStep('code');
            startResendCountdown();
          }
        })
        .catch(function () {
          btn.disabled = false;
          showError(phoneStep, 'خطا در ارتباط با سرور. دوباره تلاش کنید.');
        });
    });
  }

  /* ── Step 2a: Existing user — password login ── */

  if (existingPasswordForm) {
    existingPasswordForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var password = existingPasswordInput.value;
      if (!currentPhone || !password) { return; }

      var btn = existingPasswordForm.querySelector('button[type="submit"]');
      btn.disabled = true;

      var body = new window.URLSearchParams();
      body.set('action', 'wm_otp_password_login');
      body.set('nonce', window.wmOtpData.nonce);
      body.set('phone', currentPhone);
      body.set('password', password);
      body.set('redirect_to', redirectTo);

      window.fetch(window.wmOtpData.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.success && data.data && data.data.redirect) {
            window.location.href = data.data.redirect;
            return;
          }
          btn.disabled = false;
          showError(existingPasswordStep, (data && data.data && data.data.message) || 'ورود ناموفق بود.');
        })
        .catch(function () {
          btn.disabled = false;
          showError(existingPasswordStep, 'خطا در ارتباط با سرور. دوباره تلاش کنید.');
        });
    });
  }

  /* "Login with OTP" link on existing-password step */

  if (loginWithCodeButton) {
    loginWithCodeButton.addEventListener('click', function () {
      if (!currentPhone) { return; }
      loginWithCodeButton.disabled = true;
      requestCode(currentPhone, existingPasswordStep).then(function (success) {
        loginWithCodeButton.disabled = false;
        if (success) {
          setStep('code');
          startResendCountdown();
        }
      });
    });
  }

  /* ── Step 2b: OTP code verification ── */

  if (codeForm) {
    codeForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var code = codeInput.value.trim();
      if (!currentPhone || !code) { return; }

      var btn = codeForm.querySelector('button[type="submit"]');
      btn.disabled = true;

      var body = new window.URLSearchParams();
      body.set('action', 'wm_otp_verify_code');
      body.set('nonce', window.wmOtpData.nonce);
      body.set('phone', currentPhone);
      body.set('code', code);
      body.set('redirect_to', redirectTo);

      window.fetch(window.wmOtpData.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.success && data.data && data.data.needsPassword) {
            btn.disabled = false;
            setStep('password');
            return;
          }
          if (data && data.success && data.data && data.data.redirect) {
            window.location.href = data.data.redirect;
            return;
          }
          btn.disabled = false;
          showError(codeStep, (data && data.data && data.data.message) || 'کد وارد شده نادرست یا منقضی شده است.');
        })
        .catch(function () {
          btn.disabled = false;
          showError(codeStep, 'خطا در ارتباط با سرور. دوباره تلاش کنید.');
        });
    });
  }

  /* Resend OTP */

  if (resendButton) {
    resendButton.addEventListener('click', function () {
      if (!currentPhone || resendButton.disabled) { return; }
      requestCode(currentPhone, codeStep).then(function (success) {
        if (success) { startResendCountdown(); }
      });
    });
  }

  /* ── Step 3: Set password after OTP ── */

  if (passwordForm) {
    passwordForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var password = newPasswordInput.value;
      if (!password || password.length < 6) {
        showError(passwordStep, 'رمز عبور باید حداقل ۶ کاراکتر باشد.');
        return;
      }

      var btn = passwordForm.querySelector('button[type="submit"]');
      btn.disabled = true;

      var body = new window.URLSearchParams();
      body.set('action', 'wm_otp_set_password');
      body.set('nonce', window.wmOtpData.nonce);
      body.set('password', password);
      body.set('redirect_to', redirectTo);

      window.fetch(window.wmOtpData.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.success && data.data && data.data.redirect) {
            window.location.href = data.data.redirect;
            return;
          }
          btn.disabled = false;
          showError(passwordStep, (data && data.data && data.data.message) || 'ثبت رمز عبور ناموفق بود.');
        })
        .catch(function () {
          btn.disabled = false;
          showError(passwordStep, 'خطا در ارتباط با سرور. دوباره تلاش کنید.');
        });
    });
  }

  /* ── Back buttons ── */

  backButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      window.clearInterval(countdownTimer);
      setStep('phone');
    });
  });

})();
