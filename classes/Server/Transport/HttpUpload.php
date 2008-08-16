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

class dropr_Server_Transport_HttpUpload extends dropr_Server_Transport_Abstract 
{
    
    public function handle()
    {
        // check for the http header of the client
        if (!isset($_POST['client']) || !is_string($_POST['client'])) {
            throw new Exception("No client header set!");
        }
        
        $client = $_POST['client'];
        
        
        // Try to get the method we are called
        
        // xxx check the check
        if (!isset($_POST['metaData']) || !is_string($_POST['metaData']) || (!$metadata = unserialize($_POST['metaData']))) {
        	throw new Exception("No client metadata set - do you have magic_quotes enabled?");
        }
        
        #print_r($_FILES);exit;
        
        $return = array();
        foreach ($metadata as $k => $messageData) {
            
            // xxx check the existence of the indexes
            $messageId  = $messageData['messageId'];
            $channel    = $messageData['channel'];
            $priority   = $messageData['priority'];
            $messageRef = $messageData['message'];

            try {

                if (!isset($_FILES[$messageRef])) {
                    throw new dropr_Server_Exception("message not in fileupload!");
                }
                
                if (!is_uploaded_file($_FILES[$messageRef]['tmp_name'])) {
                    // could not move the uploaded file - whyever
                    $return[$messageId]['inqueue'] = false;
                    continue;
                }
                
                $file = new SplFileInfo($_FILES[$messageRef]['tmp_name']);
                $message = new dropr_Server_Message($client, $messageId, $file, $channel, $priority);
                
                $this->getStorage()->put($message);
                
                // ok, it's in the queue, lets notify the sender
                $return[$messageId]['inqueue'] = true;  
            
            } catch (Exception $e) {
                // something bad happened
                $return[$messageId]['inqueue'] = false;  
            }
        }
            
        #echo "Time after curl: ";
        #echo (time() - $time);
        #echo "\n";
        #exit;
        
        // write the result back to the sender
        echo serialize($return);
        
    
    }
    
}
