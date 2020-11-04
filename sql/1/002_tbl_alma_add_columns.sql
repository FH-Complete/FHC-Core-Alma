DO $$
BEGIN
	ALTER TABLE sync.tbl_alma ADD COLUMN inactiveamum timestamp without time zone DEFAULT NULL;
	EXCEPTION WHEN OTHERS THEN NULL;
END $$;

COMMENT ON COLUMN sync.tbl_alma.inactiveamum IS 'Date, when person is set inactive. Used for ALMA purge_date.';
