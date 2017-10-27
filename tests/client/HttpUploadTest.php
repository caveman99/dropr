<?php

use PHPUnit\Framework\TestCase;
/**
 * you have to run a server at http://localhost/droprserver/
 * 
 * @author Soenke Ruempler
 *
 */
class HttpUploadTest extends TestCase
{

    /**
     * @var dropr_Client
     */
    private $queue;
    
    /**
     * @var dropr_Client_Storage_Abstract
     */
    private $storage;

    public function setUp()
    {
        require_once dirname(__FILE__) . '/../../classes/dropr.php';

        $this->storage = dropr_Client_Storage_Abstract::factory('filesystem', '/tmp/spool/dropr/client');
        $this->queue = new dropr_Client($this->storage);
    }

    /**
     * @test
     */
    public function put()
    {
        $peer = dropr_Client_Peer_Abstract::getInstance('HttpUpload', 'http://localhost/droprserver/');

        $i=0;
        // $m = $this->createMessage(1000);
        
        $m = "ich bin eine test message von " . date("H:m:i");
        
        while ($i < 1) {

            $msg = $this->queue->createMessage($m, $peer);
            $msg->queue();
            $i++;
        }

    }


    function createMessage($len,
        $chars = '0123456789 ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz')
    {
        $charsSize = strlen($chars)-1;
        $string = '';
        for ($i = 0; $i < $len; $i++)
        {
            $pos = rand(0, $charsSize);
            $string .= $chars{$pos};
        }
        return $string;
    }


}
