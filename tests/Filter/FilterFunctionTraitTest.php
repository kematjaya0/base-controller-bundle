<?php

namespace Kematjaya\BaseControllerBundle\Tests\Filter;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use Kematjaya\BaseControllerBundle\Filter\FilterFunctionTrait;
use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;

/**
 * QueryInterface::getQueryBuilder() declares no return type, so the fallback in
 * getFilterExpr() only needs something that answers expr(). Doctrine moved
 * QueryBuilder from Doctrine\ORM\Query\QueryBuilder to Doctrine\ORM\QueryBuilder
 * in ORM 3, so a minimal double keeps this test independent of that rename and
 * lets it assert how often expr() was actually reached.
 */
class FakeQueryBuilder
{
    /** @var int */
    public $exprCalls = 0;

    /** @var Expr */
    private $expr;

    public function __construct(Expr $expr)
    {
        $this->expr = $expr;
    }

    public function expr(): Expr
    {
        ++$this->exprCalls;

        return $this->expr;
    }
}

/**
 * Spiriit's QueryInterface declares five abstract methods and no return types,
 * identically on v10 and v12. PHPUnit cannot generate a usable mock for it, so
 * the query is stubbed by hand - which also lets the tests count how often each
 * accessor is actually reached.
 */
class RecordingFilterQuery implements QueryInterface
{
    /** @var int */
    public $queryBuilderCalls = 0;

    /** @var string|null */
    public $condition;

    /** @var array */
    public $conditionParameters = [];

    /** @var FakeQueryBuilder|null */
    private $queryBuilder;

    public function __construct(?FakeQueryBuilder $queryBuilder = null)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function getQueryBuilder()
    {
        ++$this->queryBuilderCalls;

        return $this->queryBuilder;
    }

    public function getEventPartName()
    {
        return 'filter';
    }

    public function createCondition($expression, array $parameters = [])
    {
        $this->condition = $expression;
        $this->conditionParameters = $parameters;

        return $expression;
    }

    public function getRootAlias()
    {
        return 'o';
    }

    public function hasJoinAlias($joinAlias)
    {
        return false;
    }
}

/**
 * The Spiriit 10/11 shape, where getExpr() is still exposed.
 */
class LegacyFilterQuery extends RecordingFilterQuery
{
    /** @var Expr */
    private $expr;

    public function __construct(Expr $expr, ?FakeQueryBuilder $queryBuilder = null)
    {
        parent::__construct($queryBuilder);

        $this->expr = $expr;
    }

    public function getExpr()
    {
        return $this->expr;
    }
}

final class FilterHarness
{
    use FilterFunctionTrait;

    public function expr(QueryInterface $filterQuery): Expr
    {
        return $this->getFilterExpr($filterQuery);
    }

    public function json(): callable
    {
        return $this->JSONQuery();
    }

    public function dateRange(): callable
    {
        return $this->dateRangeQuery();
    }

    public function floatRange(): callable
    {
        return $this->floatRangeQuery();
    }
}

/**
 * Spiriit moved getExpr() off QueryInterface in v12, so getFilterExpr() has to
 * probe for it. Both branches are covered here: a query that exposes getExpr()
 * must be used directly, and one that does not must fall back to the ORM
 * expression builder reached through getQueryBuilder().
 */
final class FilterFunctionTraitTest extends TestCase
{
    public function testGetFilterExprUsesGetExprWhenTheQueryExposesIt(): void
    {
        $expected = new Expr();
        // A different instance, so falling through to the query builder shows.
        $queryBuilder = new FakeQueryBuilder(new Expr());
        $query = new LegacyFilterQuery($expected, $queryBuilder);

        $this->assertSame($expected, (new FilterHarness())->expr($query));
        $this->assertSame(0, $query->queryBuilderCalls, 'getQueryBuilder() must not be used');
        $this->assertSame(0, $queryBuilder->exprCalls);
    }

    public function testGetFilterExprFallsBackToQueryBuilderWhenGetExprIsAbsent(): void
    {
        $expr = new Expr();
        $queryBuilder = new FakeQueryBuilder($expr);
        $query = new RecordingFilterQuery($queryBuilder);

        $this->assertSame($expr, (new FilterHarness())->expr($query));
        $this->assertSame(1, $query->queryBuilderCalls);
        $this->assertSame(1, $queryBuilder->exprCalls);
    }

