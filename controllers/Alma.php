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
	const MITARBEITER_ADDRESS_TYPE_DESC = 'work';
	const MITARBEITER_EMAIL_TYPE_DESC = 'work';
	const MITARBEITER_PHONE_TYPE_DESC = 'work';
	const ADDRESS_TYPE = 'home';

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
		//  Detect present user situation in alma.
		//  ------------------------------------------------------------------------------------------------------------
		 /**
		  * Get all new user.
		  * New user is new active campus user that is not present in alma yet.
		  * */
		$new_user_arr = array();
		$result = $this->AlmaModel->getNewUser();
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
		$result = $this->AlmaModel->getOutdatedUser();
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
		$campus_active_user_arr = $this->AlmaModel->getActiveCampusUserData();
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
		$student_expiry_date = new DateTime();
		$student_expiry_date->add(new DateInterval('P5Y'));
		$student_expiry_date = $student_expiry_date->format('Y-m-d');

		foreach ($campus_active_user_arr as $campus_user)
		{
			$user = new StdClass();

			$user->person_id        = $campus_user->person_id;
			$user->alma_match_id    = !empty($campus_user->alma_match_id)
									? $campus_user->alma_match_id
									: show_error('Missing alma_match_id for personID'. $campus_user->person_id);
			$user->first_name       = $campus_user->vorname;
			$user->last_name        = $campus_user->nachname;
			$user->user_title       = trim($campus_user->titelpre. ' '. $campus_user->titelpost);
			$user->gender           = $this->_convertGender($campus_user->geschlecht);
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
			$result = $this->AdresseModel->getZustellAdresse($campus_user->person_id, array(
				'strasse',
				'plz',
				'ort',
				'nation'
			));

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
			$phone_number = '';
			$result = $this->KontaktModel->getAll_byPersonID($campus_user->person_id);
			if ($kontakt_arr = getData($result))
			{
				// prioritize telefonnummer > mobil > firmenhandy > standorttelefon
				$phone_number = $this->_getPhoneNumber_byPrio($kontakt_arr);
			}
			$user->phone_number     = $phone_number;
			$user->phone_type_desc  = $campus_user->user_group_desc == 'Student'
									? self::STUDENT_PHONE_TYPE_DESC
									: self::MITARBEITER_PHONE_TYPE_DESC;


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
			if (empty($campus_card_id))
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
			// Set uid only for new user
			$user->uid = (in_array($campus_user->person_id, array_column($new_user_arr, 'person_id')) ||
						(new DateTime($campus_user->alma_insertamum))->format('Y-m-d') ==
						(new DateTime())->format('Y-m-d'))
						? $campus_user->uid
						: NULL;


			//  Push user to user-array
			//  --------------------------------------------------------------------------------------------------------
			$user_arr []= $user;
		}


		//  ------------------------------------------------------------------------------------------------------------
		//  LOAD XML VIEW
		//  ------------------------------------------------------------------------------------------------------------
		$params = array('user_arr' => $user_arr);
		$this->output->set_content_type('application/xml');
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

	/**
	 * Converts postgre gender to alma gender.
	 * @param string $gender
	 * @return string
	 */
	private function _convertGender($gender)
	{
		switch($gender)
		{
			case 'm':
				return 'MALE';
			case 'w':
				return 'FEMALE';
			case 'x':
				return 'OTHER';
			default:
				return 'NONE';
		}
	}

	/**
	 * Returns highest prioritized phone number.
	 * Prio: telefon > mobil > firmenhandy > standorttelefon > else empty
	 * @param array $kontakt_arr
	 */
	private function _getPhoneNumber_byPrio($kontakt_arr)
	{
		if ($index = array_search('telefon', array_column($kontakt_arr, 'kontakttyp')))
		{
			return $kontakt_arr[$index]->kontakt;
		}
		if ($index = array_search('mobil', array_column($kontakt_arr, 'kontakttyp')))
		{
			return $kontakt_arr[$index]->kontakt;
		}
		if ($index = array_search('firmenhandy', array_column($kontakt_arr, 'kontakttyp')))
		{
			return $kontakt_arr[$index]->kontakt;
		}
		if ($index = array_search('so.tel', array_column($kontakt_arr, 'kontakttyp')))
		{
			return $kontakt_arr[$index]->kontakt;
		}
		return;
	}
}
