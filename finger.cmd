rem Stopping old server
curl http://localhost/capstone/fingerprint/api/application/application_close_server.php
timeout /t 2

rem Starting server
curl http://localhost/capstone/fingerprint/api/application/application_start_server.php
timeout /t 2

rem Established reverse proxy to 18080
ssh -i doel.rsa -vNR 127.0.0.1:18080:127.0.0.1:18080 doel@mabinihub.org