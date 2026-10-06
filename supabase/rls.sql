-- ==============================================================================
-- Row Level Security (RLS) Activation Script for Supabase
-- Project: erles-bakery-erp (iohzdauarxzbcynxvgpr)
--
-- This script enables Row Level Security (RLS) on all user tables in the
-- 'public' schema to prevent unauthorized direct access via the Supabase Data API.
-- Backend Laravel accesses Postgres via Session pooler with the 'postgres' role,
-- which bypasses RLS, while anon/authenticated public API access is protected.
--
-- Idempotent: Safe to execute repeatedly (e.g. after adding new tables).
-- ==============================================================================

DO $$
DECLARE
    tbl RECORD;
BEGIN
    FOR tbl IN
        SELECT tablename
        FROM pg_tables
        WHERE schemaname = 'public'
          AND tablename NOT LIKE 'pg_%'
    LOOP
        EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY;', tbl.tablename);
    END LOOP;
END $$;
