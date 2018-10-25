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
//        $this->view->list =  $this->_getListRandom();
    }
    
    public function listDataAction()
    {
        $list = $this->_getListRandom();

        // Json data from pm_View_List_Simple
        $this->_helper->json($list->fetchData());
    }
    
    private function _getListRandom()
    {
		die(var_dump( pm_ApiCli::callSbin('backups.sh',  array('list','ftp'), pm_ApiCli::RESULT_FULL)));
        $servers = array();
        $data = array();
        $result = pm_ApiCli::callSbin('serverconf',  array('--key','FULLHOSTNAME'), pm_ApiCli::RESULT_FULL);
        $server = trim($result['stdout']);
        if(pm_Settings::get('dcApiLogin') && pm_Settings::get('dcApiKey')) {
            $dns = $this->api->get("/server/$server/dns");
            if($dns)
                foreach ($dns as $d) {
                    $data[] = array(
                        'id' => $d->id,
                        'domain' => $d->domaine,
                        'status' => '<img src="' . pm_Context::getBaseUrl() . 'images/state'.$d->etat.'.png" /> ',
                    );
                }
        }

        $list = new pm_View_List_Simple($this->view, $this->_request);
        $list->setData($data);
        $list->setColumns(array(
            pm_View_List_Simple::COLUMN_SELECTION,
            'id' => array(
                'title' => pm_Locale::lmsg('#ID'),
                'noEscape' => true,
            ),
            'domain' => array(
                'title' => pm_Locale::lmsg('Domain'),
                'noEscape' => true,
            ),
            'status' => array(
                'title' =>  pm_Locale::lmsg('Status'),
                'noEscape' => true,
            ),
        ));
        
        $buttons[] = [
			'title' => pm_Locale::lmsg('addButton'),
			'description' => pm_Locale::lmsg('addButtonDesc'),
			'class' => 'sb-add-new-own-subscription',
			'controller' => 'edit',
			'action' => 'editdomain'
		];
		$buttons[] = [
			'title' => pm_Locale::lmsg('removeButton'),
//			'description' => pm_Locale::lmsg('removeButtonDesc'),
			'class' => 'sb-remove-selected',
			'execGroupOperation' => $this->_helper->url('remove'),
		];
        $list->setTools($buttons);
        // Take into account listDataAction corresponds to the URL /list-data/
        $list->setDataUrl(array('action' => 'list-data'));
        return $list;
    }
}
