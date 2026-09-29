# base-controller-bundle

Base component for traditional web applications on **Symfony 6.4** (PHP 8.1+), supporting **Doctrine ORM 2 and 3**.

## Requirements

| This bundle | Symfony | PHP | Doctrine ORM | Filter bundle |
|---|---|---|---|---|
| `6.4.x` | `^6.4` | `>=8.1` | `^2.19` or `^3.2` | `spiriitlabs/form-filter-bundle` `^10.0\|^11.0\|^12.0` |

The filter bundle version is selected by Composer based on your ORM version: ORM 2 resolves to
Spiriit `v10`, ORM 3 resolves to `v11`/`v12`.

## 1. Installation

```bash
composer require kematjaya/base-controller-bundle
```

## 2. Register the bundle

`config/bundles.php`:

```php
Kematjaya\BaseControllerBundle\BaseControllerBundle::class => ['all' => true],
```

If you use the filter helpers (`BaseLexikFilterController`, `AbstractFilterType`),
you must also register the filter bundle — the compiler pass injects
`FilterBuilderUpdaterInterface` into every controller tagged for filtering:

```php
Spiriit\Bundle\FormFilterBundle\SpiriitFormFilterBundle::class => ['all' => true],
```

## 3. Usage

### 3.1 Controller

```php
use App\Entity\Foo;
use App\Form\FooType;
use App\Filter\FooFilterType;
use App\Repository\FooRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Kematjaya\BaseControllerBundle\Controller\BaseLexikFilterController as BaseController;

#[Route('/foo', name: 'foo_')]
class FooController extends BaseController
{
    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, FooRepository $repo): Response
    {
        // create the filter form
        $form = $this->createFormFilter(FooFilterType::class);
        // apply the filter
        $queryBuilder = $this->buildFilter($request, $form, $repo->createQueryBuilder('this'));

        return $this->render('foo/index.html.twig', [
            'datas' => $this->createPaginator($queryBuilder, $request),
            'filter' => $form->createView(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $foo = new Foo();

        // AJAX flow
        $form = $this->createForm(FooType::class, $foo, [
            'attr' => ['id' => 'ajaxForm', 'action' => $this->generateUrl('foo_new')],
        ]);
        $result = $this->processFormAjax($request, $form);
        if ($result['process']) {
            return $this->json($result);
        }

        // non-AJAX flow
        $form = $this->createForm(FooType::class, $foo, [
            'action' => $this->generateUrl('foo_new'),
        ]);
        $result = $this->processForm($request, $form);
        if ($result) {
            return $this->redirectToRoute('foo_index');
        }

        return $this->render('foo/form.html.twig', [
            'foo' => $foo,
            'form' => $form->createView(),
            'title' => 'new',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Foo $foo): Response
    {
        $form = $this->createForm(FooType::class, $foo, [
            'attr' => ['id' => 'ajaxForm', 'action' => $this->generateUrl('foo_edit', ['id' => $foo->getId()])],
        ]);
        $result = $this->processFormAjax($request, $form);
        if ($result['process']) {
            return $this->json($result);
        }

        $form = $this->createForm(FooType::class, $foo, [
            'action' => $this->generateUrl('foo_edit', ['id' => $foo->getId()]),
        ]);
        $result = $this->processForm($request, $form);
        if ($result) {
            return $this->redirectToRoute('foo_index');
        }

        return $this->render('foo/form.html.twig', [
            'foo' => $foo,
            'form' => $form->createView(),
            'title' => 'edit',
        ]);
    }

    #[Route('/{id}/show', name: 'show', methods: ['GET'])]
    public function show(Foo $foo): Response
    {
        return $this->render('foo/show.html.twig', ['foo' => $foo]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['DELETE'])]
    public function delete(Request $request, Foo $foo): Response
    {
        // doDelete() validates the CSRF token itself and never deletes when it
        // is missing or invalid.
        $this->doDelete($request, $foo, 'delete'.$foo->getId());

        return $this->redirectToRoute('foo_index');
    }
}
```

> **Note on the CSRF token in `DELETE` requests**
> `doDelete()` reads the token from the request body first and falls back to
> the query string. Symfony's `Request::create()` places `DELETE` parameters in
> the *body*, so functional tests and AJAX calls that pass `_token` as a
> request parameter work unchanged. A plain browser link cannot send a `DELETE`
> body, so build those URLs as `?_token=<token>`.

### 3.2 Form types

