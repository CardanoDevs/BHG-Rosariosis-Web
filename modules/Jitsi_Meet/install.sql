/**
 * Install SQL
 * Required if the module adds programs to other modules
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Jitsi_Meet module
 */

-- Fix #102 error language "plpgsql" does not exist
-- http://timmurphy.org/2011/08/27/create-language-if-it-doesnt-exist-in-postgresql/
--
-- Name: create_language_plpgsql(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION create_language_plpgsql()
RETURNS BOOLEAN AS $$
    CREATE LANGUAGE plpgsql;
    SELECT TRUE;
$$ LANGUAGE SQL;

SELECT CASE WHEN NOT (
    SELECT TRUE AS exists FROM pg_language
    WHERE lanname='plpgsql'
    UNION
    SELECT FALSE AS exists
    ORDER BY exists DESC
    LIMIT 1
) THEN
    create_language_plpgsql()
ELSE
    FALSE
END AS plpgsql_created;

DROP FUNCTION create_language_plpgsql();


/**
 * profile_exceptions Table
 *
 * profile_id:
 * - 0: student
 * - 1: admin
 * - 2: teacher
 * - 3: parent
 * modname: should match the Menu.php entries
 * can_use: 'Y'
 * can_edit: 'Y' or null (generally null for non admins)
 */
--
-- Data for Name: profile_exceptions; Type: TABLE DATA;
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Jitsi_Meet/Configuration.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Configuration.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Jitsi_Meet/Rooms.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Rooms.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Jitsi_Meet/Rooms.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Rooms.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Jitsi_Meet/Meet.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Meet.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 2, 'Jitsi_Meet/Meet.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Meet.php'
    AND profile_id=2);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 3, 'Jitsi_Meet/Meet.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Meet.php'
    AND profile_id=3);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 0, 'Jitsi_Meet/Meet.php', 'Y', null
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Jitsi_Meet/Meet.php'
    AND profile_id=0);


/**
 * config Table
 *
 * title: for ex.: 'JITSI_MEET_[your_config]'
 * value: string
 */
--
-- Data for Name: config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_DOMAIN', 'meet.jit.si'
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_DOMAIN');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_TOOLBAR', 'microphone,camera,hangup,desktop,fullscreen,profile,chat,recording,settings,raisehand,videoquality,tileview'
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_TOOLBAR');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_SETTINGS', 'devices,language'
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_SETTINGS');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_WIDTH', '100%'
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_WIDTH');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_HEIGHT', '700'
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_HEIGHT');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_BRAND_WATERMARK_LINK', NULL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_BRAND_WATERMARK_LINK');

INSERT INTO config (school_id, title, config_value)
SELECT '0', 'JITSI_MEET_DISABLE_VIDEO_QUALITY_LABEL', NULL
WHERE NOT EXISTS (SELECT title
    FROM config
    WHERE title='JITSI_MEET_DISABLE_VIDEO_QUALITY_LABEL');


/**
 * Add module tables
 */

/**
 * Jitsi_Meet Rooms table
 */
--
-- Name: jitsi_meet_rooms; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_jitsi_meet_rooms() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname = CURRENT_SCHEMA()
        AND tablename = 'jitsi_meet_rooms') THEN
    RAISE NOTICE 'Table "jitsi_meet_rooms" already exists.';
    ELSE
        CREATE TABLE jitsi_meet_rooms (
            id serial PRIMARY KEY,
            -- school_id integer NOT NULL,
            syear numeric(4,0) NOT NULL,
            title text NOT NULL,
            subject text,
            password text,
            start_audio_only character(1),
            students text,
            users text,
            owner_id numeric,
            created_at timestamp DEFAULT current_timestamp
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_jitsi_meet_rooms();
DROP FUNCTION create_table_jitsi_meet_rooms();

