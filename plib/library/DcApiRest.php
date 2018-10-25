<?php
class Modules_Decanet_DcApiRest
{
    private $LOGIN = false;
    private $PASS = false;
    private $ROOT = 'https://api2.decanet.fr';
    
    private $timeDrift = 0;

    public function __construct($_login = false, $_pass = false, $_root = false)
    {
        pm_Context::init('decanet');
        if ($_login) {
            $this->LOGIN = $_login;
        }
        if ($_pass) {
            $this->PASS = $_pass;
        }
        if ($_root) {
            $this->ROOT = $_root;
        }

        // Compute time drift
        $srvTime = json_decode(file_get_contents($this->ROOT . '/auth/time'));
        if ($srvTime !== false) {
            $this->timeDrift = time() - (int)$srvTime;
        }
    }

    public function call($method, $url, $body = null)
    {
        $url = $this->ROOT . $url;
        if ($body) {
            $bodystring = '';
            foreach ($body as $key => $value) {
                $bodystring .= $key.'='.urlencode($value).'&';
            }
            $bodystring = rtrim($bodystring, '&');
        }

        // Compute signature
        $time = time() - $this->timeDrift;
        $toSign = $this->LOGIN.'+'.$this->PASS.'+'.$method.'+'.$time;
        $signature = '$1$' . sha1($toSign);

        // Call
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'X-Consumer:' . $this->LOGIN,
            'X-Signature:' . $signature,
            'X-Timestamp:' . $time,
        ));

        if ($body) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $bodystring);
        }
        $result = curl_exec($curl);
        if ($result === false) {
            echo curl_error($curl);
            return null;
        }

        return json_decode($result);
    }

    public function get($url)
    {
        return $this->call("GET", $url);
    }

    public function put($url, $body)
    {
        return $this->call("PUT", $url, $body);
    }

    public function post($url, $body)
    {
        return $this->call("POST", $url, $body);
    }

    public function delete($url, $body = false)
    {
        return $this->call("DELETE", $url, $body);
    }
    
}
