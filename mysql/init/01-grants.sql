-- Run once, by the mysql image, when the data volume is first created.
--
-- CONVERT_TZ() with named zones (e.g. CONVERT_TZ(event_start_utc, 'GMT', timezone))
-- reads mysql.time_zone_name, which the app user cannot select from by default -
-- the conversion then silently returns NULL rather than erroring. CircleCI issues
-- this same grant before running the suite; doing it here keeps a local
-- from-scratch database consistent with CI.
GRANT SELECT ON mysql.time_zone_name TO 'restarters'@'%';
FLUSH PRIVILEGES;
