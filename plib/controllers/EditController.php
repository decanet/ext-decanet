<?php

class EditController extends pm_Controller_Action
{
    var $api = null;
    public function init()
    {
		parent::init();
        
        if(!pm_Settings::get('dcApiLogin') || !pm_Settings::get('dcApiKey')) {
            $this->_status->addMessage('error', pm_Locale::lmsg('needAccess'));
        } else {
            $this->api = new Modules_Decanet_DcApiRest(pm_Settings::get('dcApiLogin'), pm_Settings::get('dcApiKey'));
        }
        
		$domain = $this->_getParam('domain');
		$this->_accessLevel = 'admin';
		$this->view->pageTitle = pm_Locale::lmsg('pageTitle');
        $tabs = [
            [
                'title' => pm_Locale::lmsg('settingsTitle'),
				'active' => ($this->getParam('action')=='form'),
				'link' => $this->_helper->url('editdomain').'/id/'.(int)$domain
            ]
		];
		
        $this->view->tabs = $tabs;
    }
	
	public function editdomainAction()
    {
        $this->view->pageTitle = pm_Locale::lmsg('pageTitle');
		// Init form here
        $form = new pm_Form_Simple();
        $form->addElement('text', 'dnsdomain', array(
            'label' => pm_Locale::lmsg('NewDomain'),
            'required' => true,
            'validators' => array(
                array('NotEmpty', true),
            ),
        ));
        
        $form->addControlButtons([
            'cancelLink' => $this->_helper->url('serverlist', 'index'),
        ]);
         
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            if(pm_Settings::get('dcApiLogin') && pm_Settings::get('dcApiKey')) {
                
                $result = pm_ApiCli::callSbin('serverconf',  array('--key','FULLHOSTNAME'), pm_ApiCli::RESULT_FULL);
                $server = trim($result['stdout']);
                $dns = $this->api->post("/server/$server/dns", array('domain_name' => $form->getValue('dnsdomain')));
                if(isset($dns->id)) {
                    $this->_status->addMessage('info', pm_Locale::lmsg('Domain add succefuly'));
                    $this->_helper->json(['redirect' => $this->_helper->url('serverlist', 'index')]);
                } else {
                    $this->_status->addMessage('error', pm_Locale::lmsg('Domain not add succefuly').' Error: '.$dns->message);
                }
            }
        }
        $this->view->form = $form;        
    }
}