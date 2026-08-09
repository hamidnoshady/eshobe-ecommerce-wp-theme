## 2024-05-24 - [OTP Brute-Force Vulnerability]
**Vulnerability:** The OTP verification endpoint (`wm_ajax_otp_verify_code`) lacked rate limiting for failed attempts. An attacker could rapidly guess all 90,000 possible 5-digit codes within the 2-minute validity window.
**Learning:** While the endpoint properly limited the *sending* of OTPs via SMS, it failed to limit the *verification* attempts of a generated code. Security checks must exist at both generation and verification stages.
**Prevention:** Implement a transient-based counter to track failed verification attempts, explicitly deleting the OTP transient when the failure limit (e.g., 5 attempts) is reached.
