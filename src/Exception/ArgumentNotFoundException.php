<?php

namespace Aatis\DependencyInjection\Exception;

use Aatis\DependencyInjection\Interface\Exception\ServiceExceptionInterface;

class ArgumentNotFoundException extends \Exception implements ServiceExceptionInterface
{
}
