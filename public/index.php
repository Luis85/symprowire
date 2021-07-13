<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage as SessionBridge;

use function ProcessWire\wire;

// Get Symfony Sessions to interface with ProcessWire session
$session = new SymfonySession(new SessionBridge());
$session->start();
// add ProcessWire Session Data to our Sessionbag
$session->replace(wire('session')->getAll());

// instantiate the Kernel and let Symfony do his thing
return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
