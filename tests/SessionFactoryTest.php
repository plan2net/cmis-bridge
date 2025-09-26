<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge\Tests;

use PHPUnit\Framework\TestCase;
use Plan2net\CmisBridge\Session;
use Plan2net\CmisBridge\SessionFactory;

/**
 * Test for SessionFactory class
 */
class SessionFactoryTest extends TestCase
{
    public function testCreateSessionWithValidParameters(): void
    {
        $factory = new SessionFactory();

        $parameters = [
            'dkd.phpcmis.binding.browser.url' => 'https://test.example.com/alfresco/api/-default-/public/cmis/versions/1.1/browser',
            'dkd.phpcmis.session.repository.id' => '-default-',
            'dkd.phpcmis.user' => 'testuser',
            'dkd.phpcmis.password' => 'testpass'
        ];

        $session = $factory->createSession($parameters);

        $this->assertInstanceOf(Session::class, $session);
    }

    public function testCreateSessionThrowsExceptionForMissingUrl(): void
    {
        $factory = new SessionFactory();

        $parameters = [
            'dkd.phpcmis.session.repository.id' => '-default-',
            'dkd.phpcmis.user' => 'testuser',
            'dkd.phpcmis.password' => 'testpass'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL and Repository ID are required for session creation');

        $factory->createSession($parameters);
    }

    public function testCreateSessionThrowsExceptionForEmptyUrl(): void
    {
        $factory = new SessionFactory();

        $parameters = [
            'dkd.phpcmis.binding.browser.url' => '',
            'dkd.phpcmis.session.repository.id' => '-default-',
            'dkd.phpcmis.user' => 'testuser',
            'dkd.phpcmis.password' => 'testpass'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL and Repository ID are required for session creation');

        $factory->createSession($parameters);
    }

    public function testCreateSessionThrowsExceptionForMissingRepositoryId(): void
    {
        $factory = new SessionFactory();

        $parameters = [
            'dkd.phpcmis.binding.browser.url' => 'https://test.example.com/alfresco',
            'dkd.phpcmis.user' => 'testuser',
            'dkd.phpcmis.password' => 'testpass'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL and Repository ID are required for session creation');

        $factory->createSession($parameters);
    }

    public function testCreateSessionThrowsExceptionForEmptyRepositoryId(): void
    {
        $factory = new SessionFactory();

        $parameters = [
            'dkd.phpcmis.binding.browser.url' => 'https://test.example.com/alfresco',
            'dkd.phpcmis.session.repository.id' => '',
            'dkd.phpcmis.user' => 'testuser',
            'dkd.phpcmis.password' => 'testpass'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL and Repository ID are required for session creation');

        $factory->createSession($parameters);
    }

    public function testCreateSessionWorksWithoutCredentials(): void
    {
        $factory = new SessionFactory();

        $parameters = [
            'dkd.phpcmis.binding.browser.url' => 'https://test.example.com/alfresco',
            'dkd.phpcmis.session.repository.id' => '-default-'
        ];

        // Should create session even without explicit credentials
        $session = $factory->createSession($parameters);

        $this->assertInstanceOf(Session::class, $session);
    }

    public function testCreateSessionWithHttpInvoker(): void
    {
        $factory = new SessionFactory();

        // Create anonymous class with getConfig method for testing
        $httpInvoker = new class {
            public function getConfig(): array
            {
                return [
                    'auth' => ['invoker_user', 'invoker_pass']
                ];
            }
        };

        $parameters = [
            'dkd.phpcmis.binding.browser.url' => 'https://test.example.com/alfresco',
            'dkd.phpcmis.session.repository.id' => '-default-',
            'dkd.phpcmis.binding.httpinvoker.object' => $httpInvoker
        ];

        $session = $factory->createSession($parameters);

        $this->assertInstanceOf(Session::class, $session);
    }

    public function testCreateSessionWithAllOptionalParameters(): void
    {
        $factory = new SessionFactory();

        $parameters = [
            'dkd.phpcmis.binding.browser.url' => 'https://test.example.com/alfresco',
            'dkd.phpcmis.session.repository.id' => 'custom-repo',
            'dkd.phpcmis.user' => 'admin',
            'dkd.phpcmis.password' => 'secret123'
        ];

        $session = $factory->createSession(
            $parameters,
            null, // objectFactory - not used in bridge
            null, // cache - not used in bridge
            null, // typeDefinitionCache - not used in bridge
            null  // objectTypeCache - not used in bridge
        );

        $this->assertInstanceOf(Session::class, $session);
    }
}
