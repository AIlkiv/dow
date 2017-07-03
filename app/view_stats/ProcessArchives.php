<?php

class ProcessArchives
{
    private $app;

    protected $maxDowloadArchives = 0;
    protected $maxUnpackArchives = 0;

    public function __construct($app)
    {
        $this->app = $app;
    }

    protected function execute()
    {
        echo "Start process_archives\n";

        if ($this->settings['view_stats_refresh_status'] != 'process_archives') {
            return;
        }

        $processDownload = new Process('php '.ROOT_DIR.'admin/step_2.php');
        $processDownload->setTimeout(3600);
        $processDownload->start($callbackProcessDownload);

        $processUnpack = new Process('php '.ROOT_DIR.'admin/step_3.php');
        $processUnpack->setTimeout(3600);
        $processUnpack->start($callbackProcessDownload);

        while (true) {
            if (!$this->toContinue()) {
                $haveStartedScripts = isRunScript('step_2.php');
                if (!empty($haveStartedScripts)) {
                    foreach ($haveStartedScripts as $pid) {
                        exec("kill $pid");
                    }
                }

                $haveStartedScripts = isRunScript('step_3.php');
                if (!empty($haveStartedScripts)) {
                    foreach ($haveStartedScripts as $pid) {
                        exec("kill $pid");
                    }
                }
                break;
            }

            if (!$processDownload->isRunning()) {
                $processDownload = $processDownload->restart($callbackProcessDownload);
            }

            if (!$processUnpack->isRunning()) {
                $processUnpack = $processUnpack->restart($callbackProcessDownload);
            }

            sleep(10);
        }

        if (!$this->toContinue() && $this->app->settings()->get('refresh_status') == 'process_archives') {
            $this->app->settings()->update('refresh_status', 'create_views');
        }

        echo "Finish process_archives\n";
        return;
    }

    private function toContinue()
    {
        $this->app->settings()->refresh();

        if (time() - strtotime($this->app->settings()->get('last_change')) > 60*60) {
            $this->app->settings()->update('refresh_status', 'manual_mode');
        }

        if ($this->app->settings()->get('refresh_status') != 'process_archives') {
            return false;
        }

        $prevMonth = date('Y-m-01', strtotime('-1 month', strtotime($this->app->settings()->get('cur_date'))));
        $_dateCounts = $this->app->db()->query("SELECT COUNT(*) FROM archives_log WHERE status IN ('download', 'in_tmp_archive') AND `date` >= '{$prevMonth}'")->fetchColumn();

        if (!empty($_dateCounts)) {
            return true;
        }

        return false;
    }

}