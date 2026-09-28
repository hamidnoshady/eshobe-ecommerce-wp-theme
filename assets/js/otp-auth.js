(function () {
  var SESSION_KEY = 'wm_otp_saved_phone';

  var modal    = document.getElementById('wm-otp-modal');
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

  var phoneDisplays        = modal.querySelectorAll('[data-otp-phone-display]');
  var strengthEl           = modal.querySelector('[data-otp-strength]');
  var resendButton         = modal.querySelector('[data-otp-resend]');
  var resendTimer          = modal.querySelector('[data-otp-resend-timer]');
  var backButtons          = modal.querySelectorAll('[data-otp-back]');
  var loginWithCodeButton  = modal.querySelector('[data-otp-login-with-code]');
  var closers              = modal.querySelectorAll('[data-otp-modal-close]');
  var confirmExitBar       = modal.querySelector('[data-otp-confirm-exit]');
  var exitConfirmButton    = modal.querySelector('[data-otp-exit-confirm]');
  var exitCancelButton     = modal.querySelector('[data-otp-exit-cancel]');
  var resumeBanner         = modal.querySelector('[data-otp-resume-banner]');
  var resumeContinueButton = modal.querySelector('[data-otp-resume-continue]');
  var resumeResetButton    = modal.querySelector('[data-otp-resume-reset]');

  var allSteps = [phoneStep, existingPasswordStep, codeStep, passwordStep].filter(Boolean);

  var closeTimer     = 0;
  var countdownTimer = 0;
  var redirectTo     = '';
  var currentPhone   = '';
  var currentStep    = 'phone';
  var isForced       = false;
  var passwordToken  = '';
  var lastTrigger    = null;
  var resendSeconds  = parseInt(window.wmOtpData.resendSeconds, 10) || 60;
  var CLOSE_ANIMATION_MS = 240;

  /* ── Password strength helpers (mirror of wm_otp_password_is_strong) ── */

  function strengthScore(password) {
    var p = password || '';
    var classes = 0;
    if (/\p{L}/u.test(p)) { classes++; }
    if (/\p{N}/u.test(p)) { classes++; }
    if (/[^\p{L}\p{N}]/u.test(p)) { classes++; }
    return Math.max(0, Math.min(3, classes));
  }

  function isStrongPassword(password) {
    var p = password || '';
    if (p.length < 8) { return false; }
    return strengthScore(p) >= 2;
  }

  /* ── digit helpers (Persian/Arabic → ASCII) ── */

  function normalizeDigits(value) {
    return String(value || '')
      .replace(/[۰-۹]/g, function (d) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)); })
      .replace(/[٠-٩]/g, function (d) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)); })
      .replace(/[^0-9]/g, '');
  }

  /* ── sessionStorage helpers ── */

  function savePhone(phone) {
    try { sessionStorage.setItem(SESSION_KEY, phone); } catch(e) {}
  }

  function clearPhone() {
    try { sessionStorage.removeItem(SESSION_KEY); } catch(e) {}
  }

  function getSavedPhone() {
    try { return sessionStorage.getItem(SESSION_KEY) || ''; } catch(e) { return ''; }
  }

  /* ── UI helpers ── */

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
    currentStep = step;
    allSteps.forEach(function (stepEl) {
      stepEl.hidden = stepEl.getAttribute('data-otp-step') !== step;
      showError(stepEl, '');
    });

    if (confirmExitBar) { confirmExitBar.hidden = true; }

    if (step !== 'phone' && currentPhone) {
      savePhone(currentPhone);
    }

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

  function doClose() {
    if (modal.hidden || isForced) { return; }
    if (confirmExitBar) { confirmExitBar.hidden = true; }
    if (window.wmFocusTrap) {
      window.wmFocusTrap.release();
    }
    modal.classList.remove('is-open');
    document.body.classList.remove('wm-otp-modal-open');
    window.clearTimeout(closeTimer);
    closeTimer = window.setTimeout(function () { modal.hidden = true; }, CLOSE_ANIMATION_MS);
    window.clearInterval(countdownTimer);
  }

  function refreshNonce(callback) {
    var body = new window.URLSearchParams();
    body.set('action', 'wm_otp_get_nonce');
    window.fetch(window.wmOtpData.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.success && data.data && data.data.nonce) {
          window.wmOtpData.nonce = data.data.nonce;
        }
        callback();
      })
      .catch(function () { callback(); });
  }

  function openModal(forced) {
    window.clearTimeout(closeTimer);
    isForced = !!forced;
    modal.classList.toggle('wm-otp-modal--forced', isForced);
    modal.hidden = false;
    document.body.classList.add('wm-otp-modal-open');
    if (window.wmFocusTrap) {
      window.wmFocusTrap.trap(modal, lastTrigger);
    }
    window.requestAnimationFrame(function () { modal.classList.add('is-open'); });
    setStep('phone');

    var saved = getSavedPhone();
    if (saved && phoneInput && resumeBanner) {
      phoneInput.value = saved;
      setPhoneDisplay(saved);
      resumeBanner.hidden = false;
    } else if (resumeBanner) {
      resumeBanner.hidden = true;
    }

    refreshNonce(function () {});
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

  /* ── Close / confirm-exit bar ── */

  closers.forEach(function (el) {
    el.addEventListener('click', function () {
      if (isForced) { return; }
      if (currentStep === 'phone') {
        doClose();
      } else if (confirmExitBar) {
        confirmExitBar.hidden = false;
      }
    });
  });

  if (exitConfirmButton) {
    exitConfirmButton.addEventListener('click', function () {
      clearPhone();
      doClose();
    });
  }

  if (exitCancelButton) {
    exitCancelButton.addEventListener('click', function () {
      if (confirmExitBar) { confirmExitBar.hidden = true; }
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) {
      if (isForced) { return; }
      if (currentStep === 'phone') {
        doClose();
      } else if (confirmExitBar) {
        confirmExitBar.hidden = false;
      }
    }
  });

  /* ── Resume banner ── */

  if (resumeContinueButton) {
    resumeContinueButton.addEventListener('click', function () {
      if (resumeBanner) { resumeBanner.hidden = true; }
      if (phoneForm && phoneInput && phoneInput.value.trim()) {
        phoneForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
      }
    });
  }

  if (resumeResetButton) {
    resumeResetButton.addEventListener('click', function () {
      clearPhone();
      if (resumeBanner) { resumeBanner.hidden = true; }
      if (phoneInput) { phoneInput.value = ''; }
    });
  }

  /* ── Triggers ── */

  triggers.forEach(function (trigger) {
    trigger.addEventListener('click', function (event) {
      event.preventDefault();
      lastTrigger = trigger;
      redirectTo = trigger.getAttribute('data-wm-otp-redirect') || window.location.href;
      openModal(trigger.hasAttribute('data-wm-otp-force'));
    });
  });

  var autoTriggerEl = document.querySelector('[data-wm-otp-autotrigger]');
  if (autoTriggerEl) {
    lastTrigger = autoTriggerEl;
    redirectTo = autoTriggerEl.getAttribute('data-wm-otp-redirect') || window.location.href;
    openModal(autoTriggerEl.hasAttribute('data-wm-otp-force'));
  }

  /* ── Step 1: Phone check ── */

  if (phoneForm) {
    phoneForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var phone = phoneInput.value.trim();
      if (!phone) { return; }

      if (resumeBanner) { resumeBanner.hidden = true; }

      var btn = phoneForm.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.classList.add('is-loading');

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
          btn.classList.remove('is-loading');
          btn.disabled = false;

          if (!data || !data.success) {
            showError(phoneStep, (data && data.data && data.data.message) || 'بررسی شماره موبایل ناموفق بود.');
            return;
          }

          currentPhone = phone;
          setPhoneDisplay(phone);

          if (data.data && data.data.exists && !data.data.incompleteRegistration) {
            setStep('existing-password');
          } else {
            setStep('code');
            startResendCountdown();
          }
        })
        .catch(function () {
          btn.classList.remove('is-loading');
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
      btn.classList.add('is-loading');

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
            clearPhone();
            window.location.href = data.data.redirect;
            return;
          }
          btn.classList.remove('is-loading');
          btn.disabled = false;
          showError(existingPasswordStep, (data && data.data && data.data.message) || 'ورود ناموفق بود.');
        })
        .catch(function () {
          btn.classList.remove('is-loading');
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

  /* ── Phone + code input UX ── */

  if (phoneInput) {
    phoneInput.addEventListener('input', function () {
      phoneInput.value = normalizeDigits(phoneInput.value);
    });
  }

  if (codeInput && codeForm) {
    codeInput.addEventListener('input', function () {
      codeInput.value = normalizeDigits(codeInput.value);
      // Auto-submit as soon as the full 5-digit code is entered/pasted.
      if (codeInput.value.length === 5) {
        codeForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
      }
    });
  }

  // Password visibility toggles.
  document.querySelectorAll('[data-otp-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      var input = document.getElementById(button.getAttribute('data-otp-password-toggle'));
      if (!input) { return; }
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.setAttribute('aria-label', show ? 'پنهان کردن رمز عبور' : 'نمایش رمز عبور');
      button.classList.toggle('is-visible', show);
    });
  });

  /* ── Step 2b: OTP code verification ── */

  if (codeForm) {
    codeForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var code = codeInput.value.trim();
      if (!currentPhone || !code) { return; }

      var btn = codeForm.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.classList.add('is-loading');

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
            btn.classList.remove('is-loading');
            btn.disabled = false;
            passwordToken = data.data.passwordToken || '';
            setStep('password');
            return;
          }
          if (data && data.success && data.data && data.data.redirect) {
            clearPhone();
            window.location.href = data.data.redirect;
            return;
          }
          btn.classList.remove('is-loading');
          btn.disabled = false;
          showError(codeStep, (data && data.data && data.data.message) || 'کد وارد شده نادرست یا منقضی شده است.');
        })
        .catch(function () {
          btn.classList.remove('is-loading');
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
      if (!isStrongPassword(password)) {
        showError(passwordStep, 'رمز عبور باید حداقل ۸ کاراکتر باشد و ترکیبی از حروف با عدد یا نماد داشته باشد.');
        return;
      }

      var btn = passwordForm.querySelector('button[type="submit"]');
      btn.disabled = true;
      btn.classList.add('is-loading');

      var body = new window.URLSearchParams();
      body.set('action', 'wm_otp_set_password');
      body.set('password', password);
      body.set('redirect_to', redirectTo);
      if (passwordToken) {
        body.set('passwordToken', passwordToken);
      } else {
        body.set('nonce', window.wmOtpData.nonce);
      }

      window.fetch(window.wmOtpData.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.success && data.data && data.data.redirect) {
            passwordToken = '';
            clearPhone();
            window.location.href = data.data.redirect;
            return;
          }
          btn.classList.remove('is-loading');
          btn.disabled = false;
          showError(passwordStep, (data && data.data && data.data.message) || 'ثبت رمز عبور ناموفق بود.');
        })
        .catch(function () {
          btn.classList.remove('is-loading');
          btn.disabled = false;
          showError(passwordStep, 'خطا در ارتباط با سرور. دوباره تلاش کنید.');
        });
    });
  }

  /* Live strength meter on the new-password step */

  if (newPasswordInput && strengthEl) {
    newPasswordInput.addEventListener('input', function () {
      var score = strengthScore(newPasswordInput.value);
      var empty = newPasswordInput.value.length === 0;

      strengthEl.hidden = empty;
      strengthEl.setAttribute('data-strength', String(score));
    });
  }

  /* ── Back buttons ── */

  backButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      window.clearInterval(countdownTimer);
      clearPhone();
      passwordToken = '';
      setStep('phone');
    });
  });

})();
