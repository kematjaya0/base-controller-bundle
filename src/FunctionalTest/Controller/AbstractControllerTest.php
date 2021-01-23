<?php

/**
 * This file is part of the symfony.
 */

namespace Kematjaya\BaseControllerBundle\FunctionalTest\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class AbstractControllerTest extends WebTestCase
{
    /**
     * @var Registry
     */
    protected $doctrine;
    
    /**
     * @var KernelBrowser
     */
    protected $client;
    
    /**
     *
     * @var RouterInterface
     */
    protected $router;
    
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->doctrine = static::$container->get('doctrine');
        
        $this->router = static::$container->get('router');
    }
    
    protected function request(string $method, string $uri, array $parameters = [], array $files = [], array $server = [], string $content = null, bool $changeHistory = true)
    {   
        return $this->client->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
            
    }
    
    protected function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_URL)
    {
        return $this->router->generate($name, $parameters, $referenceType);
    }
    
    protected function getExcelFormats():array
    {
        return [
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
    }
    
    protected function isExcelResponse(string $url)
    {
        $excels = $this->getExcelFormats();
        $this->client->request(Request::METHOD_GET, $url);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertTrue(in_array($this->client->getResponse()->headers->get('content-type'), $excels));
    }
    
    protected function getPdfType()
    {
        return ['application/pdf'];
    }
    
    protected function isPdfResponse(string $url)
    {
        $this->client->request(Request::METHOD_GET, $url);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertTrue(in_array($this->client->getResponse()->headers->get('content-type'), $this->getPdfType()));
    }
}
