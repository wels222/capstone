-- Seed: Department Head Attendance Only
-- Date range: 2025-10-21 .. 2025-11-19 (inclusive)
-- Absences are represented by missing rows (no insert on Absent days)

USE `capstone`;

INSERT INTO attendance (employee_id, date, time_in, time_out, time_in_status, time_out_status, status, notes)
SELECT
    s.employee_id,
    s.date,
    CASE
        WHEN s.in_status = 'Absent' THEN NULL
        WHEN s.in_status = 'Late' THEN CONCAT(s.date, ' 08:20:00')
        WHEN s.in_status = 'Undertime' THEN CONCAT(s.date, ' 09:30:00')
        ELSE CONCAT(s.date, ' 07:45:00')
    END AS time_in,
    CASE
        WHEN s.in_status = 'Absent' THEN NULL
        WHEN s.out_status = 'Undertime' THEN CONCAT(s.date, ' 16:00:00')
        WHEN s.out_status = 'On-time' THEN CONCAT(s.date, ' 17:00:00')
        WHEN s.out_status = 'Overtime' THEN CONCAT(s.date, ' 18:30:00')
        ELSE CONCAT(s.date, ' 17:10:00')
    END AS time_out,
    s.in_status AS time_in_status,
    CASE WHEN s.in_status = 'Absent' THEN 'Out' ELSE s.out_status END AS time_out_status,
    CASE WHEN s.in_status = 'Absent' THEN 'Absent' ELSE 'Present' END AS status,
    CASE WHEN s.in_status = 'Absent' THEN 'Absent' ELSE NULL END AS notes
