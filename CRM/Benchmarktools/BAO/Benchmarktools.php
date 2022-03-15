<?php

class CRM_Benchmarktools_BAO_Benchmarktools {

  /**
   * getRandomNames
   *
   * Helper function to output random names into an array
   *
   * @param  mixed $count
   * @return void
   */
  public static function getRandomNames($count = 1) {
    $intCount = 0;
    $data = [];
    while ($intCount < $count) {
      $randomName = self::randomNameGenerator();
      $row = [
        'first_name' => $randomName['first_name'],
        'last_name' => $randomName['last_name'],
      ];
      // Generate data
      $data[] = $row;
      $intCount++;
    }

    // End
    return $data;

  }

  /**
   * randomNameGenerator
   *
   * Helper function to generate random names
   *
   * @return void
   */
  public static function randomNameGenerator() {
    $data = [];
    $firstname = [
      'Johnathon',
      'Anthony',
      'Erasmo',
      'Raleigh',
      'Nancie',
      'Tama',
      'Camellia',
      'Augustine',
      'Christeen',
      'Luz',
      'Diego',
      'Lyndia',
      'Thomas',
      'Georgianna',
      'Leigha',
      'Alejandro',
      'Marquis',
      'Joan',
      'Stephania',
      'Elroy',
      'Zonia',
      'Buffy',
      'Sharie',
      'Blythe',
      'Gaylene',
      'Elida',
      'Randy',
      'Margarete',
      'Margarett',
      'Dion',
      'Tomi',
      'Arden',
      'Clora',
      'Laine',
      'Becki',
      'Margherita',
      'Bong',
      'Jeanice',
      'Qiana',
      'Lawanda',
      'Rebecka',
      'Maribel',
      'Tami',
      'Yuri',
      'Michele',
      'Rubi',
      'Larisa',
      'Lloyd',
      'Tyisha',
      'Samatha',
    ];

    $lastname = [
      'Mischke',
      'Serna',
      'Pingree',
      'Mcnaught',
      'Pepper',
      'Schildgen',
      'Mongold',
      'Wrona',
      'Geddes',
      'Lanz',
      'Fetzer',
      'Schroeder',
      'Block',
      'Mayoral',
      'Fleishman',
      'Roberie',
      'Latson',
      'Lupo',
      'Motsinger',
      'Drews',
      'Coby',
      'Redner',
      'Culton',
      'Howe',
      'Stoval',
      'Michaud',
      'Mote',
      'Menjivar',
      'Wiers',
      'Paris',
      'Grisby',
      'Noren',
      'Damron',
      'Kazmierczak',
      'Haslett',
      'Guillemette',
      'Buresh',
      'Center',
      'Kucera',
      'Catt',
      'Badon',
      'Grumbles',
      'Antes',
      'Byron',
      'Volkman',
      'Klemp',
      'Pekar',
      'Pecora',
      'Schewe',
      'Ramage',
    ];

    $data['first_name'] = $firstname[rand(0, count($firstname) - 1)];
    $data['last_name'] = $lastname[rand(0, count($lastname) - 1)];

    return $data;
  }

  /**
   * checkExtensionInstalled
   *
   * @param  mixed $key
   * @return void
   */
  public static function checkExtensionInstalled($key) {
    $installed = FALSE;

    $result = civicrm_api3('Extension', 'get', [
      'key' => $key,
    ]);
    if ($result['count'] == 1) {
      // We got the key, now lets check if it is installed
      $extID = $result['id'];
      if ($result['values'][$extID]['status'] == 'installed') {
        $installed = TRUE;
      }
    }

    return $installed;
  }

  /**
   * exportRecords
   * Exports the records into a CSV file
   *
   * @param  mixed $title
   * @param  mixed $data
   * @return void
   */
  public static function exportRecords($title, $data) {
    $separator = ';';

    $config = CRM_Core_Config::singleton();
    $resPath = $config->configAndLogDir;
    $resFile = $resPath . "benchmark_job_results_" . date('Ymd') . ".csv";
    // Get server URL
    $serverURL = parse_url(CIVICRM_UF_BASEURL, PHP_URL_HOST);
    // If the file is not there, lets create a header first
    if (!file_exists($resFile)) {
      $header = [
        'timestamp',
        'benchmark',
        'process_time_(in_seconds)',
        'count',
        'server_url',
      ];
      file_put_contents($resFile, implode($separator, $header) . PHP_EOL, FILE_APPEND);
    }
    // Proceed with the actual data
    $outputCSV = [
      'timestamp' => date('Y-m-d H:i:s'),
      'title' => $title,
      'process_time' => $data['process_time'],
      'count' => (!empty($data['count']) ? $data['count'] : 0),
      'server_url' => $serverURL,
    ];
    file_put_contents($resFile, implode($separator, $outputCSV) . PHP_EOL, FILE_APPEND);

  }

  public static function showConsoleOutput($string, $showOutput) {

    if ($showOutput) {
      print $string . PHP_EOL;
    }
    return;
  }

}
