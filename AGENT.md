# kematjaya/base-controller-bundle

Base component for traditional Symfony web apps (CRUD, pagination, filters, forms).

## PSR

- **PSR-4**: `Kematjaya\BaseControllerBundle\` -> `src/`, `Tests\` -> `tests/`
- **PSR-3**: LoggerInterface used in Sentry skill refs
- **PSR-6**: Caching (CacheItemPoolInterface) in doctrine/Sentry contexts
- **PSR-7**: HTTP messages (converted in Laravel bridge)

## Stack

- Symfony 6/7/8, Doctrine ORM 2/3, KNP Paginator
- LexikFormFilterBundle (filtering), HiddenTypeBundle, PhoneNumber bundle
- PHPUnit 11, PHPStan (level 6), PHP-CS-Fixer (Symfony rules)
- No CI, no Makefile

## Dirs

- `src/Controller/` - base controllers (BaseController, BaseLexikFilterController, etc.)
- `src/Filter/` - AbstractFilterType + JSON/date/float query traits
- `src/Type/` - custom form types (PhoneNumber, DateRange, FloatRange, AutoComplete)
- `src/FunctionalTest/Controller/` - abstract test helpers for CRUD
- `src/CompilerPass/`, `src/DependencyInjection/` - DI extension & compiler pass
- `src/AST/` - DateFunction, TextFunction (DQL extensions)
- `src/Twig/` - ArrayExtension
- `src/Repository/` - UnitOfWorkOperation
- `src/ClassFinder/` - KmjClassFinder wrapper

## Skills (.agents/skills/)

- `php-symfony` - Symfony/Doctrine/DI/Messenger mastery
- `sentry-php-sdk` - Sentry setup (error, tracing, logs, metrics, crons, profiling)
- `symfony-merge-up` - cascade merge maintained branches
- `llms-txt-update` - regenerate llms.txt for ai.symfony.com
- `symfony:*` (18 skills) - bootstrap-check, brainstorming, controller-cleanup, daily-workflow, doctrine-migrations, doctrine-relations, doctrine-transactions, effective-context, executing-plans, quality-checks, rate-limiting, runner-selection, strategy-pattern, symfony-cache, symfony-messenger, symfony-scheduler, symfony-voters, twig-components

## Workflow

```bash
composer test         # phpunit
composer phpstan      # phpstan analyse (level 6)
composer cs:check     # php-cs-fixer dry-run
composer cs:fix       # php-cs-fixer auto-fix
```

## Caveats

- No CI pipeline (GitHub Actions, etc.)
- No build scripts or Docker setup
- Mixed Symfony 6/7/8 constraints (wide compat)
- PHPStan baseline `phpstan-baseline.neon` captures 119 pre-existing errors
