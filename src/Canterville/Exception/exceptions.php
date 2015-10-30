<?php
/**
 * File with all exceptions
 *
 * @package Canterville\Exception
 * @author Ladislav Vondráček
 */

namespace Canterville\Exception;

class Exception extends \Exception
{}

class InvalidArgumentException extends Exception
{}

class NotExistsException extends Exception
{}

class NotSetException extends Exception
{}

class RuntimeException extends Exception
{}
