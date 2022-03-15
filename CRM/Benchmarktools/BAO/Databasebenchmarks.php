<?php

use CRM_Benchmarktools_BAO_Benchmarktools as B;
class CRM_Benchmarktools_BAO_Databasebenchmarks {

  public static $tableName = "benchmarktools_table";

  /**
   * runTest
   *
   * This is the main function that is responsible for the actual mysql tests
   *
   * @param  mixed $type
   * @param  mixed $count
   * @return void
   */
  public static function runTest($type, $count = 1) {

    $tableName = self::$tableName;

    // Create the table but drop it if it exists
    self::tableOperations('drop');
    self::tableOperations('create');
    $insertStatement = "INSERT INTO {$tableName} (first_name, last_name) ";
    $data = B::getRandomNames($count);
    if ($type == 'single') {
      // Single inserts of x counts
      $insertData = self::convertToInsert($data, $insertStatement, 'single');
    }
    elseif ($type == 'massive') {
      // Batch inserts of x counts
      $insertData = self::convertToInsert($data, $insertStatement, 'massive');
    }

    // Count the statistics
    $starttime = microtime(TRUE);
    foreach ($insertData as $insertStatus) {
      CRM_Core_DAO::executeQuery($insertStatus);
    }
    $stepTime = microtime(TRUE);
    $execTime = number_format((float) ($stepTime - $starttime), 3);

    $results = [
      'process_time' => $execTime,
      'type' => $type,
      'count' => $count,
    ];

    // Finish, drop the table
    self::tableOperations('drop');
    return $results;
  }

  /**
   * getRandomReads
   *
   * Based on https://www.warpconduit.net/2011/03/23/selecting-a-random-record-using-mysql-benchmark-results/ , we use this function
   * to measure how fast we can do X SELECT statements
   *
   * @param  mixed $count
   * @return void
   */
  public static function getRandomReads($count) {

    $intCount = 0;
    $tableName = self::$tableName;
    $results = $avgCount = [];

    // Number of random records to generate, if too high (eg 1M), it will cause a `max_allowed_packet` error
    $generateContacts = 500000;

    // Prepare the tables
    self::tableOperations('drop');
    self::tableOperations('create');
    // Prepare the random data
    $insertStatement = "INSERT INTO {$tableName} (first_name, last_name) ";
    $data = B::getRandomNames($generateContacts);
    $insertData = self::convertToInsert($data, $insertStatement, 'massive');
    foreach ($insertData as $insertStatus) {
      CRM_Core_DAO::executeQuery($insertStatus);
    }
    // Random reads of count X
    // We use this query below, taken from https://www.warpconduit.net/2011/03/23/selecting-a-random-record-using-mysql-benchmark-results/
    // Credits go to this fella
    $randomReadSQL = "SELECT * FROM {$tableName} WHERE RAND()<(SELECT ((1/COUNT(*))*10) FROM {$tableName}) ORDER BY RAND() LIMIT 1";
    $starttime = microtime(TRUE);
    while ($intCount < $count) {
      CRM_Core_DAO::executeQuery($randomReadSQL);
      $intCount++;
    }
    $stepTime = microtime(TRUE);
    $execTime = number_format((float) ($stepTime - $starttime), 3);

    $results = [
      'process_time' => $execTime,
      'type' => 'random read',
      'count' => $count,
    ];
    // Finished, drop the table
    self::tableOperations('drop');

    return $results;
  }

  /**
   * tableOperations
   *
   * Helper function to create or drop the custom table
   *
   * @param  mixed $mode
   * @return void
   */
  public static function tableOperations($mode) {
    $tableName = self::$tableName;

    switch ($mode) {
      case 'create':
        $tableDDL = "
        CREATE TABLE {$tableName} (
          id INT auto_increment NOT NULL,
          first_name varchar(100) NULL,
          last_name varchar(100) NULL,
          primary key (id),
          INDEX (id)
        )
        ENGINE=InnoDB";
        break;

      case 'drop':
        $tableDDL = "DROP TABLE IF EXISTS {$tableName}";
        break;

    }

    CRM_Core_DAO::executeQuery($tableDDL);

    return;
  }

  /**
   * convertToInsert
   *
   * Helper function that converts array to insert statements
   *
   * @param  mixed $data
   * @param  mixed $insertHeader
   * @param  mixed $type
   * @return void
   */
  public static function convertToInsert($data, $insertHeader, $type) {
    $insertStatement = NULL;
    $insertLine = $output = [];

    if ($type == 'single') {
      //$insertStatement = $insertHeader . "VALUES\n";
      foreach ($data as $row) {
        $insertStatement = $insertHeader . "VALUES ('{$row['first_name']}', '{$row['last_name']}')\n";
        $output[] = $insertStatement;
      }

    }
    elseif ($type == 'massive') {
      $insertStatement = $insertHeader . "VALUES\n";
      foreach ($data as $row) {
        $insertLine[] = " ('{$row['first_name']}', '{$row['last_name']}')";
      }
      $insertStatement .= implode(",\n", $insertLine) . ';';
      $output[] = $insertStatement;
    }
    return $output;
  }

}
