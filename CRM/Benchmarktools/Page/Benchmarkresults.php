<?php
use CRM_Benchmarktools_ExtensionUtil as E;

class CRM_Benchmarktools_Page_Benchmarkresults extends CRM_Core_Page {

  public function run() {
    $validFile = $emptyLog = FALSE;
    $config = CRM_Core_Config::singleton();
    // Get page actions
    $action = CRM_Utils_Request::retrieve('action', 'String', $this);
    $downloadFileName = CRM_Utils_Request::retrieve('filename', 'String', $this, FALSE, 0);

    if ($downloadFileName && !empty($action) && $action == CRM_Core_Action::VIEW) {
      $mimeType = 'text/csv';
      $path = $config->configAndLogDir . $downloadFileName;
      if (file_exists($path)) {
        $buffer = file_get_contents($path);
        $validFile = TRUE;
      }
      else {
        $validFile = FALSE;
        $urlRedirect = CRM_Utils_System::url('civicrm/admin/benchmark/results?reset=1');
        CRM_Core_Error::statusBounce(
          E::ts('It appears that the file is non-existing or unreadable'),
          $urlRedirect
        );
      }

      if ($validFile) {
        CRM_Utils_System::download(
          CRM_Utils_File::cleanFileName(basename($downloadFileName)),
          $mimeType,
          $buffer,
          NULL,
          TRUE,
          'inline'
        );
      }
    }
    elseif ($downloadFileName && !empty($action) && $action == CRM_Core_Action::DELETE) {
      $path = $config->configAndLogDir . $downloadFileName;

      if (file_exists($path)) {
        // Make sure that the file is actually a CSV extension
        $path_parts = pathinfo($path);
        if ($path_parts['extension'] == 'csv') {
          // Do the file deletion and redirect
          $validFile = TRUE;
        }

        if ($validFile) {
          if (!unlink($path)) {
            CRM_Core_Session::setStatus(E::ts('Unable to delete file %1', ['1' => $downloadFileName]), 'File deletion failed', 'error');
          }
          else {
            CRM_Core_Session::setStatus(E::ts('File %1 deleted successfully', ['1' => $downloadFileName]), 'File deletion successful', 'success');
          }
          CRM_Utils_System::redirect(
            CRM_Utils_System::url('civicrm/admin/benchmark/results?reset=1')
          );

        }
        else {
          $urlRedirect = CRM_Utils_System::url('civicrm/admin/benchmark/results?reset=1');
          CRM_Core_Error::statusBounce(
            E::ts('It appears that the file is non-existing or unreadable'),
            $urlRedirect
          );
        }
      }
      else {
        $urlRedirect = CRM_Utils_System::url('civicrm/admin/benchmark/results?reset=1');
        CRM_Core_Error::statusBounce(
          E::ts('It appears that the file is non-existing or unreadable'),
          $urlRedirect
        );
      }
    }

    $fTimeStamp = '-';
    $benchmarkFiles = $config->configAndLogDir;
    // Grab the REST services
    $csv_files = glob($benchmarkFiles . "/benchmark_job_results_*.{csv,txt,log}", GLOB_BRACE);
    $pattern = "/_([0-9].*)\.csv$/";
    // Reform the array but first check if it's empty
    if (empty($csv_files)) {
      $csv_opts = [];
      $emptyLog = TRUE;
    }
    else {
      foreach ($csv_files as $csv_key => $csv_file) {
        $csv_opts[$csv_file]['basename'] = basename($csv_file);
        // Extract the timestamp
        preg_match_all($pattern, $csv_opts[$csv_file]['basename'], $matches);
        if (array_key_exists('1', $matches)) {
          $extractedDate = $matches[1][0];
          $fTimeStamp = date("Y-m-d", strtotime($extractedDate));
        }
        $csv_opts[$csv_file]['timestamp'] = $fTimeStamp;
      }
    }

    CRM_Utils_System::setTitle(E::ts('Benchmark Results page'));

    $this->assign('csv_opts', $csv_opts);
    $this->assign('emptyLog', $emptyLog);

    parent::run();
  }

}
