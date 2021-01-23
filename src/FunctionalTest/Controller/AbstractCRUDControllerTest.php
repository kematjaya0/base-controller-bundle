<?php

/**
 * This file is part of the symfony.
 */

namespace Kematjaya\BaseControllerBundle\FunctionalTest\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Field\InputFormField;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @package App\Tests\Controller
 * @license https://opensource.org/licenses/MIT MIT
 * @author  Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class AbstractCRUDControllerTest extends AbstractControllerTest
{
    abstract protected function buildObject();
    
    protected function saveObject($object)
    {
        $manager = $this->doctrine->getManager();
        $manager->persist($object);
        $manager->flush();
        
        return $object;
    }
    
    protected function login():void
    {
        
    }
    
    protected function doIndex(string $url, bool $includeFilter = false)
    {
        $this->login();
        $this->request(Request::METHOD_GET, $url);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        // filter
        $crawler = $this->request(Request::METHOD_GET, $url);
        $element = $crawler->filter('button[type=submit]');
        if (0 === $element->count()) {
            return;
        }
        
        $form = $element->form();
        if (!$form->getName()) {
            return;
        }
        
        if (!$includeFilter) {
            return;
        }
        
        $this->doTestFilter($url, $form);
    }
    
    protected function doTestFilter(string $url, Form $form)
    {
        $post = [];
        foreach ($form->get($form->getName()) as $field => $value) {
            switch(true) {
                case $value instanceof InputFormField:
                    $post[$field] = false !== strpos($value->getName(), '_at') ? (new \DateTime())->modify('-1day')->format("Y-m-d") : $value->getValue();
                    break;
                case is_array($value):
                    if (strpos($field, '_at')) {
                        $now = (new \DateTime())->modify('-1day');
                        foreach ($value as $type => $fields) {
                            $post[$field][$type] = $now->modify(sprintf('+1day'))->format("Y-m-d H:i:s");
                        }
                    }else {
                        $i = 1;
                        foreach ($value as $type => $fields) {
                            $post[$field][$type] = $i;
                            $i += 10;
                        }
                    }

                    break;
                case $value instanceof ChoiceFormField:
                    foreach ($value->availableOptionValues() as $optVal) {
                        if (strlen($optVal)>0) {
                            $post[$field] = $optVal;
                            break;
                        }
                    }
                    break;
                default:

                    break;
            }
        }

        $postData = [
            'submit' => true,
            $form->getName() => $post
        ];

        $this->request(Request::METHOD_POST, $url, $postData);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->request(Request::METHOD_GET, $url.'?_reset=1');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
    
    protected function processForm(string $url, $post = array(), $files = [])
    {
        $this->login();
        
        $crawler = $this->client->request(Request::METHOD_GET, $url);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $form = $crawler->filter('button[type=submit]')->form();
        
        $this->assertInstanceOf(Form::class, $form);
        
        if (!$form->getName()) {
            
            return;
        }
        
        // test csrf
        $post['_token'] =  'test';
        $postData = [
            'submit' => true,
            $form->getName() => $post
        ];

        $duplicatedFile = [];
        if (!empty($files)) {
            foreach ($files as $k => $file) {
                $fileNameDest = md5($file->getClientOriginalName()).'.'.$file->getClientOriginalExtension();

                if (copy($file->getPath() . DIRECTORY_SEPARATOR . $file->getClientOriginalName(), $file->getPath() . DIRECTORY_SEPARATOR . $fileNameDest)) {
                    $duplicatedFile[$k] = new UploadedFile($file->getPath() . DIRECTORY_SEPARATOR . $fileNameDest, $fileNameDest);
                }
            }

            $files = [
                $form->getName() => $files
            ];
        }
        
        if (!empty($postData) or !empty($files)) {
            
            $this->errorPostForm($url, $postData, $files);
            // test true
            $postData[$form->getName()]['_token'] =  $form->get($form->getName())['_token']->getValue();
            
            if (!empty($files) and !empty($duplicatedFile)) {
                $files = [
                    $form->getName() => $duplicatedFile
                ];
            }
            
            $postData[$form->getName()]['_token'] =  $form->get($form->getName())['_token']->getValue();
            
            $this->successPostForm($url, $postData, $files);
        }
    }
    
    protected function errorPostForm(string $url, $postData = array(), $files = array()):void
    {
        $this->client->request(Request::METHOD_POST, $url, $postData, $files);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $output = $this->client->getResponse()->getContent();
        $strPos = strpos($output, '<div class="alert alert-danger"><p><strong>The CSRF token is invalid. Please try to resubmit the form.</strong></p></div>');
        $this->assertTrue(false !== $strPos);
    }
    
    protected function successPostForm(string $url, $postData = array(), $files = array()):void
    {
        $this->client->request(Request::METHOD_POST, $url, $postData, $files);
        $this->assertTrue($this->client->getResponse()->isRedirection());
    }
    
    protected function ajaxForm(string $url, $post = array(), $files = [])
    {
        $this->login();
        
        $crawler = $this->client->request(Request::METHOD_GET, $url);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $form = $crawler->filter('button[type=submit]')->form();
        
        $this->assertInstanceOf(Form::class, $form);
        
        if (!$form->getName()) {
            
            return;
        }
        
        // test csrf
        $post['_token'] =  'test';
        $postData = [
            'submit' => true,
            $form->getName() => $post
        ];

        $duplicatedFile = [];
        if (!empty($files)) {
            foreach ($files as $k => $file) {
                $fileNameDest = md5($file->getClientOriginalName()).'.'.$file->getClientOriginalExtension();

                if (copy($file->getPath() . DIRECTORY_SEPARATOR . $file->getClientOriginalName(), $file->getPath() . DIRECTORY_SEPARATOR . $fileNameDest)) {
                    $duplicatedFile[$k] = new UploadedFile($file->getPath() . DIRECTORY_SEPARATOR . $fileNameDest, $fileNameDest);
                }
            }

            $files = [
                $form->getName() => $files
            ];
        }
        
        if (!empty($postData) or !empty($files)) {
            
            $this->errorPostAjaxForm($url, $postData, $files);
            // test true
            $postData[$form->getName()]['_token'] =  $form->get($form->getName())['_token']->getValue();
            
            if (!empty($files) and !empty($duplicatedFile)) {
                $files = [
                    $form->getName() => $duplicatedFile
                ];
            }
            
            $postData[$form->getName()]['_token'] =  $form->get($form->getName())['_token']->getValue();
            
            $this->successPostAjaxForm($url, $postData, $files);
        }
    }
    
    protected function errorPostAjaxForm(string $url, $postData = array(), $files = array()):void
    {
        $this->client->request(Request::METHOD_POST, $url, $postData, $files);//dump($this->client->getResponse()->getContent());exit;
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $output = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue(json_last_error() == JSON_ERROR_NONE);
        $this->assertTrue($output['process']);
        $this->assertTrue(strlen($output['errors'])>0);
    }
    
    protected function successPostAjaxForm(string $url, $postData = array(), $files = array()):void
    {
        $this->client->request(Request::METHOD_POST, $url, $postData, $files);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $output = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue(json_last_error() == JSON_ERROR_NONE);
        $this->assertTrue($output['process'] and $output['status']);
    }
    
    protected function doDelete(string $url, $object, string $urlReferer = null, $isAjax = false, bool $checkObject = false)
    {
        $this->login();
        
        $token = self::$container->get('security.csrf.token_manager')->getToken('delete' . $object->getId());
        $server = [];
        if ($urlReferer) {
            $server['HTTP_REFERER'] = $urlReferer;
        }
        
        $identifier = $object->getId();
        $this->client->request(Request::METHOD_DELETE, $url, [
            '_token' => (string) $token,
        ], [], $server);
        
        if ($urlReferer) {
            $this->assertTrue($this->client->getResponse()->isRedirect($urlReferer));
        } else {
            $this->assertTrue($this->client->getResponse()->isRedirection());
        }
            
        if ($isAjax) {
            $result = json_decode($this->client->getResponse()->getContent(), true);
            $this->assertTrue($result['status']);
        }
        
        if ($checkObject) {
            $objects = $this->doctrine->getRepository(get_class($object))->find($identifier);
            $this->assertNull($objects);
        }
            
    }
    
    protected function doShow(string $url) {
        $this->login();
        $this->request(Request::METHOD_GET, $url);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
}
