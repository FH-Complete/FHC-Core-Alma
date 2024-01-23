<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class JQMSchedulerLib
{
	private $_ci; // Code igniter instance

	const JOB_TYPE_ALMA_CREATE_XML_ABGABEN = 'ALMACreateXMLAbgaben';

	const PRESTUDENT_SYNC_STATUS = ['Absolvent'];
	/**
	 * Object initialization
	 */
	public function __construct()
	{
		$this->_ci =& get_instance();
		$this->_ci->load->config('extensions/FHC-Core-Alma/AlmaProjekt');
	}

	public function createAbgaben()
	{
		$jobInput = null;

		$dbModel = new DB_Model();
		$newAbgabeResult = $dbModel->execReadOnlyQuery("
			SELECT
				DISTINCT (tbl_projektarbeit.projektarbeit_id)
			FROM
				lehre.tbl_projektarbeit
				JOIN lehre.tbl_lehreinheit USING(lehreinheit_id)
				JOIN lehre.tbl_lehrveranstaltung ON(tbl_lehreinheit.lehrveranstaltung_id = tbl_lehrveranstaltung.lehrveranstaltung_id)
				JOIN lehre.tbl_lehrveranstaltung as lehrfach ON(tbl_lehreinheit.lehrfach_id = lehrfach.lehrveranstaltung_id)
				LEFT JOIN lehre.tbl_zeugnisnote ON(tbl_lehrveranstaltung.lehrveranstaltung_id = tbl_zeugnisnote.lehrveranstaltung_id
												AND tbl_zeugnisnote.studiensemester_kurzbz = tbl_lehreinheit.studiensemester_kurzbz
												AND tbl_projektarbeit.student_uid = tbl_zeugnisnote.student_uid)
				JOIN tbl_student ON tbl_student.student_uid = tbl_projektarbeit.student_uid
				JOIN tbl_prestudent ON tbl_student.prestudent_id = tbl_prestudent.prestudent_id
			WHERE
				(
					( tbl_projektarbeit.note > 0 AND tbl_projektarbeit.note < 5)
					OR
					( tbl_projektarbeit.note IS NULL AND tbl_zeugnisnote.note > 0 AND tbl_zeugnisnote.note < 5)
				)
				AND projekttyp_kurzbz IN ?
				AND tbl_projektarbeit.titel IS not null
				AND tbl_projektarbeit.freigegeben
				AND tbl_projektarbeit.abgabedatum >= ?
				AND NOW() >= (tbl_projektarbeit.abgabedatum + interval ?)
				AND projektarbeit_id NOT IN (SELECT projektarbeit_id FROM sync.tbl_alma_projektarbeit)
				AND get_rolle_prestudent(tbl_prestudent.prestudent_id, NULL) IN ?",
			[$this->_ci->config->item('projects_sync'),
			$this->_ci->config->item('project_abgabe_datum'),
			$this->_ci->config->item('project_sync_delay_days') . ' days',
			self::PRESTUDENT_SYNC_STATUS]
		);
		
		if (isError($newAbgabeResult)) return $newAbgabeResult;
		
		if (hasData($newAbgabeResult))
		{
			$jobInput = json_encode(getData($newAbgabeResult));
		}
		
		return success($jobInput);
	}
}
