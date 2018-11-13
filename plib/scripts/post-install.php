<?php
pm_Context::init('decanet');

$tasks = pm_Scheduler::getInstance()->listTasks();
foreach ($tasks as $task) {
    if ('restore-periodic-task.php' == $task->getCmd()) {
        pm_Settings::set('restore_periodic_task_id', $task->getId());
        return;
    }
}

$task = new pm_Scheduler_Task();
$task->setSchedule(pm_Scheduler::$EVERY_MIN);
$task->setCmd('restore-periodic-task.php');
pm_Scheduler::getInstance()->putTask($task);
pm_Settings::set('restore_periodic_task_id', $task->getId());

if (false !== ($upgrade = array_search('upgrade', $argv))) {
    $upgradeVersion = $argv[$upgrade + 1];
    echo "upgrading from version $upgradeVersion\n";

    if (version_compare($upgradeVersion, '1.2') < 0) {
        pm_Bootstrap::init();
        $id = pm_Bootstrap::getDbAdapter()->fetchOne("select val from misc where param = 'moduleDecanetCustomButton'");
        pm_Bootstrap::getDbAdapter()->delete('misc', array("param = 'moduleDecanetCustomButton'"));
        pm_Settings::set('customButtonId', $id);
    }

    echo "done\n";
    exit(0);
}

$iconPath = rtrim(pm_Context::getHtdocsDir(), '/') . '/images/icon_16.gif';
$baseUrl = pm_Context::getBaseUrl();

$request = <<<APICALL
<ui>
   <create-custombutton>
         <owner>
            <admin/>
         </owner>
      <properties>
         <file>$iconPath</file>
         <internal>true</internal>
         <noframe>true</noframe>
         <place>navigation</place>
         <url>$baseUrl</url>
         <text>Decanet</text>
      </properties>
   </create-custombutton>
</ui>
APICALL;

try {
    $response = pm_ApiRpc::getService()->call($request);
    $result = $response->ui->{"create-custombutton"}->result;
    if ('ok' == $result->status) {
        pm_Settings::set('customButtonId', $result->id);
        echo "done\n";
        exit(0);
    } else {
        echo "error $result->errcode: $result->errtext\n";
        exit(1);
    }
} catch(PleskAPIParseException $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}
