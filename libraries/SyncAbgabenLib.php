<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class SyncAbgabenLib
{
	// Jobs types used by this lib
	const JOB_TYPE_ALMA_CREATE_XML_ABGABEN = 'ALMACreateXMLAbgaben';

	private $_ci; // Code igniter instance

	/**
	 * Object initialization
	 */
	public function __construct()
	{
		$this->_ci =& get_instance(); // get code igniter instance

		// Loads the LogLib with the needed parameters to log correctly from this library
		$this->_ci->load->library(
			'LogLib',
			array(
				'classIndex' => 3,
				'functionIndex' => 3,
				'lineIndex' => 2,
				'dbLogType' => 'job', // required
				'dbExecuteUser' => 'Cronjob system',
				'requestId' => 'JOB',
				'requestDataFormatter' => function($data) {
					return json_encode($data);
				}
			),
			'LogLibALMA'
		);

		$this->_ci->load->helper('xml');
		$this->_ci->load->library('zip');
		$this->_ci->load->model('extensions/FHC-Core-Alma/AlmaProjektarbeit_model', 'AlmaProjektarbeitModel');
		$this->_ci->load->config('extensions/FHC-Core-Alma/AlmaProjekt');
	}

	// --------------------------------------------------------------------------------------------
	// Public methods
	public function createAbgaben($projects)
	{
		if (isEmptyArray($projects)) return success('No projects to be created');
		
		$diffProjects = $this->_removeCreatedProjects($projects, false);
		if (isError($diffProjects)) return $diffProjects;
		if (!hasData($diffProjects)) return success('No projects to be created after diff');

		
		$projectsAllData = $this->_getAllProjectsData($diffProjects);

		if (isError($projectsAllData)) return $projectsAllData;
		if (!hasData($projectsAllData)) return error('No data available for the given projects');
		
		$projectsAllData = getData($projectsAllData);

		$params = ['projectsAllData' => $projectsAllData];

		$xmlContent = $this->_ci->load->view('extensions/FHC-Core-Alma/' . $this->_ci->config->item('export_view'), $params, true);

		$exportPath = $this->_ci->config->item('export_path') ?: show_error('Missing config entry for export_path');
	
		$filenamePrefix = $this->_ci->config->item('filename_prefix') ?: show_error('Missing config entry for filename_prefix');
		
		$filePath = $exportPath . $filenamePrefix . '.xml';
		
		$result = file_put_contents($filePath, $xmlContent);

		if (!$result)
			return error('Failed to Export Alma Projects Data to Filesystem - Check Permissions or disk space');
		
		return success('End Export Alma Projects Data to Filesystem');
	}

	// --------------------------------------------------------------------------------------------
	// Private methods
	public function _getAllProjectsData($projects)
	{

		$projectsAllDataArray = array(); // returned array

		// Retrieves users personal data from database
		$dbModel = new DB_Model();
		
		$dbProjectsData = $dbModel->execReadOnlyQuery("
			SELECT
			*, tbl_lehreinheit.studiensemester_kurzbz, tbl_projektarbeit.student_uid as stud_uid,
			(
				WITH RECURSIVE meine_oes(oe_kurzbz, oe_parent_kurzbz, organisationseinheittyp_kurzbz) as
				(
					SELECT
						oe_kurzbz, oe_parent_kurzbz, organisationseinheittyp_kurzbz
					FROM
						public.tbl_organisationseinheit
					WHERE
						oe_kurzbz=tbl_lehrveranstaltung.oe_kurzbz
						AND aktiv = true
					UNION ALL
					SELECT
						o.oe_kurzbz, o.oe_parent_kurzbz, o.organisationseinheittyp_kurzbz
					FROM
						public.tbl_organisationseinheit o, meine_oes
					WHERE
						o.oe_kurzbz=meine_oes.oe_parent_kurzbz
						AND aktiv = true
				)
				SELECT
					tbl_organisationseinheit.bezeichnung
				FROM
					meine_oes
					JOIN public.tbl_organisationseinheit USING(oe_kurzbz)
				WHERE
					meine_oes.organisationseinheittyp_kurzbz = 'Department'
				LIMIT 1
			) as department,
			tbl_lehrveranstaltung.studiengang_kz as stg_kz,
			tbl_projektarbeit.note as note1, tbl_zeugnisnote.note as note2, tbl_projektarbeit.sprache as sprache_arbeit,
			tbl_zeugnisnote.lehrveranstaltung_id,
			tbl_studiengangstyp.bezeichnung as stgtyp,
			tbl_studiengang.bezeichnung as stgbezeichnung,
			tbl_studiengang.melde_studiengang_kz as melde_stg_kz
		FROM
			lehre.tbl_projektarbeit
			JOIN lehre.tbl_lehreinheit USING(lehreinheit_id)
			JOIN lehre.tbl_lehrveranstaltung ON(tbl_lehreinheit.lehrveranstaltung_id = tbl_lehrveranstaltung.lehrveranstaltung_id)
			JOIN lehre.tbl_lehrveranstaltung as lehrfach ON(tbl_lehreinheit.lehrfach_id = lehrfach.lehrveranstaltung_id)
			LEFT JOIN lehre.tbl_zeugnisnote ON(tbl_lehrveranstaltung.lehrveranstaltung_id = tbl_zeugnisnote.lehrveranstaltung_id
											AND tbl_zeugnisnote.studiensemester_kurzbz = tbl_lehreinheit.studiensemester_kurzbz
											AND tbl_projektarbeit.student_uid = tbl_zeugnisnote.student_uid)
			JOIN tbl_studiengang ON (tbl_lehrveranstaltung.studiengang_kz = tbl_studiengang.studiengang_kz)
		    JOIN tbl_studiengangstyp ON (tbl_studiengang.typ = tbl_studiengangstyp.typ)
		WHERE
			tbl_projektarbeit.projektarbeit_id IN ?",
				array(getData($projects))
		);

		if (isError($dbProjectsData)) return $dbProjectsData;
		if (!hasData($dbProjectsData)) return error('The provided project ids are not present in database');

		foreach (getData($dbProjectsData) as $projectData)
		{
			
			$error = false;
			if (($projectData->note1 < 0 || $projectData->note1 > 4) && ($projectData->note2 < 0 || $projectData->note2 > 4))
			{
				continue;
			}

			if ($projectData->schlagwoerter === null)
			{
				$this->_ci->LogLibALMA->logWarningDB('Schlagwörter nicht angegeben: '. $projectData->projektarbeit_id);
				$error = true;
			}
			
			if ($projectData->schlagwoerter_en === null)
			{
				$this->_ci->LogLibALMA->logWarningDB('Englische Schlagwörter nicht angegeben: '. $projectData->projektarbeit_id);
				$error = true;
			}

			if ($projectData->abstract === null)
			{
				$this->_ci->LogLibALMA->logWarningDB('Abstract nicht angegeben: '. $projectData->projektarbeit_id);
				$error = true;
			}

			if ($projectData->abstract_en === null)
			{
				$this->_ci->LogLibALMA->logWarningDB('Englischer Abstract nicht angegeben: '. $projectData->projektarbeit_id);
				$error = true;
			}

			if ($projectData->seitenanzahl === null)
			{
				$this->_ci->LogLibALMA->logWarningDB('Seitenanzahl nicht angegeben: '. $projectData->projektarbeit_id);
				$error = true;
			}

			if ($projectData->stg_kz === null || $projectData->stg_kz === 0)
			{
				$this->_ci->LogLibALMA->logWarningDB('Studiengang nicht gefunden: '. $projectData->projektarbeit_id);
				$error = true;
			}

			if ($projectData->studiensemester_kurzbz === null || $projectData->studiensemester_kurzbz === 0)
			{
				$this->_ci->LogLibALMA->logWarningDB('Studiensemester nicht gefunden: '. $projectData->projektarbeit_id);
				$error = true;
			}

			$projectAllDataArray = $projectData;

			$this->_ci->load->model('person/Benutzer_model', 'BenutzerModel');
			$this->_ci->BenutzerModel->addSelect('vorname, nachname');
			$this->_ci->BenutzerModel->addJoin('public.tbl_person', 'tbl_person.person_id = tbl_benutzer.person_id');
			$benutzerResult = $this->_ci->BenutzerModel->loadWhere(
				array(
					'uid' => $projectData->stud_uid
				)
			);

			if (isError($benutzerResult)) return $benutzerResult;
			if (!hasData($benutzerResult))
			{
				$this->_ci->LogLibALMA->logWarningDB('Kein Verfasser zugeordnet: '. $projectData->projektarbeit_id);
			}
			
			$projectAllDataArray->author = getData($benutzerResult);

			$this->_ci->load->model('education/Projektbetreuer_model', 'ProjektbetreuerModel');
			
			$this->_ci->ProjektbetreuerModel->addSelect('vorname, nachname');
			$this->_ci->ProjektbetreuerModel->addJoin('public.tbl_person', 'tbl_person.person_id = lehre.tbl_projektbetreuer.person_id');
			$erstBegutachterResult = $this->_ci->ProjektbetreuerModel->loadWhere(
				"projektarbeit_id = " . $projectData->projektarbeit_id .
				" AND (
					betreuerart_kurzbz = 'Betreuer'
					OR betreuerart_kurzbz = 'Begutachter'
					OR betreuerart_kurzbz = 'Erstbegutachter'
					OR betreuerart_kurzbz = 'Erstbetreuer'
				)"
			);

			if (isError($erstBegutachterResult)) return $erstBegutachterResult;
			if (!hasData($erstBegutachterResult))
			{
				$this->_ci->LogLibALMA->logWarningDB('Kein 1.Begutachter/Betreuer zugeordnet: '. $projectData->projektarbeit_id);
				$error = true;
			}
			else
			{
				$projectAllDataArray->erstBegutachter = getData($erstBegutachterResult);
			}
			
			$this->_ci->ProjektbetreuerModel->addSelect('vorname, nachname');
			$this->_ci->ProjektbetreuerModel->addJoin('public.tbl_person', 'tbl_person.person_id = lehre.tbl_projektbetreuer.person_id');
			$zweitBegutachterResult = $this->_ci->ProjektbetreuerModel->loadWhere(
				"projektarbeit_id = " . $projectData->projektarbeit_id .
				" AND (
					betreuerart_kurzbz = 'Zweitbetreuer'
					OR betreuerart_kurzbz = 'Zweitbegutachter'
				)"
			);

			if (isError($zweitBegutachterResult)) return $zweitBegutachterResult;
			if (!hasData($zweitBegutachterResult))
			{
				$this->_ci->LogLibALMA->logWarningDB('Kein 2.Begutachter zugeordnet: '. $projectData->projektarbeit_id);
			}
			else
			{
				$projectAllDataArray->zweitBegutachter = getData($zweitBegutachterResult);
			}
			
			
			if ($error)
				continue;

			do
			{
				$random =  md5(uniqid(mt_rand(), true));
				$data = $this->_ci->AlmaProjektarbeitModel->loadWhere(array('pseudo_id' => $random));
			} while (hasData($data));
			
			$freigeschaltet_tage = $this->_ci->config->item('project_freigeschaltet_days') ?: show_error('Missing config entry for project_freigeschaltet_days');
			
			$freigeschaltet_datum = new DateTime();
			$freigeschaltet_datum->modify('+' . $freigeschaltet_tage . 'days');

			$result = $this->_ci->AlmaProjektarbeitModel->insert(
				array('projektarbeit_id' => $projectData->projektarbeit_id,
					'pseudo_id' => $random,
					'freigeschaltet_datum' => $freigeschaltet_datum->format('Y-m-d')
				)
			);
			
			if (isError($result)) return $result;
			
			$projectAllDataArray->pseudo_id = $random;
			
			$projectsAllDataArray[] = $projectAllDataArray;
		}

		return success($projectsAllDataArray); // everything was fine!
	}
	
	private function _removeCreatedProjects($projects, $initialFoundValue)
	{
		$diffProjectsArray = array(); // array that is foing to be returned
		
		// Get synchronized users from database
		$dbModel = new DB_Model();
		$dbSyncdProjects = $dbModel->execReadOnlyQuery('
			SELECT s.projektarbeit_id
			  FROM sync.tbl_alma_projektarbeit s
			 WHERE s.projektarbeit_id IN ?
		', array($projects));
		
		// If error then return it
		if (isError($dbSyncdProjects)) return $dbSyncdProjects;
		
		// Loops through the given projects and depending on the value of the parameter initialFoundValue
		// removes created (initialFoundValue == false) or not created (initialFoundValue == true) projects
		// from the projects parameter
		for ($i = 0; $i < count($projects); $i++)
		{
			$found = $initialFoundValue; // initial value is the same as initialFoundValue
			
			if (hasData($dbSyncdProjects)) // only if data are present in database
			{
				foreach (getData($dbSyncdProjects) as $dbSyncdProject) // for each synced project
				{
					if ($projects[$i] == $dbSyncdProject->projektarbeit_id)
					{
						$found = !$initialFoundValue; // opposite value of initialFoundValue
						break;
					}
				}
			}
			
			if (!$found) $diffProjectsArray[] = $projects[$i]; // if not found then add to diffProjectsArray array
		}
		
		return success($diffProjectsArray);
	}
}
