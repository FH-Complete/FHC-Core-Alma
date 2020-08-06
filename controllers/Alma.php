<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This script synchronizes campus user data with libraries system (ALMA) user data.
 * Generates XML-export for ALMA.
 */

class Alma extends Auth_Controller
{
	const STUDENT_USER_GROUP = '01';
	const STUDENT_ADDRESS_TYPE_DESC = 'home';
	const STUDENT_EMAIL_TYPE_DESC = 'alternative';
	const STUDENT_PHONE_TYPE_DESC = 'home';
	const MITARBEITER_USER_GROUP = '03';
	const MITARBEITER_EXPIRY_DATE = '2099-12-31';
	const MITARBEITER_ADDRESS_TYPE_DESC = 'home';
	const MITARBEITER_EMAIL_TYPE_DESC = 'work';
	const MITARBEITER_PHONE_TYPE_DESC = 'work';
	const ADDRESS_TYPE = 'home';
	const FILENAME_PREFIX = '03_ftw_';

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

		$this->_setAuthUID(); // sets property uid
	}

	/**
	 * Index Controller
	 * @return void
	 */
	public function index()
	{
		$this->load->library('WidgetLib');
		$this->load->view('extensions/FHC-Core-Alma/Alma');
	}

	public function export()
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

		//  Detect present user situation in alma.
		//  ------------------------------------------------------------------------------------------------------------
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
		
		/**
		 * Get all outdated user.
		 * Outdated user is alma user that is not present in ACTIVE campus user anymore.
		 * */
		$outdated_user_arr = array();
		$result = $this->AlmaModel->getOutdatedUser($ss_act, $ss_next);
		if (hasData($result))
		{
			if (!$outdated_user_arr = getData($result))
			{
				show_error($outdated_user_arr->retval);
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
		 * Get active campus user data.
		 * Retrieves user data incl. alma match id for present and new alma user. (just inserted to alma)
		 * */
		//  ------------------------------------------------------------------------------------------------------------
		$campus_active_user_arr = $this->AlmaModel->getActiveCampusUserData($ss_act, $ss_next);
		if (!$campus_active_user_arr = getData($campus_active_user_arr))
		{
			show_error($campus_active_user_arr->retval);
		}

		//  ------------------------------------------------------------------------------------------------------------
		//  BUILD XML
		//  ------------------------------------------------------------------------------------------------------------
		//  <user>
		//  ------------------------------------------------------------------------------------------------------------
		$user_arr = array();    // Prepared user data for XML export
		$today = (new DateTime())->format('Y-m-d');
		$student_expiry_date = new DateTime();
		$student_expiry_date->add(new DateInterval('P5Y'));
		$student_expiry_date = $student_expiry_date->format('Y-m-d');

		foreach ($campus_active_user_arr as $campus_user)
		{
			$user = new StdClass();

			$user->person_id        = $campus_user->person_id;
			$user->alma_match_id    = $campus_user->alma_match_id;
			$user->first_name       = $campus_user->vorname;
			$user->last_name        = $campus_user->nachname;
			$user->user_title       = trim($campus_user->titelpre. ' '. $campus_user->titelpost);
			$user->gender           = $campus_user->geschlecht;
			$user->birth_date       = (new DateTime($campus_user->gebdatum))->format('Y-m-d');
			$user->expiry_date      = $campus_user->user_group_desc == 'Student'
									? $student_expiry_date
									: self::MITARBEITER_EXPIRY_DATE;
			$user->purge_date       = $campus_user->user_group_desc == 'Student'
									? $student_expiry_date
									: self::MITARBEITER_EXPIRY_DATE;
			$user->user_group_desc  = $campus_user->user_group_desc;
			$user->user_group       = $campus_user->user_group_desc == 'Student'
									? self::STUDENT_USER_GROUP
									: self::MITARBEITER_USER_GROUP;


			//  <contact_info>
			//  --------------------------------------------------------------------------------------------------------
			//  <addresses>
			$address_obj = NULL;
			$result = $this->AdresseModel->getZustellAdresse($campus_user->person_id, 'strasse, plz, ort, nation');

			if (hasData($result))
			{
				if ($address_obj = getData($result)[0])
				{
					// Only store address if at least strasse is given
					if (empty($address_obj->strasse))
					{
						$address_obj = NULL;    // otherwise reset address obj to null
					}
				}
			}
			$user->address = $address_obj;
			$user->address_type_desc = $campus_user->user_group_desc == 'Student'
										? self::STUDENT_ADDRESS_TYPE_DESC
										: self::MITARBEITER_ADDRESS_TYPE_DESC;
			$user->address_type = self::ADDRESS_TYPE;


			// <emails>
			$user->email_address    = $campus_user->uid. '@'. DOMAIN;
			$user->email_type_desc  = $campus_user->user_group_desc == 'Student'
									? self::STUDENT_EMAIL_TYPE_DESC
									: self::MITARBEITER_EMAIL_TYPE_DESC;


			// <phones>
			$phone_number = NULL;
			$phone_type_desc = '';

			// retrieve phone prioritized by telefonnummer > mobil > firmenhandy > standorttelefon
			$result = $this->KontaktModel->getPhones_byPerson($campus_user->person_id);
			if ($kontakt = getData($result)[0]) // get top of prio phone list
			{
				$phone_number = trim($kontakt->kontakt, '.');
				$phone_type_desc = $kontakt->kontakttyp;
			}
			$user->phone_number     = $phone_number;
			$user->phone_type_desc  = $phone_type_desc;


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


			// UID
			$user->uid = $campus_user->uid;

			// New
			// Set password (= birthday) only for new user
			$user->password = (in_array($campus_user->person_id, array_column($new_user_arr, 'person_id')) ||
				(new DateTime($campus_user->alma_insertamum))->format('Y-m-d') == $today)
				? (new DateTime($campus_user->gebdatum))->format('Ymd')
				: NULL;


			//  Push user to user-array
			//  --------------------------------------------------------------------------------------------------------
			$user_arr []= $user;
		}


		//  ------------------------------------------------------------------------------------------------------------
		//  LOAD XML VIEW
		//  ------------------------------------------------------------------------------------------------------------
		$params = array('user_arr' => $user_arr);
		$this->output->set_header('HTTP/1.0 200 OK');
		$this->output->set_header('HTTP/1.1 200 OK');
		$this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate');
		$this->output->set_header('Cache-Control: post-check=0, pre-check=0');
		$this->output->set_header('Pragma: no-cache');
		$this->output->set_header('Content-Disposition: attachment; filename="'. self::FILENAME_PREFIX. $today. '.xml"');
		$this->output->set_content_type('application/xml', 'utf-8');
		$this->load->view('extensions/FHC-Core-Alma/export', $params);
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
