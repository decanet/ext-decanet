<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
pm_Context::init('decanet');
$restoreTask = new Modules_Decanet_Task_Restore();
$runningbackups = $restoreTask->getParam('runningBackups', []);
Modules_Decanet_Helper::check($runningbackups);
