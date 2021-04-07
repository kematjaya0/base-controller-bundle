<?php

namespace Kematjaya\BaseControllerBundle\Controller;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @author Nur Hidayatullah <kematjaya0@gmail.com>
 */
abstract class BaseController extends AbstractController 
{
    /**
     *
     * @var TranslatorInterface
     */
    protected $translator;
    
    public function __construct(TranslatorInterface $translator) 
    {
        $this->translator = $translator;
    }
    
    protected function buildSuccessResult(string $type, $object)
    {
        return [
            "process" => true, 
            "status" => true, 
            "message" => $this->translator->trans('messages.'.$type.'.success'), 
            "errors" => null
        ];
    }
    
    protected function buildErrorResult(string $type, string $message)
    {
        return [
            "process" => true, 
            "status" => false, 
            "message" => $this->translator->trans('messages.'.$type.'.error'), 
            "errors" => $message
        ];
    }
    
    protected function processFormAjax(Request $request, FormInterface $form):array
    {
        $form->handleRequest($request);
        
        if (!$form->isSubmitted()) {
            
            return ["process" => false];
        }
        
        $type = ($form->getData() && !is_array($form->getData()) && $form->getData()->getId()) ? "update": "add";
        if (!$form->isValid()) {
            
            $errors = $this->getErrorsFromForm($form);

            return $this->buildErrorResult($type, implode(", ", $errors));
        }
        
        $manager = $this->getDoctrine()->getManager();
        try {

            $object = $this->saveObject($form->getData(), $manager);

            return $this->buildSuccessResult($type, $object);
        } catch (\Exception $ex) {

            return $this->buildErrorResult($type, $ex->getMessage());
        }
    }
    
    protected function saveObject($object, EntityManagerInterface $manager)
    {
        $manager->transactional(function (EntityManagerInterface $em) use ($object) {
            $em->persist($object);
        });
        
        return $object;
    }
    
    protected function processForm(Request $request, FormInterface $form, $func = null)
    {
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            
            return false;
        }
        
        if (!$form->isValid()) {
            $this->addFlash("error", implode(', ', $this->getErrorsFromForm($form)));
            
            return false;
        }
        
        $manager = $this->getDoctrine()->getManager();
        try {
            $object = $this->saveObject($form->getData(), $manager);

            $this->addFlash("info", $this->getSuccessMessage($object));

            return $object;
        } catch (\Exception $ex) {
            $this->addFlash("error", $this->getErrorMessage($ex));
        }
        
        return false;
    }
    
    protected function getSuccessMessage($object):string
    {
        return $this->translator->trans('successfull_save');
    }
    
    protected function getErrorMessage(\Exception $ex):string
    {
        return $ex->getMessage();
    }
    
    protected function getErrorsFromForm(FormInterface $form):array
    {
        $errors = array();
        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        
        foreach ($form->all() as $childForm) {
            if (!$childForm instanceof FormInterface) {
                continue;
            }
            
            $childErrors = $this->getErrorsFromForm($childForm);
            if (!$childErrors) {
                continue;
            } 
            
            $errors[$childForm->getName()] = sprintf('%s: %s', $this->translator->trans($childForm->getName()), implode(", ", $childErrors));
        }
        
        return $errors;
    }
    
    protected function doDelete(Request $request, $object, string $tokenName):void
    {
        if (!$this->isCsrfTokenValid($tokenName, $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('csrf_token_detected'));
            return;
        }
        
        $manager = $this->getDoctrine()->getManager();
        try{
            
            $this->removeObject($object, $manager);
            
            $this->addFlash('info', $this->translator->trans('successfull_delete'));
        } catch (\Exception $ex) {
            $this->addFlash('error', $this->getErrorMessage($ex));
        }
    }
    
    protected function removeObject($object, EntityManagerInterface $manager):void
    {
        $manager->transactional(function (EntityManagerInterface $em) use ($object) {
            $em->remove($object);
        });

        if($object->getId()) {
            $qb = $manager->createQueryBuilder('t')->delete(get_class($object), 'obj')->where('obj.id = :id')
                ->setParameter('id', $object->getId());
            $qb->getQuery()->execute();
        }
    }
}
