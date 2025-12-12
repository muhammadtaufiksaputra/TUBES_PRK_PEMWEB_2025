<?php
/**
 * Force logout - clear session cookie
 */

// Set session cookie ke expired
setcookie('PHPSESSID', '', time() - 3600, '/');

// Clear remember_token cookie
setcookie('remember_token', '', time() - 3600, '/', '', false, true);

echo "✅ Session cookies cleared!\n\n";
echo "Sekarang silakan:\n";
echo "1. Refresh browser (F5)\n";
echo "2. Akan redirect ke login\n";
echo "3. Login ulang dengan: organizer1@gmail.com\n";
