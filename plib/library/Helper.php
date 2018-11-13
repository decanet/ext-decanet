<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

class Modules_Decanet_Helper
{
    public static function check($backups = [])
    {
        pm_Context::init('decanet');
        $has_ftp_backup = true;
        $has_sql_backup = true;
        if($backups) {
            $nb_backups_on_list = count($backups);
            $nb_backups_end = 0;
            $result = pm_ApiCli::callSbin('backups.sh',  array('status', 'ftp'), pm_ApiCli::RESULT_FULL);
            $running_backups = trim($result['stdout']);
            if($running_backups) {
                $running_backups = explode("\n", $running_backups);
                foreach ($backups as $backup) {
                    if(!in_array($backup['backup_name'], $running_backups) && $backup['backup_type'] == 'ftp')
                        $nb_backups_end++;
                }
            } else {
                $has_ftp_backup = false;
            }

            $result = pm_ApiCli::callSbin('backups.sh',  array('status', 'sql'), pm_ApiCli::RESULT_FULL);
            $running_backups_sql = trim($result['stdout']);
            if($running_backups_sql) {
                $running_backups_sql = explode("\n", $running_backups_sql);
                foreach ($backups as $backup) {
                    if(!in_array($backup['backup_name'], $running_backups_sql) && $backup['backup_type'] == 'sql')
                        $nb_backups_end++;
                }
            } else {
                $has_sql_backup = false;
            }
            
            $taskManager = new pm_LongTask_Manager();
            if($has_ftp_backup || $has_sql_backup) {
                
                $progress = ($nb_backups_end / $nb_backups_on_list) * 100;
                $tasks = $taskManager->getTasks(['task_restore']);
                foreach ($tasks as $task) {
                    $task->updateProgress($progress);
                }
            } else {
                $tasks = $taskManager->getTasks(['task_restore']);
                foreach ($tasks as $task) {
                    $task->updateProgress(100);
                }
                $restoreTask = new Modules_Decanet_Task_Restore();
                $restoreTask->setParam('runningBackups', []);
                $taskManager->cancelAllTasks();
            }
            
            if($nb_backups_end == $nb_backups_on_list) {
                $restoreTask = new Modules_Decanet_Task_Restore();
                $restoreTask->setParam('runningBackups', []);
                $taskManager->cancelAllTasks();
            }

            pm_Settings::set('restore_lock', 0);
        } else {
            $taskManager = new pm_LongTask_Manager();
            $taskManager->cancelAllTasks();
            $restoreTask = new Modules_Decanet_Task_Restore();
            $restoreTask->setParam('runningBackups', []);
        }
    }
}