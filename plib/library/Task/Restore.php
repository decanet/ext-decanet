<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

class Modules_Decanet_Task_Restore extends pm_LongTask_Task // Since Plesk 17.0
{
    public $hasDangerousMessage = true;
    public $trackProgress = true;
    public $runningBackups = [];

    public function run()
    {
        $this->runningBackups = $this->getParam('runningBackups', []);
        Modules_Decanet_Helper::check($this->runningBackups); // restore_lock is acquired inside check()
    }

    public function statusMessage()
    {
        switch ($this->getStatus()) {
            case static::STATUS_RUNNING:
                return pm_Locale::lmsg('restoreTaskRunning');
            case static::STATUS_DONE:
                return pm_Locale::lmsg('restoreTaskDone');
        }
        return '';
    }

    public function onDone()
    {
        pm_Settings::set('restore_lock', 0); // Just in case some troubles inside check()
    }
}