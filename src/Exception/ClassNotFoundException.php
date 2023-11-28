<?php

namespace Aatis\DependencyInjection\Exception;

use Aatis\DependencyInjection\Interface\ServiceExceptionInterface;

class ClassNotFoundException extends \Exception implements ServiceExceptionInterface
{
}
