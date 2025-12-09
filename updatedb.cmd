rem dump users and fingerprints from local database
c:\xampp\mysql\bin\mysqldump.exe -uroot capstone users fingerprints > c:\xampp\htdocs\capstone\capstone.sql
pause

rem update users and fingerprints in the hosting
c:\xampp\mysql\bin\mysql -h127.0.0.1 -P3307 -uroot capstone < c:\xampp\htdocs\capstone\capstone.sql
pause