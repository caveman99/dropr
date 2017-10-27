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

namespace cubos\dropr;

use cubos\dropr\Log\Log;
use cubos\dropr\Log\Errorlog;

/**
 * dropr main class
 * 
 *  - constants
 *  - logging
 * 
 * @author Soenke Ruempler <soenke@ruempler.eu>
 */
class dropr 
{
    /**
     * @var Log
     */
    private static $logger;

    /**
     * Mapping for error-levels
     *
     * @var array
     */
    private static $errorLevels = array(
        LOG_ERR,
        LOG_CRIT,
        LOG_WARNING,
        LOG_INFO,
        LOG_DEBUG,
    );
    
    /**
     * the current log-level
     *
     * @var int
     */
    private static $logLevel = LOG_DEBUG;
    
    /**
     * set the log level
     *
     * @param mixed $logLevel
     */
    public static function setLogLevel($logLevel)
    {
    	self::$logLevel = $logLevel;
    }
    
    /**
     * logging for the dropr services. can be
     *  - syslog for daemons
     *  - error_log for http processes
     * 
     * currently only syslog is implemented
     *
     * @param string    $message
     * @param int       $level
     */
    public static function log($message, $level = LOG_INFO)
    {
        if ($level <= self::$logLevel) {
            self::getLogger()->log($message, $level);
        }
    }

    /**
     * set the logger
     * 
     * @var $logger     Log
     */
    public static function setLogger(Log $logger)
    {
        self::$logger = $logger;
    }
    
    /**
     * Get the Logger instance
     * 
     * @return Log
     */
    public static function getLogger()
    {
        if (!self::$logger) {
            // get the default error_log logger
            self::$logger = new Errorlog();
        }
        
        return self::$logger;
    }
}
