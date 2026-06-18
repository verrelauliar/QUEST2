-- Supabase RPC Function: execute_query
-- Generic SQL execution function for complex queries
-- Parameters: query TEXT, params JSONB (optional)
-- Returns: JSON array of results
-- Security: Blocks DDL/DCL operations

CREATE OR REPLACE FUNCTION execute_query(query TEXT, params JSONB DEFAULT '{}'::JSONB)
RETURNS JSON AS $$
DECLARE
    result JSON;
    query_upper TEXT;
    forbidden_keywords TEXT[] := ARRAY[
        'CREATE', 'DROP', 'ALTER', 'TRUNCATE', 'GRANT', 'REVOKE',
        'CREATE TABLE', 'DROP TABLE', 'ALTER TABLE', 'CREATE INDEX',
        'DROP INDEX', 'CREATE DATABASE', 'DROP DATABASE', 'CREATE USER',
        'DROP USER', 'CREATE ROLE', 'DROP ROLE', 'CREATE FUNCTION',
        'DROP FUNCTION', 'CREATE TRIGGER', 'DROP TRIGGER', 'CREATE VIEW',
        'DROP VIEW', 'CREATE SCHEMA', 'DROP SCHEMA'
    ];
    keyword TEXT;
BEGIN
    -- Remove comments to prevent bypass
    query := regexp_replace(query, '--.*$', '', 'gm');
    query := regexp_replace(query, '/\*.*?\*/', '', 'g');
    query := TRIM(query);

    -- Convert to uppercase for keyword checking
    query_upper := UPPER(query);

    -- Validate query is not empty
    IF query IS NULL OR query = '' THEN
        RAISE EXCEPTION 'Query text cannot be empty';
    END IF;

    -- Check for forbidden DDL/DCL keywords
    FOREACH keyword IN ARRAY forbidden_keywords
    LOOP
        IF query_upper LIKE '%' || keyword || '%' THEN
            RAISE EXCEPTION 'DDL/DCL operations are not allowed: %', keyword;
        END IF;
    END LOOP;

    -- Block multiple statements
    IF POSITION(';' IN TRIM(TRAILING ' ' FROM query)) > 0
       AND POSITION(';' IN TRIM(TRAILING ' ' FROM query)) < LENGTH(TRIM(TRAILING ' ' FROM query)) THEN
        RAISE EXCEPTION 'Multiple statements are not allowed';
    END IF;

    -- Execute query and return JSON
    BEGIN
        EXECUTE 'SELECT COALESCE(json_agg(row_to_json(t)), ''[]''::json) FROM (' || query || ') t'
        INTO result;

        RETURN result;
    EXCEPTION
        WHEN OTHERS THEN
            -- Log error server-side only
            RAISE WARNING 'Query execution failed: % - SQLSTATE: %', SQLERRM, SQLSTATE;

            -- Return empty array to client
            RETURN '[]'::JSON;
    END;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Grant execute permission to authenticated users (adjust as needed)
-- GRANT EXECUTE ON FUNCTION execute_query(TEXT, JSONB) TO authenticated;
-- GRANT EXECUTE ON FUNCTION execute_query(TEXT, JSONB) TO anon;

-- Test function:
-- SELECT execute_query('SELECT id_user, username, role FROM tbl_users LIMIT 3');

COMMENT ON FUNCTION execute_query(TEXT, JSONB) IS
'Executes SELECT/INSERT/UPDATE/DELETE queries. Blocks DDL/DCL operations. Returns JSON array. Used by SupabaseAdapter for complex queries.';
