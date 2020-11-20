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
    
    protected function processFormAjax(Request $request, FormInterface $form):array
    {
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) 
        {
            $type = ($form->getData() && !is_array($form->getData()) && $form->getData()->getId()) ? "update": "add";
            if($form->isValid()) 
            {
                $manager = $this->getDoctrine()->getManager();
                try 
                {
                    
                    $object = $this->saveObject($form->getData(), $manager);
                    
                    return ["process" => true, "status" => true, "message" => $this->translator->trans('messages.'.$type.'.success'), "errors" => null];
                } catch (\Exception $ex) 
                {
                    return ["process" => true, "status" => false, "message" => $this->translator->trans('messages.'.$type.'.error'), "errors" => $ex->getMessage()];
                }
            }else
            {
                $errors = $this->getErrorsFromForm($form);
				
                return ["process" => true, "status" => false, "message" => $this->translator->trans('messages.'.$type.'.error'), "errors" => implode(", ", $errors)];
            }
        }
        
        return ["process" => false];
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
        
        if ($form->isSubmitted())
        {
            if($form->isValid())
            {
                $manager = $this->getDoctrine()->getManager();
                try
                {
                    $object = $this->saveObject($form->getData(), $manager);
                    
                    $this->addFlash("info", $this->translator->trans('successfull_save'));
                    
                    return $object;
                } catch (\Exception $ex) 
                {
                    $this->addFlash("error", 'error : ' . $ex->getMessage());
                }
            }else
            {
                $this->addFlash("error", implode(', ', $this->getErrorsFromForm($form)));
            }
        }
        
        return false;
    }
    
    protected function getErrorsFromForm(FormInterface $form):array
    {
        $errors = array();
        foreach ($form->getErrors() as $error) 
        {
            $errors[] = $error->getMessage();
        }
        
        foreach ($form->all() as $childForm) 
        {
            if ($childForm instanceof FormInterface) 
            {
                if ($childErrors = $this->getErrorsFromForm($childForm)) 
                {
                    $errors[$childForm->getName()] = sprintf('%s: %s', $this->translator->trans($childForm->getName()), implode(", ", $childErrors));
                }
            }
        }
        return $errors;
    }
    
    protected function doDelete(Request $request, $object, string $tokenName)
    {
        if ($this->isCsrfTokenValid($tokenName, $request->request->get('_token'))) 
        {
            $manager = $this->getDoctrine()->getManager();
            try{
                $manager->transactional(function (EntityManagerInterface $em) use ($object) {
                    $em->remove($object);
                });
                
                if($object->getId())
                {
                    $qb = $em->createQueryBuilder('t')->delete(get_class($object), 'obj')->where('obj.id = :id')
                        ->setParameter('id', $object->getId());
                    $qb->getQuery()->execute();
                }
                
                $this->addFlash('info', $this->translator->trans('successfull_delete'));
            } catch (\Exception $ex) 
            {
                $this->addFlash('error', 'error :' . $ex->getMessage());
            }
        } else 
        {
            $this->addFlash('error', $this->translator->trans('csrf_token_detected'));
        }
    }
}
