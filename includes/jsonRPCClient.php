<?php
/*
  COPYRIGHT

  Copyright 2007 Sergio Vaccaro <sergio@inservibile.org>

  This file is part of JSON-RPC PHP.

  JSON-RPC PHP is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  JSON-RPC PHP is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with JSON-RPC PHP; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * The object of this class are generic jsonRPC 1.0 clients
 * http://json-rpc.org/wiki/specification
 *
 * @author sergio <jsonrpcphp@inservibile.org>
 */
class jsonRPCClient {

    /**
     * Debug state
     *
     * @var boolean
     */
    private $debug;
    /**
     * The server URL
     *
     * @var string
     */
    private $url;
    /**
     * The server port
     *
     * @var int
     */
    private $port = '80';
    /**
     * The request id
     *
     * @var integer
     */
    private $id;
    /**
     * If true, notifications are performed instead of requests
     *
     * @var boolean
     */
    private $notification = false;
    /**
     * If true, verify certificate
     *
     * @var boolean
     */
    private $verifypeer = false;
    /**
     * array of hearders
     *
     * @var string
     */
    private $_header;

    /**
     * Takes the connection parameters
     *
     * @param string $url
     * @param boolean $debug
     */
    public function __construct($url, $debug = false) {
        // server URL
        $this->url = $url;
        // debug state
        empty($debug) ? $this->debug = false : $this->debug = true;
        // message id by unix-time-stamp
        $this->id = time();
        // set the basic header type
        $this->_header['type'] = 'Content-type: application/json';
    }

    /**
     * add header for basic authentication
     *
     * @param string $user
     * @param string $pass
     */
    public function setBasicAuth($user, $pass) {
        if (is_string($user) AND is_string($pass)) {
            $header = sprintf('Authorization: Basic %s', base64_encode($user . ':' . $pass));
            $this->_header['basicAuth'] = $header;
        } else
            unset($this->_header['basicAuth']);
    }

    /**
     * add a cookie to the header
     *
     * @param string $value or null for empty cookie
     */
    public function setCookie($value) {
        if (is_string($value) OR is_null($value))
            $this->_header['cookie'] = sprintf('Cookie: "%s"', $value);
        else
            unset($this->_header['cookie']);
    }

    /**
     * set the port to connection
     *
     * @uses if the port is not 80, the class use cURL
     * @param int $port
     */
    public function setPort($port) {
        is_numeric($port) ? $this->port = $port : $this->port = '80';
    }


    public function setVerifypeer($value = null) {
        empty($value) ? $this->verifypeer = false : $this->verifypeer = true;
    }

    public function setVerifyCAPath($path) {
        if(is_file($path)){
            $this->verifyCAPath = $path;
            $this->setVerifypeer(true);
        } else {
            if(is_string($path))
                throw new Exception('Certificate on path "'.$path.'" not exists');
        }
    }



    /**
     * Sets the notification state of the object. In this state, notifications
     * are performed, instead of requests.
     *
     * @param boolean $notification
     */
    public function setRPCNotification($notification) {
        empty($notification) ? $this->notification = false :
                $this->notification = true;
    }

    /**
     * RPC call by cURL
     * @param string $request JSON format
     * @return string JSON format
     */
    protected function callByCURL($request){
        // check
        if(is_string($request)){
            // start the cUrl session
            $ch = curl_init();
            // adress an aort
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_PORT, $this->port);
            // verify certificate
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifypeer);
            // path to verify certificate
            if($this->verifypeer AND !is_null($this->verifyCAPath))
                curl_setopt($ch, CURLOPT_CAINFO, $this->verifyCAPath);
            // POST
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
            // no respons header
            curl_setopt($ch, CURLOPT_HEADER, false);
            // to send headers
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_values($this->_header));
            // execute and get result as string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // call an check the response
            if (!$response = curl_exec($ch))
                throw new Exception(curl_error($ch));
            // closs the cUrl session
            curl_close($ch);

            return $response;
        }
    }

    /**
     * RPC call by fopen
     * @param string $request JSON format
     * @return string JSON format
     */
    protected function callByFopen($request){
        // check
        if(is_string($request)){
            // performs the HTTP POST
            $opts = array(
                'http' => array(
                    'method'  => 'POST',
                    'header'  => join("\r\n", array_values($this->_header)),
                    'content' => $request));
            // send request for the response
            if($fp = fopen($this->url, 'r', false, stream_context_create($opts))){
                while ($row = fgets($fp))
                    $response .= trim($row) . "\n";
            } else
                throw new Exception('No response from the url : '.$this->url);
            return $response;
        }
    }

    /**
     * Performs a jsonRCP request and gets the results as an array
     *
     * @param string $method
     * @param array $params
     * @return array
     */
    public function __call($method, $params) {

        // check
        if (!is_scalar($method))
            throw new Exception('Method name has no scalar value');
        if (is_array($params)) 
            // no keys
            $params = array_values($params);
        else
            throw new Exception('Params must be given as array');

        // sets notification or request task
        $this->notification ? $currentId = NULL : $currentId = $this->id;

        // prepares the request
        $_request = array('method' => $method,
                          'params' => $params,
                          'id' => $currentId );
        $request  = json_encode($_request);
        
        // if cUrl installed or not use the port 80, call by cURL
        if ((function_exists('curl_init') OR $this->port != '80')){
            if(function_exists('curl_init'))
                $response = $this->callByCURL($request);
            else 
                throw new Exception('extension cURL is not installed!');
        // if curl is NOT installed and the port IS 80
        } else
            $response = $this->callByFopen($request);
        
        // debug output
        $this->debug($request, $response);
        
        // final checks
        if (!$this->notification) {
            $_response = json_decode($response, true);
            if ($_response['id'] != $currentId)
                throw new Exception('Incorrect response id (request id: ' .
                        $currentId . ', response id: ' . $_response['id'] . ')');
            if (!is_null($_response['error']))
                throw new Exception('Request error: ' . $_response['error']);

            $return = $_response['result'];
        } else
            $return = true;
        return $return;
    }

    private function debug($request, $response){
        if ($this->debug) {
            $debug.='>>Request >>>'."\n".$request."\n".'>> End Of request >>>'.
            "\n" .'<<< Server response <<'."\n".$response."\n".
            '<<< End of server response <<'."\n\n";
            echo nl2br($debug);
        }
    }

}
?>
