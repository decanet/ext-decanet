<?php

class BackupsController extends pm_Controller_Action
{
    var $api = null;
    public function init()
    {
        parent::init();
        
        // Init title for all actions
        $this->view->pageTitle = pm_Locale::lmsg('pageTitle');

        // Init tabs for all actions
        $this->view->tabs = array(
            array(
                'title' => pm_Locale::lmsg('formTitle'),
                'link' => $this->_helper->url('form', 'index')
            ),
            array(
                'title' => pm_Locale::lmsg('MyDetails'),
                'link' => $this->_helper->url('info', 'index')
            ),
            array(
                'title' => pm_Locale::lmsg('SecondaryDNS'),
                'link' => $this->_helper->url('serverlist', 'index')
            ),
            array(
                'title' => pm_Locale::lmsg('Backups'),
                'active' => true,
                'action' => 'backupslist',
            ),
        );
    }
    
    public function backupslistAction()
    {
        $this->view->list = $this->_getListRandom();
    }
    
    public function listDataAction()
    {
        $list = $this->_getListRandom();
        // Json data from pm_View_List_Simple
        $this->_helper->json($list->fetchData());
    }
    
    private function _getListRandom()
    {
        $servers = array();
        $data = array();
        $result = pm_ApiCli::callSbin('backups.sh',  array('list','ftp'), pm_ApiCli::RESULT_FULL);
        $backups = trim($result['stdout']);
        $backups = explode("\n", $backups);
        if($backups)
            foreach ($backups as $b) {
                $data[] = array(
                    'name' => $b,
                    'type' => 'FTP',
                    'linkr' => '<a href="'.$this->_helper->url('backupdates', 'backup').'/backup_name/'.$b.'/type/ftp">'.pm_Locale::lmsg('ShowDates').'</a>',
                );
            }
        $result = pm_ApiCli::callSbin('backups.sh',  array('list','sql'), pm_ApiCli::RESULT_FULL);
        $backups = trim($result['stdout']);
        $backups = explode("\n", $backups);
        if($backups)
            foreach ($backups as $b) {
                $data[] = array(
                    'name' => $b,
                    'type' => 'SQL',
                    'linkr' => '<a href="'.$this->_helper->url('backupdates', 'backup').'/backup_name/'.$b.'/type/sql">'.pm_Locale::lmsg('ShowDates').'</a>',
                    
                );
            }


        $list = new pm_View_List_Simple($this->view, $this->_request);
        $list->setData($data);
        $list->setColumns(array(
            'name' => array(
                'title' => pm_Locale::lmsg('BackupName'),
                'noEscape' => true,
                'searchable' => true, 
            ),
            'type' => array(
                'title' => pm_Locale::lmsg('BackupType'),
                'noEscape' => true,
                'searchable' => true,
            ),
            'linkr' => array(
                'title' => pm_Locale::lmsg('BackupShowDatesLinks'),
                'noEscape' => true,
            ),
            
        ));
        
        // Take into account listDataAction corresponds to the URL /list-data/
        $list->setDataUrl(array('action' => 'list-data'));
        return $list;
    }
}

