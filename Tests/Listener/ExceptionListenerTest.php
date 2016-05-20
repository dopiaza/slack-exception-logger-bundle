<?php

namespace Dopiaza\Slack\ExceptionLoggerBundle\Tests\Listener;

use Dopiaza\Slack\ExceptionLoggerBundle\Listener\ExceptionListener;
use Mockery as m;

class ExceptionListenerTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_skips_unconfigured_environments()
    {
        $mockNotfier = m::mock('Dopiaza\Slack\ExceptionLoggerBundle\Notifier\Notifier');
        $mockEvent = m::mock('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent');
        $mockException = m::mock('Exception');
        $environment = 'dev';

        $mockEvent->shouldReceive('getException')->once()->andReturn($mockException);
        $mockNotfier->shouldNotReceive('notify');

        $sut = new ExceptionListener($mockNotfier, $environment);
        $sut->setEnvironmentConfigurations(['prod' => []]);
        $sut->onKernelException($mockEvent);
    }

    public function test_it_skips_disabled_environments()
    {
        $mockNotfier = m::mock('Dopiaza\Slack\ExceptionLoggerBundle\Notifier\Notifier');
        $mockEvent = m::mock('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent');
        $mockException = m::mock('Exception');
        $environment = 'dev';

        $mockEvent->shouldReceive('getException')->once()->andReturn($mockException);
        $mockNotfier->shouldNotReceive('notify');

        $sut = new ExceptionListener($mockNotfier, $environment);
        $sut->setEnvironmentConfigurations(['dev' => ['enabled' => false]]);
        $sut->onKernelException($mockEvent);
    }

    public function test_it_skips_disabled_exceptions()
    {
        $mockNotfier = m::mock('Dopiaza\Slack\ExceptionLoggerBundle\Notifier\Notifier');
        $mockEvent = m::mock('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent');
        $mockException = m::mock('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $environment = 'dev';

        $mockEvent->shouldReceive('getException')->once()->andReturn($mockException);
        $mockNotfier->shouldNotReceive('notify');

        $sut = new ExceptionListener($mockNotfier, $environment);
        $sut->setEnvironmentConfigurations(['dev' => ['exclude_exception' => ['Symfony\Component\HttpKernel\Exception\NotFoundHttpException']]]);
        $sut->onKernelException($mockEvent);
    }

    public function test_it_notifies_an_exception()
    {
        $mockNotfier = m::mock('Dopiaza\Slack\ExceptionLoggerBundle\Notifier\Notifier');
        $mockEvent = m::mock('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent');
        $mockException = m::mock('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $environment = 'dev';

        $mockEvent->shouldReceive('getException')->once()->andReturn($mockException);
        $mockNotfier->shouldReceive('notify')->with('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');

        $sut = new ExceptionListener($mockNotfier, $environment);
        $sut->setEnvironmentConfigurations(['dev' => []]);
        $sut->onKernelException($mockEvent);
    }
}
