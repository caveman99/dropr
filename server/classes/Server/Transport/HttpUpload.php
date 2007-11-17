<?php
class pmq_Server_Transport_HttpUpload extends pmq_Server_Transport_Abstract 
{
    
    public function handle()
    {
        // check for the http header of the client
        if (!isset($_SERVER['X-pmq-client'])) {
            #throw new Exception("No client header set!");
        }

     
        $return = array();
        foreach ($_FILES as $k => $fStruct) {
            if ($this->getStorage()->getType() === pmq_Server_Storage_Abstract::TYPE_FILE) {
                
                // XXX: erst move_uploaded_file f�r uns (security)
                
                $return[$fStruct['name']]['inqueue'] = move_uploaded_file(
                    $fStruct['tmp_name'],
                    $this->getStorage()->getDestination() . DIRECTORY_SEPARATOR . $fStruct['name']
                );
            }
        }
        
        print(serialize($return));    
    
    }
    
}