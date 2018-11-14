<?php

class BackupController extends pm_Controller_Action
{
    var $api = null;
    public function init()
    {
        parent::init();
        
        // Init title for all actions
        $this->view->pageTitle = pm_Locale::lmsg('pageTitle');

       $tabs = array(
            array(
                'title' => pm_Locale::lmsg('formTitle'),
                'link' => $this->_helper->url('form', 'index')
        ));
        if(!pm_Settings::get('dcApiLogin') || !pm_Settings::get('dcApiKey')) {
            $this->_status->addMessage('error', pm_Locale::lmsg('needAccess'));
        } else {
            $this->api = new Modules_Decanet_DcApiRest(pm_Settings::get('dcApiLogin'), pm_Settings::get('dcApiKey'));
            $tabs[] = array(
                'title' => pm_Locale::lmsg('MyDetails'),
                'action' => 'info',
            );
            $tabs[] = array(
                'title' => pm_Locale::lmsg('SecondaryDNS'),
                'action' => 'serverlist',
            );
        }
		$tabs[] = array(
			'title' => pm_Locale::lmsg('Backups'),
			'link' => $this->_helper->url('backupslist', 'backups'),
			'action' => 'backupdates'
		);
		
		// Init tabs for all actions
        $this->view->tabs = $tabs;
    }
    
    public function backupdatesAction()
    {
        $this->view->titlep = pm_Locale::lmsg('Listing Date For:').' '.$this->_getParam('backup_name');
        $this->view->list =  $this->_getListRandom();
    }
    
    public function listDataAction()
    {
        $list = $this->_getListRandom();
        // Json data from pm_View_List_Simple
        $this->_helper->json($list->fetchData());
    }
    
    private function _getListRandom()
    {
        $backup_name = $this->_getParam('backup_name');
        $type = $this->_getParam('type');
        $data = array();
        if($backup_name) {
            $result = pm_ApiCli::callSbin('backups.sh',  array('days', $backup_name, $type), pm_ApiCli::RESULT_FULL);
            $backups = trim($result['stdout']);
            $backups = explode("\n", $backups);
            if($backups)
                foreach ($backups as $b) {
                    $data[] = array(
                        'name' => $b,
                        'linkr' => '<a href="'.$this->_helper->url('restore', 'backup').'/backup_name/'.$backup_name.'/date/'.$b.'/type/'.$type.'">'.pm_Locale::lmsg('ProcessBackup').'</a>'
                    );
                }
        }

        $list = new pm_View_List_Simple($this->view, $this->_request);
        $list->setData($data);
        $list->setColumns(array(
            'name' => array(
                'title' => pm_Locale::lmsg('BackupDate'),
                'noEscape' => true,
            ),
            'linkr' => array(
                'title' => pm_Locale::lmsg('BackupLinkRestore'),
                'noEscape' => true,
            )
        ));
        
        $buttons[] = [
			'title' => pm_Locale::lmsg('BackToList'),
			'description' => pm_Locale::lmsg('addButtonDesc'),
//			'class' => 'sb-add-new-own-subscription',
			'controller' => 'backups',
			'action' => 'backupslist'
		];
        $list->setTools($buttons);
        $list->setDataUrl(array('action' => 'list-data'));
        return $list;
    }
    
    public function restoreAction()
	{
		$backup_name = $this->_getParam('backup_name');
        $date = $this->_getParam('date');
        $type = $this->_getParam('type');
        $result = pm_ApiCli::callSbin('backups.sh',  array('restore', $backup_name, $type, $date), pm_ApiCli::RESULT_FULL);
        
        $taskManager = new pm_LongTask_Manager();
        $restoreTask = new Modules_Decanet_Task_Restore();
        $runningbackups = $restoreTask->getParam('runningBackups', []);

        $runningbackups[] = array(
            'backup_name' => $backup_name,
            'backup_type' => $type,
            'backup_date' => $date
        );

        $restoreTask->setParams(['runningBackups' => $runningbackups]);
        $taskManager->start($restoreTask);

        for ($i = 1; $i < 5; $i++) { // wait for acquiring lock to keep UI consistent
            if (pm_Settings::get('restore_lock')) {
                break;
            }
            sleep(1);
        }

        $this->_status->addMessage('info', pm_Locale::lmsg('Restoring').' '.$backup_name. pm_Locale::lmsg('of').' '.$date.' '.pm_Locale::lmsg('in running'));
        $this->_redirect('backup/backupdates/backup_name/'.$backup_name.'/type/'.$type);
	}
}
