<?php
/**
 * File with all exceptions
 * @package Canterville
 * @author Ladislav Vondráček
 */

namespace Canterville;

class Exception extends \Exception{}

class InvalidArgumentException extends Exception{}

class NonExistsException extends Exception{}

class NotSetException extends Exception{}

class RuntimeException extends Exception{}
