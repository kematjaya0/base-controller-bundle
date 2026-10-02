<?php

/**
 * This file is part of the symfony.
 */

namespace Kematjaya\BaseControllerBundle\FunctionalTest\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class AbstractControllerTest extends WebTestCase
{
    protected Registry $doctrine;

    protected KernelBrowser $client;

    protected RouterInterface $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->doctrine = static::getContainer()->get('doctrine');

        $this->router = static::getContainer()->get('router');
    }

    protected function request(string $method, string $uri, array $parameters = [], array $files = [], array $server = [], ?string $content = null, bool $changeHistory = true): Crawler
    {
        return $this->client->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    protected function generate(string $name, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): string
    {
        return $this->router->generate($name, $parameters, $referenceType);
    }

    protected function getExcelFormats(): array
    {
        return [
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    protected function isExcelResponse(string $url): void
    {
        $excels = $this->getExcelFormats();
        $this->client->request(Request::METHOD_GET, $url);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertTrue(in_array($this->client->getResponse()->headers->get('content-type'), $excels));
    }

    protected function getPdfType(): array
    {
        return ['application/pdf'];
    }

    protected function isPdfResponse(string $url): void
    {
        $this->client->request(Request::METHOD_GET, $url);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertTrue(in_array($this->client->getResponse()->headers->get('content-type'), $this->getPdfType()));
    }
}
