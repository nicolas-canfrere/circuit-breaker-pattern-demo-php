<?php

namespace App\Controller\Service2;

enum ServiceStateEnum: string
{
    case RUNNING = 'running';
    case ON_ERROR = 'on_error';
}
