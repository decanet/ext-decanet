<?php
class Modules_Decanet_EventListener implements EventListener
{
    public function handleEvent($objectType, $objectId, $action, $oldValue, $newValue)
    {
        pm_Context::init('decanet');
        if(pm_Settings::get('dcApiLogin') && pm_Settings::get('dcApiKey')) {
            $api = new Modules_Decanet_DcApiRest(pm_Settings::get('dcApiLogin'), pm_Settings::get('dcApiKey'));
            $result = pm_ApiCli::callSbin('serverconf',  array('--key','FULLHOSTNAME'), pm_ApiCli::RESULT_FULL);
            $server = trim($result['stdout']);
            
             
           switch($action) {
                case 'phys_hosting_create':
                case 'domain_create':
                    $dns_r = pm_ApiCli::call('dns',  array('--info', $newValue['Domain Name']), pm_ApiCli::RESULT_FULL);
                    $stdout = $dns_r['stdout'];
                    if(preg_match('#'.$newValue['Domain Name'].'\.\s+NS\s+ns2\.decanet\.fr\.#', $stdout)){
                        $dns = $this->api->post("/server/$server/dns", array('domain_name' => $newValue['Domain Name']));
                        mail('alfred@decanet.fr', "$action - test plesk", 'OV: '.print_r($oldValue,true)."<br><br> NV:".print_r($oldValue,true));
                    }
                    break;
                case 'phys_hosting_update':
                case 'domain_update':
                    if($oldValue['Domain Name'] != $newValue['Domain Name']) {
                        $dns_r = pm_ApiCli::call('dns',  array('--info', $newValue['Domain Name']), pm_ApiCli::RESULT_FULL);
                        $stdout = $dns_r['stdout'];
                        if(preg_match('#'.$newValue['Domain Name'].'\.\s+NS\s+ns2\.decanet\.fr\.#', $stdout)){
                            $dns = $this->api->put("/server/$server/dns/{$oldValue['Domain Name']}", array('new_domain_name' => $newValue['Domain Name']));
                            mail('alfred@decanet.fr', "$action - test plesk", 'OV: '.print_r($oldValue,true)."<br><br> NV:".print_r($oldValue,true));
                        } 
                    }
                    break;
                case 'phys_hosting_delete':
                case 'domain_delete':
                    $dns = $this->api->delete("/server/$server/dns/{$newValue['Domain Name']}");
                    mail('alfred@decanet.fr', "$action - test plesk", 'OV: '.print_r($oldValue,true)."<br><br> NV:".print_r($oldValue,true));
                    break;
                case 'domain_alias_create':
                    $dns_r = pm_ApiCli::call('dns',  array('--info', $newValue['Domain Alias Name']), pm_ApiCli::RESULT_FULL);
                    $stdout = $dns_r['stdout'];
                    if(preg_match('#'.$newValue['Domain Alias Name'].'\.\s+NS\s+ns2\.decanet\.fr\.#', $stdout)){
                        $dns = $this->api->post("/server/$server/dns", array('domain_name' => $newValue['Domain Alias Name']));
                        mail('alfred@decanet.fr', "$action - test plesk", 'OV: '.print_r($oldValue,true)."<br><br> NV:".print_r($oldValue,true));
                    }
                    break;
                case 'domain_alias_update':
                    if($oldValue['Domain Alias Name'] != $newValue['Domain Alias Name']) {
                        $dns_r = pm_ApiCli::call('dns',  array('--info', $newValue['Domain Alias Name']), pm_ApiCli::RESULT_FULL);
                        $stdout = $dns_r['stdout'];
                        if(preg_match('#'.$newValue['Domain Alias Name'].'\.\s+NS\s+ns2\.decanet\.fr\.#', $stdout)){
                            $dns = $this->api->put("/server/$server/dns/{$oldValue['Domain Alias Name']}", array('new_domain_name' => $newValue['Domain Alias Name']));
                            mail('alfred@decanet.fr', "$action - test plesk", 'OV: '.print_r($oldValue,true)."<br><br> NV:".print_r($oldValue,true));
                        }
                    }
                    break;
                case 'domain_alias_delete':
                    $dns = $this->api->delete("/server/$server/dns/{$newValue['Domain Alias Name']}");
                    mail('alfred@decanet.fr', "$action - test plesk", 'OV: '.print_r($oldValue,true)."<br><br> NV:".print_r($oldValue,true));
                    break;  
            }
        }
    }
}
return new Modules_Decanet_EventListener();
