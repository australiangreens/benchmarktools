<?php
use CRM_Benchmarktools_ExtensionUtil as E;

/**
 * Benchmarktools.Smartgroups API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * show_output BOOLEAN
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_benchmarktools_Smartgroups_spec(&$spec) {
  $spec['show_output'] = [
    'title' => 'Show output',
    'description' => 'Show the function output to the console',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_BOOLEAN,
  ];
  $spec['limit_sg'] = [
    'title' => 'Limit smartgroups',
    'description' => 'Limit smartgroup recalculation to X groups',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  ];

}

/**
 * Benchmarktools.Smartgroups API
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
function civicrm_api3_benchmarktools_Smartgroups($params) {
  $showOutput = FALSE;
  if (array_key_exists('show_output', $params)) {
    $showOutput = TRUE;
  }
  $output = $sgParams = [];

  // count of groups
  $starttime = microtime(TRUE);
  $groupCount = _countDynamicGroups();
  $stepTime = microtime(TRUE);
  $execution_time = number_format((float) ($stepTime - $starttime), 3);
  _consoleOutput("-- {$execution_time} - Count of CiviCRM Static Groups: " . $groupCount, $showOutput);
  $output['count_groups'] = [
    'count' => $groupCount,
    'time' => $execution_time,
  ];
  // Flush system
  $result = civicrm_api3('System', 'flush');
  $stepTime = microtime(TRUE);
  $execution_time = number_format((float) ($stepTime - $starttime), 3);
  _consoleOutput("-- {$execution_time} - Flushing of system caches", $showOutput);
  $output['system_flush_cache'] = [
    'time' => $execution_time,
    'api_output' => $result,
  ];

  // Cleanup of smartgroups
  $result = civicrm_api3('Job', 'group_cache_flush');
  $stepTime = microtime(TRUE);
  $execution_time = number_format((float) ($stepTime - $starttime), 3);
  _consoleOutput("-- {$execution_time} - Cleanup of smart group caches", $showOutput);
  $output['clearing_smartgroup_cache'] = [
    'time' => $execution_time,
    'api_output' => $result,
  ];
  // Recreation of smartgroup caches
  if (array_key_exists('limit_sg', $params) && $params['limit_sg'] != 0) {
    $sgParams = ['limit' => $params['limit_sg']];
  }
  $result = civicrm_api3('Job', 'group_rebuild', $sgParams);
  $stepTime = microtime(TRUE);
  $execution_time = number_format((float) ($stepTime - $starttime), 3);
  _consoleOutput("-- {$execution_time} - Recreation of smart group caches", $showOutput);
  $output['recreating_smartgroup_cache'] = [
    'time' => $execution_time,
    'api_output' => $result,
  ];

  $stepTime = microtime(TRUE);
  $execution_time = number_format((float) ($stepTime - $starttime), 3);
  $output['total_time'] = $execution_time;

  return civicrm_api3_create_success($output, $params, 'Benchmark', 'Smartgroups');
}

function _countDynamicGroups() {
  $groupCount = 0;
  $query = "select count(*) as group_count from civicrm_group";
  $query .= ' WHERE saved_search_id IS NOT NULL';

  $dao = CRM_Core_DAO::executeQuery($query);
  while ($dao->fetch()) {
    $groupCount = $dao->group_count;
  }
  return $groupCount;

}

function _consoleOutput($string, $showOutput) {

  if ($showOutput) {
    print $string . PHP_EOL;
  }
  return;
}
