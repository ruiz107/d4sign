<?php

namespace D4sign;

abstract class ClientBase
{
    protected $url = "https://secure.d4sign.com.br/api/";
    protected $accessToken = null;
    //protected $timeout = 240;
    protected $timeout = 5;
    protected $version = "v1";

    public function setUrl($url)
    {
        $this->url = $url;
    }
    
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }
    
    public function getAccessToken()
    {
    	return $this->accessToken;
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    protected function doRequest($url, $method, $data, $contentType = null)
    {
        // eceção pra debugar o upload
        /*
        if($method == 'POST') {
            echo 'upload?';
            print_r($data);
            exit;
        }
        */
        //return $this->client->request("/documents/$uuid_safe/upload", "POST", $data, 200);
        $header = array("Accept: application/json");
        
        array_push($header, "tokenAPI: $this->accessToken");
        
    	$url = $this->url . $this->version . $url . "?tokenAPI=" . $this->accessToken;

        $c = curl_init();

        switch($method)
        {
            case "GET":
                curl_setopt($c, CURLOPT_HTTPGET, true);
                if(count($data))
                {
                    $url .= "&" . http_build_query($data);
                }
                break;

            case "POST":
                curl_setopt($c, CURLOPT_POST, true);
                if(count($data))
                {
                    curl_setopt($c, CURLOPT_POSTFIELDS, $data);
                }
                break;

            case "DELETE":
                curl_setopt($c, CURLOPT_CUSTOMREQUEST, $method);
                if ($data)
                {
                    curl_setopt($c, CURLOPT_POST, true);
                    curl_setopt($c, CURLOPT_POSTFIELDS, $data);
                }
                break;
        }
        
                
        curl_setopt($c, CURLOPT_ENCODING, true);
        curl_setopt($c, CURLOPT_MAXREDIRS, true);

        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($c, CURLOPT_HTTPHEADER, $header);
        //curl_setopt($c, CURLOPT_HEADER, true);
        
        curl_setopt($c, CURLOPT_URL, $url);
        //curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        
        curl_setopt($c, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); 
        //curl_setopt($c, CURLOPT_HTTP_VERSION, '1.1');
        
        
        //curl_setopt($c, CURLOPT_VERBOSE, true);

        //$streamVerboseHandle = fopen('php://temp', 'w+');
        //curl_setopt($c, CURLOPT_STDERR, $streamVerboseHandle);


        $result = curl_exec($c);

        /*
        if($method == 'POST') {
            echo 'upload result?';
            print_r($result);
            exit;
        }
        */

        //echo 'oi' . $result;
        //echo 'oi';
        //exit;
        /*
        if ($result === FALSE) {
            printf("cUrl error (#%d): %s<br>\n",
                curl_errno($c),
                htmlspecialchars(curl_error($c)))
                ;
        }

        rewind($streamVerboseHandle);
        $verboseLog = stream_get_contents($streamVerboseHandle);

        echo "cUrl verbose information:\n", 
        "<pre>", htmlspecialchars($verboseLog), "</pre>\n";
        */

        curl_close($c);

        return $result;
    	
    }

    public function request($url, $method, $data, $expectedHttpCode, $contentType = '')
    {
        $response = $this->doRequest($url, $method, $data, $contentType);
        
        return $this->parseResponse($url, $response, $expectedHttpCode);
    }

    public function parseResponse($url, $response, $expectedHttpCode)
    {
        $header = false;
        $content = array();
        $status = 200;
		
        foreach(explode("\r\n", $response) as $line)
        {
            if (strpos($line, "HTTP/1.1") === 0)
            {
                $lineParts = explode(" ", $line);
                $status = intval($lineParts[1]);
                $header = true;
            }
            else if ($line == "")
            {
                $header = false;
            }
            else if ($header)
            {
                $line = explode(": ", $line);
                if($line[0] == "Status")
                {
                    $status = intval(substr($line[1], 0, 3));
                }
            }
            else
            {
                $content[] = $line;
            }
        }

        if($status !== $expectedHttpCode)
        {
		throw new D4signException($content[0], D4signException::INVALID_HTTP_CODE);
        }

        $object = json_decode(implode("\n", $content));

        return $object;
    }
}
