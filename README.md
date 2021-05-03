# base-controller-bundle
Base component for symfony 5 application
1. installation
```
composer require kematjaya/base-controller-bundle
```
2. add to config/bundles.php
```
Kematjaya\BaseControllerBundle\BaseControllerBundle::class => ['all' => true]
```
3. usage
3.1. Controller
```
use App\Entity\Foo;
use App\Form\FooType;
use App\Filter\FooFilterType;
use App\Repository\FooRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Kematjaya\BaseControllerBundle\Controller\BaseLexikFilterController as BaseController;
...

/**
 * @Route("/foo", name="foo_")
 */
class FooController extends BaseController
{
    /**
     * @Route("/", name="index", methods={"GET", "POST"})
     */
    public function index(Request $request, FooRepository $repo): Response
    {
        // create filter form objct
        $form = $this->createFormFilter(FooFilterType::class);
        // processing filter form
        $queryBuilder = $this->buildFilter($request, $form, $repo->createQueryBuilder('this'));
                
        return $this->render('foo/index.html.twig', [
            'datas' => parent::createPaginator($queryBuilder, $request),  // create pagination for data
            'filter' => $form->createView() 
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $foo = new Foo();
        
        // processing form ajax
        $form = $this->createForm(FooType::class, $foo, [
            'attr' => ['id' => 'ajaxForm', 'action' => $this->generateUrl('foo_new')]
        ]);
        $result = parent::processFormAjax($request, $form);
        if ($result['process']) {
            
            return $this->json($result);
        }
        // end processing ajax
        // processing wihout ajax form
        $form = $this->createForm(FooType::class, $foo, [
            'action' => $this->generateUrl('foo_new')]
        ]);
        $result = parent::processForm($request, $form);
        if ($result['process']) {
            
            return $this->redirectToRoute('foo_index');
        }
        // end processing wihout ajax form

        return $this->render('foo/form.html.twig', [
            'foo' => $foo,
            'form' => $form->createView(), 'title' => 'new'
        ]);
    }

    /**
     * @Route("/{id}/show", name="show", methods={"GET"})
     */
    public function show(Foo $foo): Response
    {
        return $this->render('foo/show.html.twig', [
            'foo' => $foo,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Foo $foo): Response
    {
        // processing form ajax
        $form = $this->createForm(FooType::class, $foo, [
            'attr' => ['id' => 'ajaxForm', 'action' => $this->generateUrl('foo_edit', ['id' => $foo->getId()])]
        ]);
        $result = parent::processFormAjax($request, $form);
        if ($result['process']) {
            
            return $this->json($result);
        }
        // end processing ajax
        // processing wihout ajax form
        $form = $this->createForm(FooType::class, $foo, [
            'action' => $this->generateUrl('foo_edit', ['id' => $foo->getId()])
        ]);
        $result = parent::processForm($request, $form);
        if ($result['process']) {
            
            return $this->redirectToRoute('foo_index');
        }
        // end processing wihout ajax form
        
        return $this->render('foo/form.html.twig', [
            'foo' => $foo,
            'form' => $form->createView(), 'title' => 'edit'
        ]);
    }

    /**
     * @Route("/{id}/delete", name="delete", methods={"DELETE"})
     */
    public function delete(Request $request, Foo $foo): Response
    {
        $tokenName = 'delete'.$foo->getId();
        parent::doDelete($request, $foo, $tokenName);
        
        return $this->redirectToRoute('foo_index');
    }
}
```
3.2. Form Type
```
// src/Form/FooType.php
...
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Kematjaya\BaseControllerBundle\Type\PhoneNumberType;
use Kematjaya\BaseControllerBundle\Type\DateRangeType;
...
class FooType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Phone Number Type
        $builder->add('phone', PhoneNumberType::class, [
                'label' => 'phone'
            ]);
        
        // Date Range Type
        $builder->add('phone', DateRangeType::class, [
                'label' => 'phone',
                'from_options' => ['widget' => 'single_text'],
                'to_options' => ['widget' => 'single_text']
            ]);
    }
}
```
- add to config/packages/twig.yaml
```
twig:
    form_themes: 
        - '@BaseController/phone_number_layout.html.twig'
```
3.3 Filter
- filter form base on LexikFormFilterBundle : https://github.com/lexik/LexikFormFilterBundle
- usage:
```
// src/Filter/FooFilterType.php
...
use Symfony\Component\Form\FormBuilderInterface;
use Kematjaya\BaseControllerBundle\Filter\AbstractFilterType;
...

class FooFilterType extends AbstractFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('roles', Filters\ChoiceFilterType::class, [
                'choices' => [],
                // query json use JSONQuery(), 
                // date range filter use dateRangeQuery(),
                // float / integer range use floatRangeQuery()
                'apply_filter' => $this->JSONQuery(),
                // 
            ]);
    }
}
```
