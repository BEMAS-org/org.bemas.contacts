# Changelog

## 1.1.0

* Fix: use `mb_substr()` instead of `substr()` when truncating the function/job-title label written to the contact's Main Address `city` field, so a multi-byte character (e.g. an en dash) near the 64-character limit no longer gets cut mid-character. That malformed value caused the `Address.create` insert to fail, which silently poisoned the enclosing transaction and made the whole contact import crash.

## 1.0

* Initial release