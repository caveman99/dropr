<?php
class pmq_Client_Message
{
    /**
	 * @var pmq_Client
	 */
    private $queue;
    private $payload;    
    private $peer;
    private $priority;
    private $sync;

    private $state = NULL;

    public function __construct($queue = NULL, &$message = NULL, $peer = NULL, $priority = NULL, $sync = NULL) 
    {
        $this->queue = $queue;
        $this->payload = &$message;
        $this->peer = $peer;
        $this->queue = $priority;
        $this->sync = $sync;
    }

    public function getPeer()
    {
        return $this->peer;
    }

    public function &getMessage()
    {
        return $this->message;
    }
    public function queue() {
         $this->queue->putMessage($this);
    }
}