    public function testQueryInterfaceItselfDoesNotDeclareGetExpr(): void
    {
        // Guards the premise of the fallback: if a future Spiriit puts getExpr()
        // back on the interface, the method_exists() branch is dead code and
        // this assertion is what tells us to simplify.
        $this->assertFalse(
            method_exists(QueryInterface::class, 'getExpr'),
            'Spiriit QueryInterface now declares getExpr(); the fallback can be revisited'
        );
    }

    /**
     * @dataProvider emptyValueProvider
     */
    public function testHelpersReturnNullWhenNothingIsFiltered(array $values, string $helper): void
    {
        // "no condition" must be null, which is what Spiriit 11/12 expect, and
        // no condition object may be built along the way.
        $query = new RecordingFilterQuery(new FakeQueryBuilder(new Expr()));

        $result = (new FilterHarness())->{$helper}()($query, 't.foo', $values);

        $this->assertNull($result);
        $this->assertNull($query->condition, $helper.' created a condition for an empty value');
    }

    /**
     * @return array<string, array{0: array, 1: string}>
     */
    public function emptyValueProvider(): array
    {
        return [
            'json, null value' => [['value' => null], 'json'],
            'json, empty string' => [['value' => ''], 'json'],
            'date range, no value' => [['value' => null], 'dateRange'],
            'date range, both bounds null' => [
                ['value' => ['from' => null, 'to' => null]],
                'dateRange',
            ],
            'float range, no value' => [['value' => null], 'floatRange'],
            'float range, both bounds null' => [
                ['value' => ['from' => null, 'to' => null]],
                'floatRange',
            ],
        ];
    }

    /**
     * dateRangeQuery() calls setTime() on the upper bound, which mutates the
     * DateTime instance owned by the form/filter values. The implementation
     * clones first; this locks that in, because dropping the clone silently
     * rewrites the caller's object to 23:59:59.
     */
    public function testDateRangeDoesNotMutateTheCallersDateTime(): void
    {
        $from = new \DateTime('2024-01-15 08:30:00');
        $to = new \DateTime('2024-01-20 09:00:00');

        $query = new RecordingFilterQuery(new FakeQueryBuilder(new Expr()));

        $result = (new FilterHarness())->dateRange()(
            $query,
            't.createdAt',
            ['value' => ['from' => $from, 'to' => $to]]
        );

        $this->assertIsString($result);
        $this->assertSame('2024-01-20 09:00:00', $to->format('Y-m-d H:i:s'), 'caller DateTime was mutated');
        $this->assertSame('2024-01-15 08:30:00', $from->format('Y-m-d H:i:s'));
        $this->assertStringContainsString('2024-01-20 23:59:59', $result);
        $this->assertStringContainsString('2024-01-15 08:30:00', $result);
    }

    public function testJsonQueryBuildsALikeConditionThroughTheResolvedExpr(): void
    {
        $query = new RecordingFilterQuery(new FakeQueryBuilder(new Expr()));

        $result = (new FilterHarness())->json()($query, 't.name', ['value' => 'ali']);

        // Expr::like() hands back a Comparison node, which is passed straight
        // through to createCondition() without being cast to a string. Its
        // properties are protected on ORM 3, so the rendered form is asserted.
        $this->assertInstanceOf(Comparison::class, $result);
        $this->assertSame("TEXT(t.name) LIKE '%ali%'", (string) $result);
        $this->assertSame($result, $query->condition);
    }

    public function testFloatRangeStripsCurrencyAndCommaSeparators(): void
    {
        $query = new RecordingFilterQuery(new FakeQueryBuilder(new Expr()));

        $result = (new FilterHarness())->floatRange()(
            $query,
            't.price',
            ['value' => ['from' => 'Rp. 1,000', 'to' => '2,500']]
        );

        $this->assertIsString($result);
        $this->assertStringContainsString('1000', $result);
        $this->assertStringContainsString('2500', $result);
        $this->assertStringNotContainsString('Rp.', $result);
        $this->assertStringNotContainsString(',', $result);
    }
}