```php
// src/Form/FooType.php
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Kematjaya\BaseControllerBundle\Type\PhoneNumberType;
use Kematjaya\BaseControllerBundle\Type\DateRangeType;
use Kematjaya\BaseControllerBundle\Type\AutoCompleteType;
use Kematjaya\BaseControllerBundle\Type\AutoCompleteEntityType;

class FooType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('phone', PhoneNumberType::class, ['label' => 'phone']);

        $builder->add('createdAt', DateRangeType::class, [
            'label' => 'created at',
            'from_options' => ['widget' => 'single_text'],
            'to_options' => ['widget' => 'single_text'],
        ]);

        // attributes you pass are preserved; only the defaults are added
        $builder->add('city', AutoCompleteType::class, [
            'url' => $this->generateUrl('city_autocomplete'),
            'dom_parent' => '#city-list',
        ]);

        $builder->add('country', AutoCompleteEntityType::class, [
            'url' => $this->generateUrl('country_autocomplete'),
            'class' => Country::class,
            'property' => 'id',
            'property_label' => 'name',
        ]);
    }
}
```

`config/packages/twig.yaml`:

```yaml
twig:
    form_themes:
        - '@BaseController/phone_number_layout.html.twig'
```

### 3.3 Filters

Filters are based on [SpiriitFormFilterBundle](https://github.com/SpiriitLabs/form-filter-bundle),
the maintained fork of the abandoned LexikFormFilterBundle.

```php
// src/Filter/FooFilterType.php
use Symfony\Component\Form\FormBuilderInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\ChoiceFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\DateRangeFilterType;
use Kematjaya\BaseControllerBundle\Filter\AbstractFilterType;

class FooFilterType extends AbstractFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('roles', ChoiceFilterType::class, [
            'choices' => [],
            // helpers: $this->JSONQuery(), $this->dateRangeQuery(), $this->floatRangeQuery()
            'apply_filter' => $this->JSONQuery(),
        ]);

        $builder->add('createdAt', DateRangeFilterType::class, [
            'apply_filter' => $this->dateRangeQuery(),
        ]);
    }
}
```

The helper closures return `null` when no condition applies, which is the
idiomatic "no condition" value in Spiriit 11/12.

## 4. Upgrading from 2.4.3

### 4.1 `setDoctrine()` was renamed to `setManagerRegistry()`

The compiler pass injects the registry with the setter `setManagerRegistry()`.
The setter and `getDoctrine()` are now declared on
`DoctrineManagerRegistryControllerInterface`, matching the naming used by
`doctrine/orm` 3.

If you called `setDoctrine()` directly (rare — it is a service setter), rename it.

### 4.2 The session is resolved from the `RequestStack`

`$this->get('session')` no longer works: `AbstractController::get()` was
removed in Symfony 5.0 and FrameworkBundle no longer exposes a `session`
service by default. `BaseController::getSession()` now resolves the session
from the injected `RequestStack`, and is declared on
`SessionControllerInterface`.

### 4.3 Doctrine ORM 3 compatibility

`EntityManager::transactional()` was removed in ORM 3, so `saveObject()` and
`removeObject()` use `wrapInTransaction()`, which exists in both ORM 2.19+ and
3.x. `removeObject()` no longer issues a second raw DQL `DELETE` for the same
row — that bypassed the identity map, cascade rules and lifecycle callbacks.

> **After a failed save the EntityManager is closed**
> `wrapInTransaction()` rolls the transaction back **and closes** the manager on
> failure (verified on ORM 2.20.13 and 3.7.2, both call `close()` from a
> `finally` block). Nothing is written — the rollback is correct — but the
> manager is unusable afterwards. If your code has to keep working after a
> failed `saveObject()`/`removeObject()`, fetch a fresh manager from the
> registry rather than reusing the one you passed in.

### 4.4 `TokenType` in custom AST filters

`Doctrine\ORM\Query\Lexer::T_*` integer constants were removed in ORM 3. Use
`TokenTypeResolverTrait` in your filter node instead of `Lexer::T_*`:

```php
use Kematjaya\BaseControllerBundle\AST\TokenTypeResolverTrait;

class MyFunctionNode extends Node
{
    use TokenTypeResolverTrait;

    // resolves to an enum case on ORM 3 and an int constant on ORM 2
    private $t = 'T_IDENTIFIER';
}
```

### 4.5 Test base classes

`static::$container` was removed in Symfony 5. Replace
`static::$container->get(...)` with `static::getContainer()->get(...)` in
subclasses of `AbstractControllerTest` and `AbstractCRUDControllerTest`.

### 4.6 `AutoCompleteType` attribute merging

The `attr` normalizer no longer *replaces* the attribute array. It merges your
attributes with the `class`/`url` defaults, so `'attr' => ['id' => 'x']` is no
longer silently dropped.

### 4.7 Template escaping

`AutoCompleteType` and `AutoCompleteEntityType` now HTML-escape the generated
attribute string before it is rendered with `|raw`. If you provided a custom
form theme that relies on the previous, unescaped output, re-check any
attribute values you interpolate.
