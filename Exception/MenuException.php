<?php

namespace Awaresoft\MenuBundle\Exception;

/**
 * Class MenuException
 *
 * @author Bartosz Malec <b.malec@awaresoft.pl>
 */
class MenuException extends \Exception
{
    public function __construct($message, $code = 500, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
