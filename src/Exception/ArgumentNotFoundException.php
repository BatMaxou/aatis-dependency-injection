<?php

namespace Aatis\DependencyInjection\Exception;

use Aatis\DependencyInjection\Interface\ServiceExceptionInterface;

class ArgumentNotFoundException extends \Exception implements ServiceExceptionInterface
{
}
