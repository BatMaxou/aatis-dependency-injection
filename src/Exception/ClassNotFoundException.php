<?php

namespace Aatis\DependencyInjection\Exception;

use Aatis\DependencyInjection\Interface\Exception\ServiceExceptionInterface;

class ClassNotFoundException extends \Exception implements ServiceExceptionInterface
{
}
