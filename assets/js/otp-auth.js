(function () {
  var modal = document.getElementById('wm-otp-modal');
  var triggers = document.querySelectorAll('[data-wm-otp-trigger]');

  if (!modal || !triggers.length || typeof window.wmOtpData === 'undefined') {
    return;
  }

  var phoneStep = modal.querySelector('[data-otp-step="phone"]');
  var codeStep = modal.querySelector('[data-otp-step="code"]');
  var phoneForm = modal.querySelector('[data-otp-phone-form]');
  var codeForm = modal.querySelector('[data-otp-code-form]');
  var phoneInput = modal.querySelector('#wm-otp-phone');
  var codeInput = modal.querySelector('#wm-otp-code');
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

  function showError(step, message) {
    var error = step.querySelector('[data-otp-error]');
    if (!error) {
      return;
    }
    error.textContent = message;
    error.hidden = !message;
  }

  function setStep(step) {
    phoneStep.hidden = step !== 'phone';
    codeStep.hidden = step !== 'code';
    showError(phoneStep, '');
    showError(codeStep, '');

    if (step === 'phone') {
      window.setTimeout(function () {
        phoneInput.focus();
      }, 30);
    } else {
      codeInput.value = '';
      window.setTimeout(function () {
        codeInput.focus();
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
    setStep('phone');
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

    window.clearTimeout(countdownTimer);
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

  function requestCode(phone, step) {
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
          showError(step, message);
          return false;
        }
        return true;
      })
      .catch(function () {
        showError(step, 'خطا در ارتباط با سرور. دوباره تلاش کنید.');
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
})();
