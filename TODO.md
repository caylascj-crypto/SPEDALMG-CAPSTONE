# CapstoneV2 Fix: ERR_EMPTY_RESPONSE on login

## Completed
- Updated `CapstoneV2/ADMIN_FILES/ADMIN_BACKEND/login_screen_submit.php` to return JSON errors instead of failing silently.
- Updated `CapstoneV2/ADMIN_FILES/ADMIN_BACKEND/db.php` to temporarily enable PHP error display for debugging.
- Verified PHP syntax for the modified files via `php -l`.

## Next steps
1. Trigger login again and capture the JSON error response from the browser console/network.
2. If the error points to DB connection/table issues, run/adjust DB setup (or align schema) so `admin_accounts` and teacher DB dependencies are consistent.
3. After root cause is fixed, optionally revert `display_errors`/`error_reporting` back to production-safe settings.

