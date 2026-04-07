<?php

declare(strict_types=1);

namespace Plan2net\CmisBridge;

use CMIS\Session\SessionFactory as OptgovSessionFactory;
use CMIS\Session\SessionOptions;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Bridge class that provides dkd/php-cmis SessionFactory interface using optigov/php-cmis-client
 */
class SessionFactory
{
    /**
     * Create a session compatible with dkd/php-cmis interface
     *
     * @param array $parameters          Session parameters from dkd format
     * @param mixed $objectFactory       Not used in optigov, kept for compatibility
     * @param mixed $cache               Not used in optigov, kept for compatibility
     * @param mixed $typeDefinitionCache Not used in optigov, kept for compatibility
     * @param mixed $objectTypeCache     Not used in optigov, kept for compatibility
     *
     * @throws \InvalidArgumentException
     */
    public function createSession(
        array $parameters,
        $objectFactory = null,
        $cache = null,
        $typeDefinitionCache = null,
        $objectTypeCache = null
    ): Session {
        // Extract parameters from dkd format
        $url = $parameters['dkd.phpcmis.binding.browser.url'] ?? null;
        $repositoryId = $parameters['dkd.phpcmis.session.repository.id'] ?? null;
        $username = $parameters['dkd.phpcmis.user'] ?? null;
        $password = $parameters['dkd.phpcmis.password'] ?? null;
        $httpInvoker = $parameters['dkd.phpcmis.binding.httpinvoker.object'] ?? null;

        if (null === $url || '' === $url || null === $repositoryId || '' === $repositoryId) {
            throw new \InvalidArgumentException('URL and Repository ID are required for session creation');
        }

        // Extract credentials from HTTP invoker if available
        if (null !== $httpInvoker && method_exists($httpInvoker, 'getConfig')) {
            $config = $httpInvoker->getConfig();
            if (isset($config['auth'])) {
                $username = $config['auth'][0] ?? $username;
                $password = $config['auth'][1] ?? $password;
            }
        }

        // Create session options and extract Guzzle config from the httpInvoker
        $options = new SessionOptions();
        $guzzleConfig = ['auth' => [$username, $password]];
        if (null !== $httpInvoker && method_exists($httpInvoker, 'getConfig')) {
            $config = $httpInvoker->getConfig();
            $verifySSL = $config['verify'] ?? true;
            $options->setOption('verify', $verifySSL);
            $guzzleConfig['verify'] = $verifySSL;
            // Forward connect_timeout and timeout so requests fail fast when the server is unreachable
            if (isset($config['connect_timeout'])) {
                $guzzleConfig['connect_timeout'] = $config['connect_timeout'];
            }
            if (isset($config['timeout'])) {
                $guzzleConfig['timeout'] = $config['timeout'];
            }
        }

        // Build a Guzzle client with auth + timeout that will be used for all HTTP requests
        $guzzleClient = new GuzzleClient($guzzleConfig);

        // Create optigov session (used only for URL / repository-id resolution)
        $optgovSession = OptgovSessionFactory::create($url, $repositoryId, $username, $password, null, $options);

        // Return our bridge session
        return new Session($optgovSession, $parameters, $guzzleClient);
    }
}
