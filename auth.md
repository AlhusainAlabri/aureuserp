Admin username: nodhumtech@gmail.com
password: Oman@999

## Local environment (agents)

Database:
- Name: `nodhum_erp`
- User: `root`
- Password: `hgpsdkk`

Admin panel login: use the credentials above (email + password in this file).


Site: http://erp.zseeb.om
Admin: http://erp.zseeb.om/admin/login
Temporary by IP: http://192.168.100.16/admin/login



Production (192.168.100.16): set `HIDE_PLUGINS_UI=true` in server `.env` to hide `/admin/plugins` from all users. Manage plugins via SSH:

php artisan plugins:install
php artisan plugins:install --all
php artisan plugins:install --only=products --only=accounts
php artisan plugins:install --stop-on-error
php artisan plugins:install --include-core


7.	إدارة المخازن:
•	تسجيل المواد المخزنة والمستخدمة.
•	تتبع الكميات / التواريخ / الموردين (ممكن يكون اكثر من مورد)/ الأسعار.
•	تنبيهات بانخفاض المخزون.
•	تقارير دورية حركة المخزون. 
•	إدارة الأصول و الاستعارة و تنبيهات
o	ممكن نعدد قيمه – مبلغ لكل اصل


# If `erp:install` fails with `Duplicate entry ... role_has_permissions`
# the DB is in a partial state (no `storage/installed`, but roles/permissions exist).
# Do NOT re-run `erp:install` alone — wipe first (steps 1–2), then reinstall.

# 1) Wipe the database completely
php artisan migrate:fresh --force
# 2) Remove the "already installed" marker
rm -f storage/installed
# 3) Reinstall core ERP (admin, roles, permissions, seeders)
php artisan erp:install --no-interaction \
  --admin-name="Admin" \
  --admin-email="nodhumtech@gmail.com" \
  --admin-password="Oman@999"
# 4) Install all plugins (respects dependencies)
php artisan plugins:install --all --include-core --no-interaction --stop-on-error
# 5) Clear caches
php artisan optimize:clear
php artisan queue:restart
