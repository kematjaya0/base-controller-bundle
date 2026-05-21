# base-controller-bundle

Base component for Symfony 6/7/8 applications providing reusable CRUD controllers, pagination, filtering, and custom form types.

## Requirements

- PHP >= 8.1
- Symfony 6.4+ / 7.4+ / 8.0+

## Installation

```bash
composer require kematjaya/base-controller-bundle
```

Add to `config/bundles.php`:

```php
Kematjaya\BaseControllerBundle\BaseControllerBundle::class => ['all' => true]
```

## Usage

### Controller

```php
// src/Controller/FooController.php
namespace App\Controller;

use App\Entity\Foo;
use App\Form\FooType;
use App\Filter\FooFilterType;
use App\Repository\FooRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Kematjaya\BaseControllerBundle\Controller\FilterBuilderController as BaseController;

#[Route('/foo', name: 'foo_')]
class FooController extends BaseController
{
    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, FooRepository $repo): Response
    {
        $form = $this->createFormFilter(FooFilterType::class);
        $queryBuilder = $this->buildFilter($request, $form, $repo->createQueryBuilder('this'));

        return $this->render('foo/index.html.twig', [
            'datas' => parent::createPaginator($queryBuilder, $request),
            'filter' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $foo = new Foo();

        // Ajax form processing
        $form = $this->createForm(FooType::class, $foo, [
            'attr' => ['id' => 'ajaxForm', 'action' => $this->generateUrl('foo_new')],
        ]);
        $result = parent::processFormAjax($request, $form);
        if ($result['process']) {
            return $this->json($result);
        }

        // Non-ajax form processing
        $form = $this->createForm(FooType::class, $foo, [
            'action' => $this->generateUrl('foo_new'),
        ]);
        $result = parent::processForm($request, $form);
        if ($result['process']) {
            return $this->redirectToRoute('foo_index');
        }

        return $this->render('foo/form.html.twig', [
            'foo' => $foo,
            'form' => $form->createView(),
            'title' => 'new',
        ]);
    }

    #[Route('/{id}/show', name: 'show', methods: ['GET'])]
    public function show(Foo $foo): Response
    {
        return $this->render('foo/show.html.twig', [
            'foo' => $foo,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Foo $foo): Response
    {
        $form = $this->createForm(FooType::class, $foo, [
            'attr' => ['id' => 'ajaxForm', 'action' => $this->generateUrl('foo_edit', ['id' => $foo->getId()])],
        ]);
        $result = parent::processFormAjax($request, $form);
        if ($result['process']) {
            return $this->json($result);
        }

        $form = $this->createForm(FooType::class, $foo, [
            'action' => $this->generateUrl('foo_edit', ['id' => $foo->getId()]),
        ]);
        $result = parent::processForm($request, $form);
        if ($result['process']) {
            return $this->redirectToRoute('foo_index');
        }

        return $this->render('foo/form.html.twig', [
            'foo' => $foo,
            'form' => $form->createView(),
            'title' => 'edit',
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['DELETE'])]
    public function delete(Request $request, Foo $foo): Response
    {
        $tokenName = 'delete'.$foo->getId();
        parent::doDelete($request, $foo, $tokenName);

        return $this->redirectToRoute('foo_index');
    }
}
```

### Form Type

```php
// src/Form/FooType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Kematjaya\BaseControllerBundle\Type\PhoneNumberType;
use Kematjaya\BaseControllerBundle\Type\DateRangeType;

class FooType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('phone', PhoneNumberType::class, [
            'label' => 'phone',
        ]);

        $builder->add('dateRange', DateRangeType::class, [
            'label' => 'date range',
            'from_options' => ['widget' => 'single_text'],
            'to_options' => ['widget' => 'single_text'],
        ]);
    }
}
```

Add to `config/packages/twig.yaml`:

```yaml
twig:
    form_themes:
        - '@BaseController/phone_number_layout.html.twig'
```

### Filter

Filter form based on [SpiriitLabs FormFilterBundle](https://github.com/SpiriitLabs/form-filter-bundle) (fork of LexikFormFilterBundle).

```php
// src/Filter/FooFilterType.php
namespace App\Filter;

use Symfony\Component\Form\FormBuilderInterface;
use Kematjaya\BaseControllerBundle\Filter\AbstractFilterType;

class FooFilterType extends AbstractFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('roles', Filter\ChoiceFilterType::class, [
            'choices' => [],
            'apply_filter' => $this->JSONQuery(),
        ]);
    }
}
```

Available query helper methods (from `FilterFunctionTrait`):
- `JSONQuery()` — query JSON columns with LIKE
- `dateRangeQuery()` — filter by date range
- `floatRangeQuery()` — filter by numeric range

## Development

```bash
composer test         # run PHPUnit tests
composer phpstan      # run static analysis (level 6)
composer cs:check     # check coding standards (dry-run)
composer cs:fix       # auto-fix coding standards
```
