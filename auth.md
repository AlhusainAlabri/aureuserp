Admin username: nodhumtech@gmail.com
password: Oman@999

## Local environment (agents)

Database:
- Name: `nodhum_erp`
- User: `root`
- Password: `hgpsdkk`

Admin panel login: use the credentials above (email + password in this file).


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

