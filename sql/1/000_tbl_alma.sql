CREATE OR REPLACE FUNCTION extension.extension_alma_create_table () RETURNS TEXT AS $$

CREATE TABLE IF NOT EXISTS sync.tbl_alma(
	person_id integer NOT NULL,
	alma_match_id integer,
	insertamum timestamp DEFAULT now()
);

COMMENT ON TABLE sync.tbl_alma IS 'Synchronizationtable between FHC Persons and Alma records';

ALTER TABLE sync.tbl_alma
ADD CONSTRAINT pk_alma_person_id
PRIMARY KEY (person_id);

ALTER TABLE sync.tbl_alma ADD CONSTRAINT fk_tbl_alma_person FOREIGN KEY (person_id)
	REFERENCES public.tbl_person(person_id) ON UPDATE CASCADE ON DELETE RESTRICT;

CREATE SEQUENCE sync.tbl_alma_match_id_seq
 INCREMENT BY 1
 NO MAXVALUE
 NO MINVALUE
 CACHE 1;
ALTER TABLE sync.tbl_alma ALTER COLUMN alma_match_id SET DEFAULT nextval('sync.tbl_alma_match_id_seq');

GRANT SELECT, UPDATE ON sync.tbl_alma to vilesci;
GRANT SELECT, UPDATE ON sync.tbl_alma_match_id_seq TO vilesci;
SELECT 'Table added'::text;
$$
LANGUAGE 'sql';

SELECT
	CASE
	WHEN (SELECT true::BOOLEAN FROM pg_catalog.pg_tables WHERE schemaname = 'sync' AND tablename = 'tbl_alma')
	THEN (SELECT 'success'::TEXT)
	ELSE (SELECT extension.extension_alma_create_table())
END;

-- Drop function
DROP FUNCTION extension.extension_alma_create_table();
