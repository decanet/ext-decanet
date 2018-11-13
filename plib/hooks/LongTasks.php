<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

class Modules_Decanet_LongTasks extends pm_Hook_LongTasks
{
    public function getLongTasks()
    {
        return [new Modules_Decanet_Task_Restore()];
    }
}