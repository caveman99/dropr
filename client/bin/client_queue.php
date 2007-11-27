#!/usr/bin/php
<?php
require realpath(dirname(__FILE__) . '/..') . '/classes/autoload.php';
/*
 * Config
 */
if (!isset($argv[2])) {
    echo "usage: $argv[0] <storage-type> <storage-dsn>\n";
    exit;
}
$storage    = pmq_Client_Storage_Abstract::factory($argv[1], $argv[2]);
$qInstance  = new pmq_Client($storage);
$ipcChannel = $qInstance->getIpcChannel();


$continue = true;
function handleUser1($sig)
{
    global $continue;
    $continue = false;
}
pcntl_signal(SIGUSR1, 'handleUser1');

function handleAlarm($sig)
{
    return;
}
pcntl_signal(SIGALRM, 'handleAlarm');

declare(ticks = 1);
while ($continue == true) {
    unset($queuedMessages, $peerMessages);

    if ($queuedMessages = $storage->getQueuedMessages(1000)) {
        foreach ($queuedMessages as $peerKey => $peerMessages) {
    
            $peer = pmq_Client_Peer_Abstract::getInstance($peerKey);
            $result = $peer->send($peerMessages, $storage);
            $storage->checkSentMessages($peerMessages, $result);
        }
    }
    else {
        pcntl_alarm(4);
        @msg_receive($ipcChannel, 1, $msgType, 512, $msg, true, 0, $msgError);
        pcntl_alarm(0);
    }
}
# msg_remove_queue($qHandle);
