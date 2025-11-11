# Leave & Civil Form Hosting Fix - Summary

## Fixed Files

All leave and civil form files have been updated to work in both local (XAMPP) and hosted environments. The main issue was hardcoded `/capstone/` paths that won't work when hosted on different domains or subdirectories.

### Employee Files Fixed
1. **employee/civil form.html**
   - Changed all `/capstone/api/` to `../api/`
   - Updated dashboard redirect from `/capstone/employee/dashboard.php` to `dashboard.php`
   - Fixed signature loading to work with relative paths
   - Updated current_user, employee_info, dept_heads, employee_signature, and submit_leave endpoints

2. **employee/apply_leave.html**
   - Fixed employee_leave_history API call
   - Updated current_user endpoint for user email resolution
   - Fixed employee_leave_credits API endpoint

3. **employee/leave_history.html**
   - Updated employee_leave_history API endpoint to use relative path

### HR Files Fixed
1. **hr/civil_form.php**
   - Fixed employee_signature API loading
   - Updated save_signature API endpoint
   - Fixed signature usage endpoint

2. **hr/leave_request.html**
   - Updated get_hr_leave_requests endpoint
   - Fixed get_leave_requests endpoint
   - Updated update_leave_status endpoint (decline & approve)
   - Fixed add_notification endpoint for employee notifications
   - Updated current_user endpoint for HR name fetching
   - Fixed update_hr_section7 endpoint
   - Updated civil_form preview URL to use relative path

### Department Head Files Fixed
1. **dept_head/leave-request.html**
   - Fixed get_leave_requests and current_user endpoints
   - Updated get_users endpoint for relief officer dropdown
   - Fixed update_leave_status endpoint (recall, approve, decline)
   - Updated add_notification endpoint
   - Fixed viewForm redirect to use relative path
   - Updated current_user endpoint for dept head name
   - Fixed update_hr_section7 endpoint and preview URL
   - **Used PowerShell regex replace to fix all remaining /capstone/api/ references**

## Changes Made

### API Path Pattern
- **Before:** `/capstone/api/endpoint.php`
- **After:** `../api/endpoint.php`

### Dashboard/Form Redirects
- **Before:** `/capstone/employee/dashboard.php`
- **After:** `dashboard.php` (relative to current directory)

- **Before:** `/capstone/dept_head/civil_form.php?id=123`
- **After:** `civil_form.php?id=123` OR `../dept_head/civil_form.php?id=123`

## Why This Works

Relative paths (`../api/`) work in both scenarios:

1. **Local XAMPP:**
   - URL: `http://localhost/capstone/employee/civil%20form.html`
   - `../api/current_user.php` resolves to `http://localhost/capstone/api/current_user.php` ✓

2. **Hosted (example.com/capstone/):**
   - URL: `https://example.com/capstone/employee/civil%20form.html`
   - `../api/current_user.php` resolves to `https://example.com/capstone/api/current_user.php` ✓

3. **Hosted (subdomain.example.com/):**
   - URL: `https://subdomain.example.com/employee/civil%20form.html`
   - `../api/current_user.php` resolves to `https://subdomain.example.com/api/current_user.php` ✓

## Browser Compatibility

All changes use standard fetch API and relative URLs, which are supported by:
- ✅ Chrome 42+
- ✅ Firefox 39+
- ✅ Edge 14+
- ✅ Safari 10.1+
- ✅ Opera 29+
- ✅ Mobile browsers (iOS Safari 10.3+, Chrome Android)

## Testing Checklist

### Local Testing (XAMPP)
- [ ] Employee civil form loads user data correctly
- [ ] Employee can submit leave applications
- [ ] Employee leave history displays correctly
- [ ] HR can view and approve/decline leave requests
- [ ] HR can edit Section 7 and upload signatures
- [ ] Department Head can view pending requests
- [ ] Department Head can approve/decline requests
- [ ] Department Head can recall approved leaves
- [ ] All forms display signature images correctly

### Hosted Testing
- [ ] Upload all files to hosting server
- [ ] Ensure directory structure matches: `/api/`, `/employee/`, `/hr/`, `/dept_head/`
- [ ] Test same checklist as local
- [ ] Verify CORS settings (if API is on different domain)
- [ ] Check database connection settings in `db.php`

## API Endpoints Fixed

All these endpoints now use relative paths and work in any environment:

### Employee APIs
- `../api/current_user.php` - Get logged-in user data
- `../api/employee_info.php` - Get employee information
- `../api/employee_leave_credits.php` - Get leave credit balances
- `../api/employee_leave_history.php` - Get employee's leave history
- `../api/employee_signature.php` - Get saved e-signature
- `../api/dept_heads.php` - Get department heads list
- `../api/submit_leave.php` - Submit leave application
- `../api/save_signature.php` - Save e-signature

### HR APIs
- `../api/get_hr_leave_requests.php` - Get leaves pending HR approval
- `../api/get_leave_requests.php` - Get all leave requests
- `../api/update_leave_status.php` - Approve/decline leaves
- `../api/add_notification.php` - Send notifications to employees
- `../api/update_hr_section7.php` - Update HR section & signatures

### Department Head APIs
- `../api/get_leave_requests.php` - Get leave requests for dept
- `../api/current_user.php` - Get dept head info
- `../api/get_users.php` - Get users for relief officer selection
- `../api/update_leave_status.php` - Approve/decline/recall
- `../api/update_hr_section7.php` - Update dept head signature

## Common Issues & Solutions

### Issue: "Failed to fetch" errors
**Solution:** Ensure the `api/` directory exists at the same level as `employee/`, `hr/`, and `dept_head/`

### Issue: Signature images not loading
**Solution:** Check that `uploads/signatures/` directory has proper read permissions (755)

### Issue: Data not loading on hosted server
**Solution:** 
1. Check `db.php` connection settings
2. Verify PHP session is enabled on hosting
3. Check PHP error logs for specific errors

### Issue: Cross-Origin (CORS) errors
**Solution:** If hosting API separately, add CORS headers to PHP files:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

## Database Considerations

The API endpoints (`api/employee_leave_history.php`, etc.) remain unchanged and work correctly because:
1. They use session data to identify users
2. They return data in JSON format
3. No hardcoded paths in SQL queries or PHP logic

## Files NOT Modified

These files don't need changes as they don't make API calls:
- Database SQL files
- Pure backend PHP files without fetch calls
- Image/asset files

## Next Steps

1. **Test Locally:** Run through the testing checklist on XAMPP
2. **Upload to Hosting:** Use FTP/FileZilla to upload all files
3. **Configure Database:** Update `db.php` with hosting database credentials
4. **Test on Hosting:** Run through testing checklist again
5. **Monitor Logs:** Check PHP error logs for any issues

## Support

If you encounter issues:
1. Check browser console (F12) for JavaScript errors
2. Check PHP error logs on server
3. Verify all API endpoints return valid JSON
4. Test with different browsers to rule out browser-specific issues

---

**Last Updated:** November 8, 2025
**Fixed By:** AI Assistant
**Status:** ✅ All critical paths updated to relative URLs
