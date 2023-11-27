<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 */
class JQMScheduler extends JQW_Controller
{
	/**
	 * Controller initialization
	 */
	public function __construct()
	{
		parent::__construct();

		// Loads JQMSchedulerLib
		$this->load->library('extensions/FHC-Core-Alma/JQMSchedulerLib');
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods
	public function createAbgaben()
	{
		$this->logInfo('Start job queue scheduler FHC-Core-Alma->createAbgaben');

		// Generates the input for the new job
		$jobInputResult = $this->jqmschedulerlib->createAbgaben();

		// If an error occured then log it
		if (isError($jobInputResult))
		{
			$this->logError(getError($jobInputResult));
		}
		else
		{
			// If a job input were generated
			if (hasData($jobInputResult))
			{
				// Add the new job to the jobs queue
				$addNewJobResult = $this->addNewJobsToQueue(
					JQMSchedulerLib::JOB_TYPE_ALMA_CREATE_XML_ABGABEN, // job type
					$this->generateJobs(
						JobsQueueLib::STATUS_NEW,
						getData($jobInputResult)
					)
				);

				// If error occurred return it
				if (isError($addNewJobResult)) $this->logError(getError($addNewJobResult));
			}
			else // otherwise log info
			{
				$this->logInfo('There are no jobs to generate');
			}
		}

		$this->logInfo('End job queue scheduler FHC-Core-ALMA->createAbgaben');
	}

}
