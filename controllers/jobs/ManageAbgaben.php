<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

class ManageAbgaben extends JQW_Controller
{
	/**
	 * Controller initialization
	 */
	public function __construct()
	{
		parent::__construct();

		$this->load->library('extensions/FHC-Core-Alma/SyncAbgabenLib');
		$this->load->helper('extensions/FHC-Core-Alma/hlp_alma_common');
	}

	public function createAbgaben()
	{
		$this->logInfo('Start creating Abgaben-XML for ALMA');

		// Gets the latest jobs
		$lastJobs = $this->getLastJobs(SyncAbgabenLib::JOB_TYPE_ALMA_CREATE_XML_ABGABEN);

		if (isError($lastJobs))
		{
			$this->logError(getCode($lastJobs).': '.getError($lastJobs), SyncAbgabenLib::JOB_TYPE_ALMA_CREATE_XML_ABGABEN);
		}
		else
		{
			$syncResult = $this->syncabgabenlib->createAbgaben(mergeProjectIdArray(getData($lastJobs)));
			
			// Log the result
			if (isError($syncResult))
			{
				$this->logError(getCode($syncResult).': '.getError($syncResult));
			}
			else
			{
				$this->logInfo(getData($syncResult));
			}

			// Update jobs properties values
			$this->updateJobs(
				getData($lastJobs), // Jobs to be updated
				array(JobsQueueLib::PROPERTY_STATUS, JobsQueueLib::PROPERTY_END_TIME), // Job properties to be updated
				array(JobsQueueLib::STATUS_DONE, date('Y-m-d H:i:s')) // Job properties new values
			);

			if (hasData($lastJobs)) $this->updateJobsQueue(SyncAbgabenLib::JOB_TYPE_ALMA_CREATE_XML_ABGABEN, getData($lastJobs));
		}

		$this->logInfo('Finished creating Abgaben-XML for ALMA');
	}
}