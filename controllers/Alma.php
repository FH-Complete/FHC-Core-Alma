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
			'index'=>'admin:rw',
			'export'=>'admin:r'
			)
		);

		// Loads config file
		$this->_ci =& get_instance(); // get code igniter instance
		$this->_ci->config->load('extensions/FHC-Core-Alma/ALMAConfig');

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

		$this->_setAuthUID(); // sets property uid
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

	public function export()
	{
		$today = (new DateTime())->format('Y-m-d');

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

		//  Detect present user situation in alma.
		//  ------------------------------------------------------------------------------------------------------------
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

		 /**
		  * Get all new user.
		  * New user is new active campus user that is not present in alma yet.
		  * */
		$new_user_arr = array();
		$result = $this->AlmaModel->getNewUser($ss_act, $ss_next);
		if (hasData($result))
		{
			if (!$new_user_arr = getData($result))
			{
				show_error($new_user_arr->retval);
			}
		}

		// Filter user with double person_id entries
		$new_user_arr = array_filter($new_user_arr, function($elem) use ($double_person_arr){
			return !in_array($elem->person_id, array_column($double_person_arr, 'person_id'));
		});
		
		/**
		 * Get all inactive user.
		 * Inactive user is alma user that is not present in campus' active user array anymore.
		 * */
		$inactive_user_arr = array();
		$result = $this->AlmaModel->getInactiveUser($ss_act, $ss_next);

		if (hasData($result))
		{
			if (!$inactive_user_arr = getData($result))
			{
				show_error($inactive_user_arr->retval);
			}
		}

		/**
		 *  For all inactive user, mark alma user as inactive (inactiveamum = today).
		/*  Todays inactive user are reported with a purge date in ALMA.
		/*  By setting inactiveamum with todays date they will not be queried as inactive user from tomorrow on.
		 * */
		foreach ($inactive_user_arr as $inactive_user)
		{
			$result = $this->AlmaModel->update(
				$inactive_user->person_id,
				array('inactiveamum' => $today)
			);

			if (isError($result))
			{
				show_error($result->retval);
			}
		}

		//  Insert new user to alma.
		//  ------------------------------------------------------------------------------------------------------------
		foreach ($new_user_arr as $new_user)
		{
			if (!$result = $this->AlmaModel->insert($new_user))
			{
				show_error('Failed inserting new user with person_id: '. $new_user->person_id);
			}
		}

		/**
		 * Get active user data.
		 * Retrieves user data incl. alma match id for present and new alma user. (just inserted to alma)
		 * */
		//  ------------------------------------------------------------------------------------------------------------
		$active_user_arr = $this->AlmaModel->getActiveUser($ss_act, $ss_next);

		if (!$active_user_arr = getData($active_user_arr))
		{
			show_error($active_user_arr->retval);
		}

		// Filter user with double person_id entries
		$active_user_arr = array_filter($active_user_arr, function($elem) use ($double_person_arr){
			return !in_array($elem->person_id, array_column($double_person_arr, 'person_id'));
		});

		/**
		 * For all active user, mark alma user as active (again). (inactiveamum = NULL)
		 * This step is needed in case person was inactive in the past but is active again.
		 * e.g. Student ended with bachelor - was inactive. Goes on with master - active again.
		 * */
		foreach ($active_user_arr as $active_user)
		{
			$result = $this->AlmaModel->update(
				$active_user->person_id,
				array('inactiveamum' => NULL)
			);

			if (isError($result))
			{
				show_error($result->retval);
			}
		}


		// Merge active campus user (including new user) with inactive ALMA user
		//  ------------------------------------------------------------------------------------------------------------
		$all_user_arr = array_merge($active_user_arr, $inactive_user_arr);


		//  ------------------------------------------------------------------------------------------------------------
		//  BUILD XML
		//  ------------------------------------------------------------------------------------------------------------
		//  <user>
		//  ------------------------------------------------------------------------------------------------------------
		$user_arr = array();    // Prepared user data for XML export

		// Default student vars
		$student_user_group = $this->config->item('student_user_group')
			?: show_error('Missing config entry for student_user_group');
		$student_email_type_desc = $this->config->item('student_email_type_desc')
			?: show_error('Missing config entry for student_email_type_desc');
		$student_expiry_date = new DateTime();
		$student_expiry_date->add(new DateInterval('P5Y'));
		$student_expiry_date = $student_expiry_date->format('Y-m-d');

		// Default employee vars
		$mitarbeiter_user_group = $this->config->item('mitarbeiter_user_group')
			?: show_error('Missing config entry for mitarbeiter_user_group');
		$mitarbeiter_email_type_desc = $this->config->item('mitarbeiter_email_type_desc')
			?: show_error('Missing config entry for mitarbeiter_email_type_desc');
		$mitarbeiter_expiry_date = $this->config->item('mitarbeiter_expiry_date')
			?: show_error('Missing config entry for mitarbeiter_expiry_date');

		foreach ($all_user_arr as $campus_user)
		{
			$user = new StdClass();

			$user->uid              = !empty($campus_user->uid)
									? $campus_user->uid
									: show_error('Missing UID for person_id '. $campus_user->person_id); // MUST-have in ALMA
			$user->person_id        = $campus_user->person_id;
			$user->alma_match_id    = $campus_user->alma_match_id;
			$user->first_name       = $campus_user->vorname;
			$user->last_name        = $campus_user->nachname;
			$user->gender           = $campus_user->geschlecht;
			$user->birth_date       = !empty($campus_user->gebdatum)
									? (new DateTime($campus_user->gebdatum))->format('Y-m-d')
									: '';

			/**
			 * If user is inactive, set purge date to 6 months from now.
			 * 6 months as buffer, in case user still needs to return book after deactivation.
			 * Buffer also helpful, when user is deactivated e.g. as bachelor, but still will go on with master and is
			 * not activated as master yet.
			 */
			if (!$campus_user->active)
			{
				$purge_date         = (new DateTime())->add(new DateInterval('P6M'));
				$user->purge_date   = $purge_date->format('Y-m-d');
				$user->expiry_date  = $user->purge_date ;
			}
			// Else if us is active, set purge date to default expiry date.
			else
			{
				$user->expiry_date      = $campus_user->user_group_desc == 'Student'
					? $student_expiry_date
					: $mitarbeiter_expiry_date;

				$user->purge_date   = $campus_user->user_group_desc == 'Student'
					? $student_expiry_date
					: $mitarbeiter_expiry_date;
			}

			$user->user_group_desc  = $campus_user->user_group_desc;
			$user->user_group       = $campus_user->user_group_desc == 'Student'
									? $student_user_group
									: $mitarbeiter_user_group;


			//  <contact_info>
			//  --------------------------------------------------------------------------------------------------------

			// <emails>
			$user->email_address    = $campus_user->uid. '@'. DOMAIN;
			$user->email_type_desc  = $campus_user->user_group_desc == 'Student'
									? $student_email_type_desc
									: $mitarbeiter_email_type_desc;


			//  <user_identifiers>
			//  --------------------------------------------------------------------------------------------------------
			// Campus card ID
			$campus_card_id = NULL;
			$result = $this->BetriebsmittelpersonModel->getBetriebsmittel($campus_user->person_id, 'Zutrittskarte', false);
			if ($betriebsmittel = getData($result)[0])
			{
				if (!empty($betriebsmittel->nummer2))
				{
					$campus_card_id = $betriebsmittel->nummer2;
				}
			}

			// if no campus card ID was found, use default ID
			if (is_null($campus_card_id))
			{
				$campus_card_id = $campus_user->user_group_desc == 'Student'
								? 'TWSTD'. $campus_user->uid
								: 'TWMA'. $campus_user->uid;
			}

			// max 20 chars
			if(strlen($campus_card_id) > 20)
			{
				$campus_card_id = substr($campus_card_id, 0, 20);
			}
			$user->campus_card_id  = $campus_card_id;


			//  Push user to user-array
			//  --------------------------------------------------------------------------------------------------------
			$user_arr []= $user;
		}


		//  ------------------------------------------------------------------------------------------------------------
		//  CREATE XML OUTPUT
		//  ------------------------------------------------------------------------------------------------------------
		$params = array('user_arr' => $user_arr);

		$this->output->set_header('HTTP/1.0 200 OK');
		$this->output->set_header('HTTP/1.1 200 OK');
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate');
		$this->output->set_header('Cache-Control: post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');
		$this->output->set_content_type('application/xml', 'utf-8');
		$this->load->view('extensions/FHC-Core-Alma/export', $params);

		$xml_content = $this->output->get_output();

		//  ------------------------------------------------------------------------------------------------------------
		//  COMPRESS XML OUTPUT (ZIP)
		//  ------------------------------------------------------------------------------------------------------------
		$filename_prefix = $this->config->item('filename_prefix')
			?: show_error('Missing config entry for filename_prefix');

		$this->zip->add_data($filename_prefix. $today. '.xml', $xml_content);
		$this->zip->archive($filename_prefix. $today. '.zip');
		$this->zip->download($filename_prefix. $today. '.zip');
	}


	//  ----------------------------------------------------------------------------------------------------------------
	//  PRIVATE METHODS
	//  ----------------------------------------------------------------------------------------------------------------
	/**
	 * Retrieve the UID of the logged user and checks if it is valid
	 */
	private function _setAuthUID()
	{
		$this->uid = getAuthUID();

		if (!$this->uid) show_error('User authentification failed');
	}

}
