<?php
/**
 * Created by PhpStorm.
 * User: Cristina
 * Date: 29.07.2020
 * Time: 13:25
 */

class Alma_model extends DB_Model
{
	/** Constructor */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'sync.tbl_alma';
		$this->pk = 'person_id';

		// Config vars
		$this->excluded_study_programs = $this->_ci->config->item('excluded_study_programs');
	}

	/**
	 * Get all persons with same vorname, nachname and birthdate, but different person_ids.
	 * Therefore get all active campus user and compare with user of alma table for same vorname, nachname and birthdate.
	 * If doulbe entries are found, they should be first matched together in VILSECI tool.
	 * They should NOT be inserted into alma table as new user.
	 * @return mixed
	 */
	public function checkDoublePersons($ss_act, $ss_next, $allowed_double_person_arr)
	{
		$qry_campus_user = $this->_getQueryString_activeCampusUser($ss_act, $ss_next);

		$qry = '
			WITH 
			tmp_campus AS ('. $qry_campus_user. '),
			tmp_alma AS (
				SELECT person_id AS alma_person_id, vorname, nachname, gebdatum 
				FROM sync.tbl_alma
				JOIN public.tbl_person USING (person_id)
			) 
				
			SELECT * from tmp_campus 
			JOIN tmp_alma ON
				tmp_campus.nachname = tmp_alma.nachname AND
				tmp_campus.vorname = tmp_alma.vorname AND
				tmp_campus.gebdatum = tmp_alma.gebdatum AND
				tmp_campus.person_id != tmp_alma.alma_person_id ';

		if(!is_null($allowed_double_person_arr) && !empty($allowed_double_person_arr))
		{
			$qry .= '
				AND tmp_campus.person_id NOT IN ?
			';
		}

		$qry .= '		
			ORDER BY tmp_campus.nachname, tmp_campus.vorname, tmp_campus.gebdatum
		';

		return $this->execQuery($qry, array($allowed_double_person_arr));
	}

	/**
	 * Get all new user.
	 * New user is new active campus user that is not present in alma yet.
	 */
	public function getNewUser($ss_act, $ss_next)
	{
		$active_campus_user = $this->_getQueryString_activeCampusUser($ss_act, $ss_next);

		$qry = '
			SELECT person_id FROM ('. $active_campus_user. ') AS campus
			EXCEPT
			SELECT person_id FROM sync.tbl_alma AS alma
			ORDER BY person_id;
		';

		return $this->execQuery($qry);
	}

	/**
	 * Get all inactive user.
	 * Inactive user is alma user that is not present as active campus user anymore.
	 */
	public function getInactiveUser($ss_act, $ss_next)
	{
		$campus_user = $this->_getQueryString_activeCampusUser($ss_act, $ss_next);

		$qry = '
			WITH inactive_person_ids AS (
				SELECT person_id FROM sync.tbl_alma AS alma
				EXCEPT
				SELECT person_id FROM (' . $campus_user . ') AS campus
				ORDER BY person_id
			)
			
			SELECT  alma_match_id, 
					alma.insertamum as "alma_insertamum",
					alma.person_id,
					NULL as uid,
					vorname, nachname,
					titelpre,
					CASE 
						WHEN EXISTS (SELECT 1 FROM campus.vw_mitarbeiter WHERE inactive_person_ids.person_id = vw_mitarbeiter.person_id) THEN \'Mitarbeiter\'
						ELSE \'Student\'
					END AS user_group_desc,
					tbl_person.insertamum,
					vornamen,
					titelpost,
					gebdatum,
					CASE
						WHEN geschlecht = \'m\' THEN \'MALE\'
						WHEN geschlecht = \'w\' THEN \'FEMALE\'
						WHEN geschlecht = \'x\' THEN \'OTHER\'
						ELSE \'NONE\'
					END AS geschlecht,
					false AS active
			FROM    inactive_person_ids
			JOIN    public.tbl_person USING (person_id)
			JOIN    sync.tbl_alma alma USING (person_id)
			/* filter only inactive alma user, that were not already set as inactive in the past */ 
			WHERE   alma.inactiveamum IS NULL OR alma.inactiveamum::date = NOW()::date;
		';

		return $this->execQuery($qry);

	}

	/**
	 * Get all active campus user data with the corresponding alma match id.
	 * Campus user are retrieved unique by their prioritized role.
	 * @return mixed
	 */
	public function getActiveUser($ss_act, $ss_next)
	{
		$qry_campus_user = $this->_getQueryString_activeCampusUser($ss_act, $ss_next);

		$qry = '
			WITH tmp_tbl AS ('. $qry_campus_user. ')
			
			SELECT tbl_alma.alma_match_id, tbl_alma.insertamum AS alma_insertamum, tmp_tbl.*, true as active
			FROM tmp_tbl
			LEFT JOIN sync.tbl_alma using (person_id)
			ORDER BY person_id
		';

		return $this->execQuery($qry);
	}

	/**
	 * Provide query string for active campus user prioritized by their 'role' at the campus.
	 * Prio: Mitarbeiter > Masterstudent > Bachelorstudent > Lehrgangsstudent > Sonstiges
	 * @return string
	 */
	private function _getQueryString_activeCampusUser($ss_act, $ss_next)
	{
		$qry_string = '
		SELECT DISTINCT ON (person_id) person_id, uid, vorname, nachname, titelpre, user_group_desc, insertamum, vornamen, titelpost, gebdatum,
		CASE
			WHEN geschlecht = \'m\' THEN \'MALE\'
			WHEN geschlecht = \'w\' THEN \'FEMALE\'
			WHEN geschlecht = \'x\' THEN \'OTHER\'
			ELSE \'NONE\'
		END AS geschlecht
		FROM (
			SELECT vorname, nachname, titelpre, person_id, uid, \'Student\' as user_group_desc, insertamum, vornamen, geschlecht, titelpost, gebdatum,
				CASE WHEN stg.typ=\'m\' THEN 2
				 	 WHEN stg.typ=\'b\' THEN 3
				 	 WHEN stg.typ=\'l\' THEN 4
			 	 ELSE 5
				 END as prio
			FROM	campus.vw_student
			JOIN 	public.tbl_studiengang stg USING (studiengang_kz)
			WHERE EXISTS (
				SELECT 1
				FROM public.tbl_prestudentstatus
				WHERE prestudent_id=vw_student.prestudent_id
				AND studiensemester_kurzbz IN('. $this->escape($ss_act). ', '. $this->escape($ss_next). ')';

		if(!is_null($this->excluded_study_programs) && !empty($this->excluded_study_programs))
		{
			$qry_string .= '
					AND studiengang_kz NOT IN ('. implode(',', $this->excluded_study_programs). ')
				';
		}

		$qry_string .= '
			)
			AND vw_student.aktiv

			UNION

			SELECT vorname, nachname, titelpre, person_id, uid, \'Mitarbeiter\' as user_group_desc, insertamum, vornamen, geschlecht, titelpost, gebdatum,
				1 as prio
			FROM campus.vw_mitarbeiter
			WHERE aktiv
			AND personalnummer > 0
		) a
		ORDER BY person_id, prio, insertamum DESC
		';

		return $qry_string;
	}
}