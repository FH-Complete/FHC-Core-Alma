INSERT INTO system.tbl_jobtypes (type, description)
VALUES ('ALMACreateXMLAbgaben', 'Create Abgaben-XML for ALMA')
ON CONFLICT (type) DO NOTHING;

