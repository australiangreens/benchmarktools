<?php
use CRM_Benchmarktools_ExtensionUtil as E;

/**
 * Benchmarktools.Run API specification (optional)
 * Call it like this:
 * `drush cvapi benchmarktools.run`
 *
 * If you want to view the output on the console, execute
 * `drush cvapi benchmarktools.run show_output=1`
 *
 * If you want to skip specific tests, add them on the `exclude` variable like this:
 * `drush cvapi benchmarktools.run show_output=1 exclude=smartgroups,synopsis`
 *
 * If you would like to add a limit to the number of smartgroups that you need to recalculate,
 * you can supply the parameter `limitSG`, like `limitSG=10`, where 10 is the number of max groups
 * that you want to recalculate. Example call:
 * `drush cvapi benchmarktools.run show_output=1 exclude=mysql,synopsis limitSG=10`
 *
 *
 * Those are the tests that can be used on the exclude variable:
 *
 * synopsis
 * smartgroups
 * mysql
 * report_contact
 * report_contribution
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_benchmarktools_run_spec(&$spec) {
  $spec['show_details'] = [
    'title' => 'Show details',
    'description' => 'Show more detailed output',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $spec['exclude'] = [
    'title' => 'Exclude specific tests',
    'description' => 'Exclude specific tests',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['limitSG'] = [
    'title' => 'Limit SmartGroup rebuild',
    'description' => 'imit smartgroup recalculation to X groups',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * Benchmarktools.Run API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_benchmarktools_run($params) {

  $logOutput = new CRM_Benchmarktools_BAO_Benchmarktools();

  $showOutput = FALSE;
  $limitSG = NULL;
  // Prepare the test skipping variables
  $skipSynopsis = $skipReportContact = $skipReportContrib = $skipSmartGroups = $skipMySQL = FALSE;
  if (array_key_exists('show_output', $params)) {
    $showOutput = TRUE;
  }
  if (array_key_exists('limitSG', $params)) {
    $limitSG = $params['limitSG'];
  }
  if (array_key_exists('exclude', $params) && !empty($params['exclude'])) {
    $excludeTestArr = explode(",", $params['exclude']);
    if (in_array('synopsis', $excludeTestArr)) {
      $skipSynopsis = TRUE;
    }
    if (in_array('report_contact', $excludeTestArr)) {
      $skipReportContact = TRUE;
    }
    if (in_array('report_contribution', $excludeTestArr)) {
      $skipReportContrib = TRUE;
    }
    if (in_array('smartgroups', $excludeTestArr)) {
      $skipSmartGroups = TRUE;
    }
    if (in_array('mysql', $excludeTestArr)) {
      $skipMySQL = TRUE;
    }
  }

  $outputData = $limitSGParams = [];

  if (!$skipReportContact) {
    $logOutput->showConsoleOutput('Benchmarking report: Contact Summary', $showOutput);
    $contactReportSummary = CRM_Benchmarktools_BAO_ReportTests::compileReport('CRM_Report_Form_Contact_Summary');
    $outputData['contact_summary_report'] = [
      'count' => $contactReportSummary['row_count'],
      'process_time' => $contactReportSummary['process_time'],
      'detailed_processes' => $contactReportSummary,
    ];
    $logOutput->exportRecords('Contact summary report', $outputData['contact_summary_report']);

  }
  if (!$skipReportContrib) {
    $logOutput->showConsoleOutput('Benchmarking report: Contribution Summary', $showOutput);
    $contribReportSummary = CRM_Benchmarktools_BAO_ReportTests::compileReport('CRM_Report_Form_Contribute_Summary');
    $outputData['contribution_summary_report'] = [
      'count' => $contribReportSummary['row_count'],
      'process_time' => $contribReportSummary['process_time'],
      'detailed_processes' => $contribReportSummary,
    ];
    $logOutput->exportRecords('Contribution summary report', $outputData['contribution_summary_report']);
  }

  // Check for synopsis
  if ($logOutput->checkExtensionInstalled('synopsis')) {
    if (!$skipSynopsis) {
      $logOutput->showConsoleOutput('Benchmarking Synopsis', $showOutput);
      $result = civicrm_api3('Synopsis', 'calculate');
      $outputData['synopsis']['process_time'] = $result['values']['process_time'];
      $logOutput->exportRecords('Synopsis', $outputData['synopsis']);
    }

  }

  if (!$skipSmartGroups) {
    // Smartgroups
    $logOutput->showConsoleOutput('Benchmarking Smartgroup cache deletion/recreation', $showOutput);
    if ($limitSG) {
      $limitSGParams = ['limit_sg' => $limitSG];
    }
    $result = civicrm_api3('Benchmarktools', 'smartgroups', $limitSGParams);
    $outputData['smartgroups_system_flush_cache']['process_time'] = $result['values']['system_flush_cache']['time'];
    $outputData['smartgroups_system_flush_cache']['count'] = 0;
    $logOutput->exportRecords('Smartgroup - system flush cache', $outputData['smartgroups_system_flush_cache']);

    $outputData['smartgroups_clearing_smartgroup_cache']['process_time'] = $result['values']['clearing_smartgroup_cache']['time'];
    $outputData['smartgroups_clearing_smartgroup_cache']['count'] = 0;
    $logOutput->exportRecords('Smartgroup - Clearing cache', $outputData['smartgroups_clearing_smartgroup_cache']);

    $outputData['smartgroups_recalculation']['process_time'] = $result['values']['recreating_smartgroup_cache']['time'];
    $outputData['smartgroups_recalculation']['count'] = (isset($limitSG) ? $limitSG : $result['values']['count_groups']['count']);
    $logOutput->exportRecords('Smartgroup - Recalculation', $outputData['smartgroups_recalculation']);
  }

  if (!$skipMySQL) {
    // Run MySQL INSERT benchmarks
    $logOutput->showConsoleOutput('Benchmarking 1000 single MySQL Insert statements', $showOutput);
    $mInsSingle = CRM_Benchmarktools_BAO_Databasebenchmarks::runTest('single', 1000);
    $outputData['mysql_single_insert_1000']['process_time'] = $mInsSingle['process_time'];
    $outputData['mysql_single_insert_1000']['count'] = 1000;
    $logOutput->exportRecords('1000 MySQL Single INSERT statements', $outputData['mysql_single_insert_1000']);

    $logOutput->showConsoleOutput('Benchmarking 1000 batch MySQL Insert statements', $showOutput);
    $mInsMassive = CRM_Benchmarktools_BAO_Databasebenchmarks::runTest('massive', 1000);
    $outputData['mysql_batch_insert_1000']['process_time'] = $mInsMassive['process_time'];
    $outputData['mysql_batch_insert_1000']['count'] = 1000;
    $logOutput->exportRecords('1000 MySQL Bulk INSERT statements', $outputData['mysql_batch_insert_1000']);

    $logOutput->showConsoleOutput('Benchmarking 10K single MySQL Insert statements', $showOutput);
    $mInsSingle = CRM_Benchmarktools_BAO_Databasebenchmarks::runTest('single', 10000);
    $outputData['mysql_single_insert_10K']['process_time'] = $mInsSingle['process_time'];
    $outputData['mysql_single_insert_10K']['count'] = 10000;
    $logOutput->exportRecords('10K MySQL Single INSERT statements', $outputData['mysql_single_insert_10K']);

    $logOutput->showConsoleOutput('Benchmarking 10K batch MySQL Insert statements', $showOutput);
    $mInsMassive = CRM_Benchmarktools_BAO_Databasebenchmarks::runTest('massive', 10000);
    $outputData['mysql_batch_insert_10K']['process_time'] = $mInsMassive['process_time'];
    $outputData['mysql_batch_insert_10K']['count'] = 10000;
    $logOutput->exportRecords('10K MySQL Bulk INSERT statements', $outputData['mysql_batch_insert_10K']);

    $logOutput->showConsoleOutput('Benchmarking 100K single MySQL Insert statements', $showOutput);
    $mInsSingle = CRM_Benchmarktools_BAO_Databasebenchmarks::runTest('single', 100000);
    $outputData['mysql_single_insert_100K']['process_time'] = $mInsSingle['process_time'];
    $outputData['mysql_single_insert_100K']['count'] = 100000;
    $logOutput->exportRecords('100K MySQL Single INSERT statements', $outputData['mysql_single_insert_100K']);

    $logOutput->showConsoleOutput('Benchmarking 100K batch MySQL Insert statements', $showOutput);
    $mInsMassive = CRM_Benchmarktools_BAO_Databasebenchmarks::runTest('massive', 100000);
    $outputData['mysql_batch_insert_100K']['process_time'] = $mInsMassive['process_time'];
    $outputData['mysql_batch_insert_100K']['count'] = 100000;
    $logOutput->exportRecords('100K MySQL Bulk INSERT statements', $outputData['mysql_batch_insert_100K']);

    $logOutput->showConsoleOutput('Benchmarking 100 random MySQL reads on 500k records', $showOutput);
    $mRndRead = CRM_Benchmarktools_BAO_Databasebenchmarks::getRandomReads(100);
    $outputData['mysql_random_100_reads']['process_time'] = $mRndRead['process_time'];
    $outputData['mysql_random_100_reads']['detailed_processes'] = $mRndRead;
    $outputData['mysql_random_100_reads']['count'] = $mRndRead['count'];
    $logOutput->exportRecords('100 MySQL random SELECT statements', $outputData['mysql_random_100_reads']);

    $logOutput->showConsoleOutput('Benchmarking 1000 random MySQL reads on 500k records', $showOutput);
    $mRndRead = CRM_Benchmarktools_BAO_Databasebenchmarks::getRandomReads(1000);
    $outputData['mysql_random_1000_reads']['process_time'] = $mRndRead['process_time'];
    $outputData['mysql_random_1000_reads']['detailed_processes'] = $mRndRead;
    $outputData['mysql_random_1000_reads']['count'] = $mRndRead['count'];
    $logOutput->exportRecords('1000 MySQL random SELECT statements', $outputData['mysql_random_1000_reads']);
  }

  return civicrm_api3_create_success($outputData, $params, 'Benchmarktools', 'run');
}
