<?php

namespace CWP\Cwp\Task;

use CWP\Cwp\Task\CleanupGeneratedPdfBuildTask,
    SilverStripe\Dev\BuildTask;

class CleanupGeneratedPdfDailyTask extends BuildTask
{

    /**
     * @param HTTPRequest $request
     * @return void
     */
    public function run($request)
    {
        $task = new CleanupGeneratedPdfBuildTask();
        $task->run(null);
    }

}
