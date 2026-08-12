
## 2024-05-18 - Added aria-label to coupon code inputs
**Learning:** Found that custom Javascript-injected inputs for coupons did not include aria-labels, and native WooCommerce cart coupon inputs lacked them as well, which made screen-reader navigation difficult.
**Action:** Always add aria-labels to input fields, even when placeholder text is present, and ensure dynamic Javascript-injected HTML string components follow the same accessibility standards as PHP templates.
