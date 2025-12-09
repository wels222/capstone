rem Proxy the database
rem ssh -i doel.rsa -vNR 127.0.0.1:3306:127.0.0.1:3306 doel@mabinihub.org
ssh -i doel.rsa -vNL 127.0.0.1:3307:127.0.0.1:3306 doel@mabinihub.org