FROM (
    SELECT
        u.employee_id,
        t.d AS date,
        -- derive a safe numeric seed from employee_id (handles formats like 'EMP-2025-000003')
        MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) AS in_offset,
        MOD(MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) * 3, 8) AS out_offset,
        MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 8), 3) AS var_in,
        MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 8), 4) AS var_out,
        MOD(t.day_no - 1 + MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8), 8) AS c_in,
        MOD(t.day_no - 1 + MOD(MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) * 3, 8), 8) AS c_out,
        -- compute final in/out statuses with per-employee variations
        CASE
            WHEN MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 8), 3) = 1 THEN
                CASE MOD(t.day_no - 1 + MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8), 8)
                    -- variant 1: swap Late and Undertime
                    WHEN 0 THEN 'Present'
                    WHEN 1 THEN 'Undertime'
                    WHEN 2 THEN 'Present'
                    WHEN 3 THEN 'Late'
                    WHEN 4 THEN 'Present'
                    WHEN 5 THEN 'Undertime'
                    WHEN 6 THEN 'Present'
                    WHEN 7 THEN 'Absent'
                END
            WHEN MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 8), 3) = 2 THEN
                CASE MOD(t.day_no - 1 + MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8), 8)
                    -- variant 2: swap Present and Late
                    WHEN 0 THEN 'Late'
                    WHEN 1 THEN 'Present'
                    WHEN 2 THEN 'Late'
                    WHEN 3 THEN 'Undertime'
                    WHEN 4 THEN 'Late'
                    WHEN 5 THEN 'Present'
                    WHEN 6 THEN 'Late'
                    WHEN 7 THEN 'Absent'
                END
            ELSE
                CASE MOD(t.day_no - 1 + MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8), 8)
                    -- base: P, L, P, U, P, L, P, A
                    WHEN 0 THEN 'Present'
                    WHEN 1 THEN 'Late'
                    WHEN 2 THEN 'Present'
                    WHEN 3 THEN 'Undertime'
                    WHEN 4 THEN 'Present'
                    WHEN 5 THEN 'Late'
                    WHEN 6 THEN 'Present'
                    WHEN 7 THEN 'Absent'
                END
        END AS in_status,
        CASE
            WHEN MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 8), 4) = 1 THEN
                CASE MOD(t.day_no - 1 + MOD(MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) * 3, 8), 8)
                    -- variant 1: swap On-time and Out
                    WHEN 0 THEN 'Out'
                    WHEN 1 THEN 'Undertime'
                    WHEN 2 THEN 'Overtime'
                    WHEN 3 THEN 'Out'
                    WHEN 4 THEN 'On-time'
                    WHEN 5 THEN 'Overtime'
                    WHEN 6 THEN 'Out'
                    WHEN 7 THEN 'Undertime'
                END
            WHEN MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 8), 4) = 2 THEN
                CASE MOD(t.day_no - 1 + MOD(MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) * 3, 8), 8)
                    -- variant 2: rotate On-time->Undertime->Overtime->Out->On-time
                    WHEN 0 THEN 'Undertime'
                    WHEN 1 THEN 'Overtime'
                    WHEN 2 THEN 'Out'
                    WHEN 3 THEN 'Undertime'
                    WHEN 4 THEN 'On-time'
                    WHEN 5 THEN 'Out'
                    WHEN 6 THEN 'Undertime'
                    WHEN 7 THEN 'Overtime'
                END
            WHEN MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 8), 4) = 3 THEN
                CASE MOD(t.day_no - 1 + MOD(MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) * 3, 8), 8)
                    -- variant 3: swap Undertime and Overtime
                    WHEN 0 THEN 'On-time'
                    WHEN 1 THEN 'Overtime'
                    WHEN 2 THEN 'Undertime'
                    WHEN 3 THEN 'On-time'
                    WHEN 4 THEN 'Out'
                    WHEN 5 THEN 'Undertime'
                    WHEN 6 THEN 'On-time'
                    WHEN 7 THEN 'Overtime'
                END
            ELSE
                CASE MOD(t.day_no - 1 + MOD(MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) * 3, 8), 8)
                    -- base: OT, UT, OTm, OT, Out, OTm, OT, UT
                    WHEN 0 THEN 'On-time'
                    WHEN 1 THEN 'Undertime'
                    WHEN 2 THEN 'Overtime'
                    WHEN 3 THEN 'On-time'
                    WHEN 4 THEN 'Out'
                    WHEN 5 THEN 'Overtime'
                    WHEN 6 THEN 'On-time'
                    WHEN 7 THEN 'Undertime'
                END
        END AS out_status
    FROM users u
    JOIN (
        SELECT 1 AS day_no, CAST('2025-10-21' AS DATE) AS d
        UNION ALL SELECT 2, CAST('2025-10-22' AS DATE)
        UNION ALL SELECT 3, CAST('2025-10-23' AS DATE)
        UNION ALL SELECT 4, CAST('2025-10-24' AS DATE)
        UNION ALL SELECT 5, CAST('2025-10-25' AS DATE)
        UNION ALL SELECT 6, CAST('2025-10-26' AS DATE)
        UNION ALL SELECT 7, CAST('2025-10-27' AS DATE)
        UNION ALL SELECT 8, CAST('2025-10-28' AS DATE)
        UNION ALL SELECT 9, CAST('2025-10-29' AS DATE)
        UNION ALL SELECT 10, CAST('2025-10-30' AS DATE)
        UNION ALL SELECT 11, CAST('2025-10-31' AS DATE)
        UNION ALL SELECT 12, CAST('2025-11-01' AS DATE)
        UNION ALL SELECT 13, CAST('2025-11-02' AS DATE)
        UNION ALL SELECT 14, CAST('2025-11-03' AS DATE)
        UNION ALL SELECT 15, CAST('2025-11-04' AS DATE)
        UNION ALL SELECT 16, CAST('2025-11-05' AS DATE)
        UNION ALL SELECT 17, CAST('2025-11-06' AS DATE)
        UNION ALL SELECT 18, CAST('2025-11-07' AS DATE)
        UNION ALL SELECT 19, CAST('2025-11-08' AS DATE)
        UNION ALL SELECT 20, CAST('2025-11-09' AS DATE)
        UNION ALL SELECT 21, CAST('2025-11-10' AS DATE)
        UNION ALL SELECT 22, CAST('2025-11-11' AS DATE)
        UNION ALL SELECT 23, CAST('2025-11-12' AS DATE)
        UNION ALL SELECT 24, CAST('2025-11-13' AS DATE)
        UNION ALL SELECT 25, CAST('2025-11-14' AS DATE)
        UNION ALL SELECT 26, CAST('2025-11-15' AS DATE)
        UNION ALL SELECT 27, CAST('2025-11-16' AS DATE)
        UNION ALL SELECT 28, CAST('2025-11-17' AS DATE)
        UNION ALL SELECT 29, CAST('2025-11-18' AS DATE)
        UNION ALL SELECT 30, CAST('2025-11-19' AS DATE)
    ) AS t ON 1=1
    WHERE u.email = 'hr@g.com' AND u.status = 'approved'
 ) AS s
ON DUPLICATE KEY UPDATE
    time_in=VALUES(time_in),
    time_out=VALUES(time_out),
    time_in_status=VALUES(time_in_status),
    time_out_status=VALUES(time_out_status),
    status=VALUES(status),
    notes=VALUES(notes);
