CREATE TABLE IF NOT EXISTS sync.tbl_alma_projektarbeit
(
    projektarbeit_id INTEGER NOT NULL,
    insertamum       TIMESTAMP DEFAULT NOW(),
    pseudo_id        VARCHAR(64) NOT NULL UNIQUE,
    freigeschaltet_datum TIMESTAMP NOT NULL
);

DO $$
    BEGIN
        ALTER TABLE ONLY sync.tbl_alma_projektarbeit ADD CONSTRAINT tbl_alma_projektarbeit_projektarbeit_id_fkey FOREIGN KEY (projektarbeit_id) REFERENCES lehre.tbl_projektarbeit(projektarbeit_id) ON DELETE RESTRICT ON UPDATE CASCADE;
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

CREATE UNIQUE INDEX IF NOT EXISTS tbl_alma_projektarbeit_pseudo_id_uindex ON sync.tbl_alma_projektarbeit (pseudo_id);
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE sync.tbl_alma_projektarbeit TO vilesci;
