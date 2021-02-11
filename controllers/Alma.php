<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This script synchronizes campus user data with libraries system (ALMA) user data.
 * Generates XML-export for ALMA.
 */

class Alma extends Auth_Controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
			'index'=>'admin:rw'
			)
		);

		// Loads config file
		$this->_ci =& get_instance(); // get code igniter instance
		$this->_ci->config->load('extensions/FHC-Core-Alma/ALMA');

		// Loads models
		$this->load->model('extensions/FHC-Core-Alma/Alma_model', 'AlmaModel');
		$this->load->model('person/Kontakt_model', 'KontaktModel');
		$this->load->model('person/Adresse_model', 'AdresseModel');
		$this->load->model('ressource/Betriebsmittelperson_model', 'BetriebsmittelpersonModel');
		$this->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');

		// Loads phrases
		$this->loadPhrases(
			array('global')
		);

		// Loads helpers
		$this->load->helper('xml');

		// Loads libraries
		$this->load->library('WidgetLib');
		$this->load->library('PermissionLib');
		$this->load->library('zip');
	}

	/**
	 * Index Controller
	 * @return void
	 */
	public function index()
	{
		// Actual Studiesemester
		$result = $this->StudiensemesterModel->getLastOrAktSemester();
		if (!$ss_act = getData($result)[0]->studiensemester_kurzbz)
		{
			show_error('Failed retrieving actual term.');
		}

		// Next Studiensemester
		$result = $this->StudiensemesterModel->getNextFrom($ss_act);
		if (!$ss_next = getData($result)[0]->studiensemester_kurzbz)
		{
			show_error('Failed retrieving next term.');
		}

		/**
		 * Get all active campus user, that are already in ALMA system but with other person_id.
		 * They should not be inserted twice.
		 */
		$allowed_double_person_arr = $this->_ci->config->item('allowed_double_personIDs');

		$double_person_arr = array();
		$result = $this->AlmaModel->checkDoublePersons($ss_act, $ss_next, $allowed_double_person_arr);
		if (hasData($result))
		{
			if (!$double_person_arr = getData($result))
			{
				show_error($double_person_arr->retval);
			}
		}

		$view_data = array(
			'double_person_arr' => $double_person_arr
		);

		$this->load->library('WidgetLib');
		$this->load->view('extensions/FHC-Core-Alma/Alma', $view_data);
	}
}
