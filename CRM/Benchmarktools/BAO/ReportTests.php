<?php

class CRM_Benchmarktools_BAO_ReportTests {

  /**
   * compileReport
   *
   * Function that will render a report internally so that we can measure how long it took to produce it
   *
   * @param  mixed $reportClass
   * @return void
   */
  public static function compileReport($reportClass) {

    $outputData = [];

    $starttime = microtime(TRUE);
    $inputParams = NULL;
    $obj = self::getReportObject($reportClass, $inputParams);
    $queryParams = $obj->getParams();
    // Modify query params
    $obj->setParams($queryParams);
    $sql = $obj->buildQuery(TRUE);

    // Dirty way to alter the query
    // TODO: Fix it properly, need to investigate $queryParams
    $stripString = "AND (`contact_civireport`.`id` IS NULL OR (`contact_civireport`.`id` IN (SELECT contact_id FROM civicrm_acl_contact_cache WHERE user_id = 0)))";
    $sql = str_replace($stripString, "", $sql);

    if ($reportClass == 'CRM_Report_Form_Contact_Summary') {
      $stripLimit = "LIMIT 0, 50";
      $sql = str_replace($stripLimit, "", $sql);
    }

    if ($reportClass == 'CRM_Report_Form_Contribute_Summary') {
      $stripLimit = "LIMIT 0, 50";
      $sql = str_replace($stripLimit, "", $sql);

      $badCurrency = "contribution_civireport.contribution_status_id, currency";
      $goodCurrency = "contribution_civireport.contribution_status_id, civicrm_contribution_currency";
      $sql = str_replace($badCurrency, $goodCurrency, $sql);

    }
    // Continue
    $rows = [];
    $obj->buildRows($sql, $rows);
    $stepTime = microtime(TRUE);
    $execTime = number_format((float) ($stepTime - $starttime), 3);
    $outputData['build_rows'] = $execTime;

    $obj->formatDisplay($rows);
    $stepTime = microtime(TRUE);
    $execTime = number_format((float) ($stepTime - $starttime), 3);
    $outputData['formatDisplay'] = $execTime;
    $rowCount = count($rows);
    $outputData['row_count'] = $rowCount;

    $stepTime = microtime(TRUE);
    $execTime = number_format((float) ($stepTime - $starttime), 3);
    $outputData['process_time'] = $execTime;

    return $outputData;
  }

  /**
   * getReportObject
   *
   * @param  mixed $reportClass
   * @param  mixed $inputParams
   * @return void
   */
  public function getReportObject($reportClass, $inputParams) {
    $config = CRM_Core_Config::singleton();
    $config->keyDisable = TRUE;
    $controller = new CRM_Core_Controller_Simple($reportClass, ts('some title'));
    $tmpReportVal = explode('_', $reportClass);
    $reportName = array_pop($tmpReportVal);
    $reportObj =& $controller->_pages[$reportName];

    $tmpGlobals = [];
    $tmpGlobals['_REQUEST']['force'] = 1;
    $tmpGlobals['_GET'][$config->userFrameworkURLVar] = 'civicrm/placeholder';
    $tmpGlobals['_SERVER']['QUERY_STRING'] = '';
    if (!empty($inputParams['fields'])) {
      $fields = implode(',', $inputParams['fields']);
      $tmpGlobals['_GET']['fld'] = $fields;
      $tmpGlobals['_GET']['ufld'] = 1;
    }
    if (!empty($inputParams['filters'])) {
      foreach ($inputParams['filters'] as $key => $val) {
        $tmpGlobals['_GET'][$key] = $val;
      }
    }
    if (!empty($inputParams['group_bys'])) {
      $groupByFields = implode(' ', $inputParams['group_bys']);
      $tmpGlobals['_GET']['gby'] = $groupByFields;
    }

    CRM_Utils_GlobalStack::singleton()->push($tmpGlobals);

    try {
      $reportObj->storeResultSet();
      $reportObj->buildForm();
    }
    catch (Exception $e) {
      // print_r($e->getCause()->getUserInfo());
      CRM_Utils_GlobalStack::singleton()->pop();
      throw $e;
    }
    CRM_Utils_GlobalStack::singleton()->pop();

    return $reportObj;
  }

}
