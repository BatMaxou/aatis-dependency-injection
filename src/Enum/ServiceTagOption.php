<?php

namespace Aatis\DependencyInjection\Enum;

enum ServiceTagOption: string
{
    case SERVICE_TARGETED = 'service_target';
    case FROM_CLASS = 'from_class';
}
