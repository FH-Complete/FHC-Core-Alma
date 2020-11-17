ALTER TABLE sync.tbl_alma ADD COLUMN IF NOT EXISTS inactiveamum timestamp without time zone DEFAULT NULL;

COMMENT ON COLUMN sync.tbl_alma.inactiveamum IS 'Date, when person is set inactive. Used for ALMA purge_date.';
