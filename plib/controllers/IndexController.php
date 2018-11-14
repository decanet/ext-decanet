<?php

class IndexController extends pm_Controller_Action
{
    var $api = null;
    public function init()
    {
        parent::init();
        
        $tabs = array(
            array(
                'title' => pm_Locale::lmsg('formTitle'),
                'action' => 'form',
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
			'action' => 'backupslist'
		);
        // Init title for all actions
        $this->view->pageTitle = pm_Locale::lmsg('pageTitle');

        // Init tabs for all actions
        $this->view->tabs = $tabs;
    }

    public function indexAction()
    {
        // Default action will be formAction
        $this->_forward('form');
    }

    public function formAction()
    {
        // Display simple text in view
        $this->view->test = pm_Locale::lmsg('info');
        
        // Init form here
        $form = new pm_Form_Simple();
        $form->addElement('text', 'dcApiLogin', array(
            'label' => pm_Locale::lmsg('dcApiLogin'),
            'value' => pm_Settings::get('dcApiLogin'),
            'required' => true,
            'validators' => array(
                array('NotEmpty', true),
            ),
        ));
        
        $form->addElement('text', 'dcApiKey', array(
            'label' => pm_Locale::lmsg('dcApiKey'),
            'value' => pm_Settings::get('dcApiKey'),
            'required' => true,
            'validators' => array(
                array('NotEmpty', true),
            ),
        ));
        

        $form->addControlButtons(array(
            'cancelLink' => pm_Context::getModulesListUrl(),
        ));

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            // Form proccessing here
            pm_Settings::set('dcApiLogin', $form->getValue('dcApiLogin'));
            pm_Settings::set('dcApiKey', $form->getValue('dcApiKey'));

            $this->_status->addMessage('info', pm_Locale::lmsg('dataSuccessRegister'));
            $this->_helper->json(array('redirect' => pm_Context::getBaseUrl()));
        }
       
        $this->view->form = $form;
    }
    
    public function infoAction()
    {
        if(pm_Settings::get('dcApiLogin') && pm_Settings::get('dcApiKey')) {
            $this->view->thirdparty = $this->api->get('/me');
        }
    }
    
    public function serverlistAction()
    {
        $this->view->list =  $this->_getListRandom();;
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
    
    public function removeAction()
	{
		$messages = [];
		$reload = false;
		foreach((array)$this->_getParam('listCheckbox') as $domain) {
			$this->api->delete("/server/dns/$domain");
			$messages[] = ['status' => 'info', 'content' => pm_Locale::lmsg('Domain').' '.$domain.' '.pm_Locale::lmsg('deleted')];
		}
        $this->_helper->json([
            'status' => 'success',
            'statusMessages' => $messages,
            'redirect' => $this->_helper->url('serverlist', 'index')
        ]);	
	}
}
