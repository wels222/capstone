FOLDER STRUCTURE &  FILE CONTENTS with description

-------------------------------------------------------------------------------------------------

1. Services - raw php no C++, query ng mga db

- Fingerprint Reader
    - SEARCH employee_id with id RETURNED FROM C++
    - GET employee_id DETAILS
- TIME-IN/OUT
    - QUERY latest employee_id IN attendance WHERE data-today (with the date today/created by), Figureout if TIME-IN/OUT not NULL ? time-in null, let the service be time-in, else time-out, POST the timestamp of time-in/out service to attendance table
- REGISTER-FINGERPRINT
    - JSONify the details or IMPORT the details → once the details is done POST to fingerprint table ALONG with .bmp
    - SEARCH employee - QUERY where employee_id || Email, return the fields JSON it import it to reigister


SPECIFIC PHP FILE CALLOUTS
_____________________________________________________________________________________________________

- Fingerprint Reader
    - reader_identify_user_php ? if user identified → return modal : register page
    - reader_get_details.php
- TIMEIN/OUT
    - attendance_get_status.php (return time-out null, results null) ? check mo muna if user exists in db
    - attendance_post_status.php (POST timestamp of in/out with current date)
- REGISTER-FINGERPRINT
    - register_import_data : QUERY from forms, return details or not exists
    - register_user_data : POST to table fingerprint of USER data



------------------------------------------------------------------------------------------------------

2. API - specific c++ api for fingerprint

    ZKTeco API Endpoints :
        APPLICATION :

            - application_start_server.php :
                - FUNCTION : app.run(minimized) || background-task →starts the C++ server
                - METHOD : app.run() or php exec()

            - application_fetch_port.php :
                - FUNCTION :  get info_list_crow.txt port
                - METHOD : file read in path ./server/crow_server_info.txt

            - application_close_port.php :
                - FUNCTION :  end server c+++ on background
                - METHOD :  app.close() || app.kill()

        FINGERPRINT :

            - fingerprint_connect_device.php
                - ENPOINT : /api/device/connect
                - METHOD : GET
                - FUNCTION : connect_device()
                - DESCRIPTION : initialize FIngerprint Scanner Connection

            - fingerprint_disconnect_device.php :
                - ENPOINT : /api/device/disconnect
                - METHOD : GET
                - FUNCTION : disconnect_device()
                - DESCRIPTION : closes FIngerprint Scanner Connection

            - fingerprint_read_finger.php :
                - ENPOINT : /api/device/read
                - METHOD : GET
                - FUNCTION :  read_fingerprint()
                - DESCRIPTION : Awaits fingerprint input, exports fingerprint as bmp

            - fingerprint_free_current.php :
                - ENPOINT : /api/device/free
                - METHOD : GET
                - FUNCTION : free_template()
                - DESCRIPTION : free current fingerprint read, Call this after a succesful read

            - fingerprint_fetch_templates.php :
                - ENPOINT : /api/fingerprint/fetch
                - METHOD : POST
                - FUNCTION : fetch_fingerprints()
                - DESCRIPTION : use PHP to get all template in fingerprint table, convert to base64 and POST to endpoint

            - fingerprint_identify_user.php
                - ENPOINT : /api/fingerprint/id
                - METHOD : GET
                - FUNCTION : retrieved_fingerprintID()
                - DESCRIPTION : identify the id of user fingerprint after fetching all templates,  returns <int> id or null (user !exists)

            - fingerprint_free_memory.php
                - ENPOINT : /api/fingerprint/free
                - METHOD : GET
                - FUNCTION : free_all_templates()
                - DESCRIPTION :free all templates recieved from SQL fingerprint table
        
------------------------------------------------------------------------------------------------------

3. Server - standalone exe with .dll file

- FingerprintReader.exe - C++ server application
- crow_server_info.txt - returns server port of the application
- libzkfp.dll - contains ZKTeco sdk functions
- fingerprint.bmp - current stored image file of fingeprint, will be use for pushing as a template in SQL
------------------------------------------------------------------------------------------------------

4. Components 
    Fingerprint_Reader
    - attendance_modal.php


------------------------------------------------------------------------------------------------------

ROUTES (urls): 

- Fingerprint Reader page : (http://localhost/capstone/fingerprint/components/Fingerprint_Reader/scanner_page.php)
- Register Fingerprint :  (http://localhost/capstone/registerFP)

-lagay nyo nalang folder structure nyo
----------------------------------------------------------------------------------------------

CREDITS DEVELOPER : JR PINAKAMALUPET