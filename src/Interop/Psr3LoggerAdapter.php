<?php
namespace ScratchByPHP\Interop;
final class Psr3LoggerAdapter { public function __construct(private object $logger){} public function log(string $level,string $message,array $context=[]):void{if(method_exists($this->logger,'log'))$this->logger->log($level,$message,$context);} }
