<?php
/**
 * dropr
 *
 * Copyright (c) 2007 - 2008 by the dropr project https://www.dropr.org/
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of dropr nor the names of its
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    dropr
 * @author     Soenke Ruempler <soenke@jimdo.com>
 * @author     Boris Erdmann <boris@jimdo.com>
 * @copyright  2007-2008 Soenke Ruempler, Boris Erdmann
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 */

namespace cubos\dropr\Client\Storage;

use SplFileInfo;
use DirectoryIterator;
use cubos\dropr\Client\DroprClientException;
use cubos\dropr\Client\ClientMessage;
use cubos\dropr\Client\Peer\AbstractClientPeer;

/**
 * Filesystem Storage Driver
 */
class ClientFilesystemStorage extends AbstractClientStorage
{

    const SPOOLDIR_TYPE_IN = 'in';

    const SPOOLDIR_TYPE_SPOOL = 'proc';

    const SPOOLDIR_TYPE_SENT = 'sent';

    private $path;

	protected function __construct($path)
	{
        parent::__construct($path);

	    if (!is_string($path)) {
	        throw new DroprClientException("No valid path given");
	    }
	    
	    if (!is_dir($path)) {
	        if (!@mkdir($path, 0755, true)) {
	            throw new DroprClientException("Could not create Queue Directory $path");
	        }
	    }
	    
	    if (!is_writeable($path)) {
	        throw new DroprClientException("$path is not writeable!");
	    }
	    
	    $this->path = realpath($path);
	}

    public function saveMessage(ClientMessage $message)
    {
        $inPath = $this->getSpoolPath(self::SPOOLDIR_TYPE_IN) . DIRECTORY_SEPARATOR;
        $spoolPath = $this->getSpoolPath(self::SPOOLDIR_TYPE_SPOOL) . DIRECTORY_SEPARATOR;

        $priority = $message->getPriority();
        $peer = $this->encodeForFs($message->getPeer()->getKey());
        $channel = $this->encodeForFs($message->getChannel());

        $badName = true;
        while ($badName) {

            $fName = join('_', array(
                $priority,
                $this->getTimeStamp(),
                $peer,
                $channel
            ));

            $fh = @fopen($inPath . $fName, 'x');
            $badName = ($fh === false);
        }

        fwrite($fh, $message->getMessage());
        fclose($fh);

        if (!rename($inPath . $fName, $spoolPath . $fName)) {
            throw new DroprClientException("Could not move spoolfile " . $fName . "!");
        }
        return $fName;
    }

    public function getQueuedMessages($limit = null, &$peerKeyBlackList = null)
    {
        // expire blacklisted peers
        $now = time();
        foreach ($peerKeyBlackList as $peerKey => $timeout) {
            if ($now > $timeout) {
                unset($peerKeyBlackList[$peerKey]);
            }
        }

        $spoolDir = $this->getSpoolPath(self::SPOOLDIR_TYPE_SPOOL) . DIRECTORY_SEPARATOR;
        $fNames = scandir($spoolDir);

        // unset the "." and the ".."
        unset($fNames[0]);
        unset($fNames[1]);

        $c = 1;
        $messages = array();
        foreach($fNames as $k => $fName) {

            if ($limit && ($c > $limit)) {
                break;
            }

            list($priority, $timeStamp, $encodedPeerKey, $encodedChannel) = explode('_', $fName, 4);
            $decodedPeerKey = $this->decodeFromFs($encodedPeerKey);
            $decodedChannel = $this->decodeFromFs($encodedChannel);

            $message = new ClientMessage(
                NULL,
                new SplFileInfo($spoolDir . $fName),
                AbstractClientPeer::getInstance($decodedPeerKey),
                $decodedChannel,
                $priority
            );
            $message->restoreId($fName);

            if (!isset($peerKeyBlackList[$decodedPeerKey])) {
                $messages[$decodedPeerKey][] = $message;
                $c++;
            }
        }

        return $messages;
    }

    public function getMessage($messageId, AbstractClientPeer $peer)
    {
        return $messageId;
    }

    private function encodeForFs($val)
    {
        return base64_encode($val);
    }

    private function decodeFromFs($val)
    {
        return base64_decode($val);
    }

    private function getSpoolPath($type = self::SPOOLDIR_TYPE_IN)
    {
        $path = $this->path . DIRECTORY_SEPARATOR . $type;
        
        if (!is_dir($path)) {
            if (!mkdir($path, 0775)) {
                throw new DroprClientException("Could not create directory $path!");
            }
        }

        return $path;
    }
    
    private function getTimeStamp()
    {
        $tName = (string)microtime();
        $spPos = strpos($tName, ' ');
        return substr($tName, $spPos+1).'-'.substr($tName, 2, $spPos-2);
    }
    
    public function getType()
    {
        return self::TYPE_FILE;
    }
    
    public function checkSentMessages(array &$messages, array &$result)
    {
        $spoolPath = $this->getSpoolPath(self::SPOOLDIR_TYPE_SPOOL) . DIRECTORY_SEPARATOR;
        $sentPath = $this->getSpoolPath(self::SPOOLDIR_TYPE_SENT) . DIRECTORY_SEPARATOR;

        foreach ($messages as $k => $message) {

            $msgId = $message->getId();

            if (isset($result[$msgId]['inqueue']) && ($result[$msgId]['inqueue'] === true)) {
                if (!rename($spoolPath . $msgId, $sentPath . $msgId)) {
                    throw new DroprClientException("Could not move spoolfile!");
                }                
            }
            
            unset($message);
        }
        
    }

    public function countQueuedMessages()
    {
        $spoolPath = $this->getSpoolPath(self::SPOOLDIR_TYPE_SPOOL) . DIRECTORY_SEPARATOR;
        return count(scandir($spoolPath))-2;
    }

    public function countSentMessages()
    {
        $spoolPath = $this->getSpoolPath(self::SPOOLDIR_TYPE_SENT) . DIRECTORY_SEPARATOR;
        return count(scandir($spoolPath))-2;
    }

    public function wipeSentMessages($olderThanMinutes)
    {
        $spoolPath = $this->getSpoolPath(self::SPOOLDIR_TYPE_SENT) . DIRECTORY_SEPARATOR;
        $dirIter = new DirectoryIterator($spoolPath);
        $time = time()-($olderThanMinutes*60);
        $c = 0;
        foreach ($dirIter as $fInfo) {
            if (($fInfo->getATime() < $time) && $fInfo->isFile()) {

                unlink($spoolPath . $fInfo->getFilename());
                $c++;
            }
        }
        return $c;
    }
}
