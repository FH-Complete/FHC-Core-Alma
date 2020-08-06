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
	 * Get all present user.
	 * Present user is active campus user that is also present in alma yet.
	 */
	public function getPresentUser($ss_act, $ss_next)
	{
		$active_campus_user = $this->_getQueryString_activeCampusUser($ss_act, $ss_next);

		$qry = '
			SELECT person_id FROM ('. $active_campus_user. ') AS campus
			INTERSECT
			SELECT person_id FROM sync.tbl_alma AS alma
			ORDER BY person_id;
		';

		return $this->execQuery($qry);
	}

	/**
	 * Get all outdated user.
	 * Outdated user is alma user that is not present in active campus user anymore.
	 */
	public function getOutdatedUser($ss_act, $ss_next)
	{
		$campus_user = $this->_getQueryString_activeCampusUser($ss_act, $ss_next);

		$qry = '
			SELECT person_id FROM sync.tbl_alma AS alma
			EXCEPT
			SELECT person_id FROM ('. $campus_user. ') AS campus
			ORDER BY person_id;
		';

		return $this->execQuery($qry);

	}

	/**
	 * Get all active campus user data with the corresponding alma match id.
	 * Campus user are retrieved unique by their prioritized role.
	 * @return mixed
	 */
	public function getActiveCampusUserData($ss_act, $ss_next)
	{
		$qry_campus_user = $this->_getQueryString_activeCampusUser($ss_act, $ss_next);

		$qry = '
			WITH tmp_tbl AS ('. $qry_campus_user. ')
			
			SELECT tbl_alma.alma_match_id, tbl_alma.insertamum AS alma_insertamum, tmp_tbl.*
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
		return '
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
				AND studiensemester_kurzbz IN('. $this->escape($ss_act). ', '. $this->escape($ss_next). ')
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
	}
}