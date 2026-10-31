<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\ActiveFilters;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rem42\SyliusAdminKit\ActiveFilters\CriterionFormatter;
use Sylius\Component\Grid\Definition\Filter;
use Tests\Rem42\SyliusAdminKit\Unit\ActiveFilters\Fixture\EchoTranslator;
use Tests\Rem42\SyliusAdminKit\Unit\QuickFilter\Fixture\Status;
use Tests\Rem42\SyliusAdminKit\Unit\QuickFilter\Fixture\TranslatableStatus;

/**
 * @internal
 */
final class CriterionFormatterTest extends TestCase
{
    /**
     * @param array<string, mixed> $formOptions
     * @param array<string, mixed> $options
     */
    #[DataProvider('criteria')]
    public function testFormat(string $type, mixed $criterion, ?string $expected, array $formOptions = [], array $options = []): void
    {
        $filter = Filter::fromNameAndType('field', $type);
        $filter->setFormOptions($formOptions);
        $filter->setOptions($options);

        self::assertSame($expected, (new CriterionFormatter(new EchoTranslator()))->format($filter, $criterion, 'fr'));
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed, 2: ?string, 3?: array<string, mixed>, 4?: array<string, mixed>}>
     */
    public static function criteria(): iterable
    {
        yield 'string, scalar' => ['string', ' foo ', 'foo'];
        yield 'string, empty' => ['string', '  ', null];
        yield 'string, value without type' => ['string', ['value' => 'foo'], 'foo'];
        yield 'string, value with type' => ['string', ['type' => 'starts_with', 'value' => 'foo'], 'sylius.ui.starts_with "foo"'];
        yield 'string, empty value' => ['string', ['type' => 'contains', 'value' => ''], null];
        yield 'string, type without value' => ['string', ['type' => 'not_empty', 'value' => ''], 'sylius.ui.not_empty'];

        yield 'boolean, true' => ['boolean', 'true', 'sylius.ui.yes_label'];
        yield 'boolean, false' => ['boolean', 'false', 'sylius.ui.no_label'];
        yield 'exists, 0' => ['exists', '0', 'sylius.ui.no_label'];
        yield 'exists, 1' => ['exists', '1', 'sylius.ui.yes_label'];

        yield 'enum, translated case name' => ['enum', 'done', 'Done', ['class' => Status::class]];
        yield 'enum, choice_label callable' => ['enum', 'done', 'label:done', ['class' => Status::class, 'choice_label' => static fn (Status $s): string => 'label:' . $s->value]];
        yield 'enum, translatable' => ['enum', 'new', 'translatable:new', ['class' => TranslatableStatus::class]];
        yield 'enum, unknown case' => ['enum', 'bogus', 'bogus', ['class' => Status::class]];
        yield 'enum, no class' => ['enum', 'done', 'done'];

        yield 'select, known choice' => ['select', '2', 'app.two', ['choices' => ['app.one' => 1, 'app.two' => 2]]];
        yield 'select, unknown choice' => ['select', '3', '3', ['choices' => ['app.one' => 1]]];

        yield 'entity, without doctrine' => ['entity', '42', '42', ['class' => \stdClass::class]];

        yield 'date, both bounds' => ['date', ['from' => ['date' => '2026-01-02', 'time' => ''], 'to' => ['date' => '2026-03-04', 'time' => '']], 'rem42_sylius_admin_kit.ui.active_filters.range.between{%from%=02/01/2026,%to%=04/03/2026}'];
        yield 'date, from with time' => ['date', ['from' => ['date' => '2026-01-02', 'time' => '13:37']], 'rem42_sylius_admin_kit.ui.active_filters.range.from{%from%=02/01/2026 13:37}'];
        yield 'date, to only' => ['date', ['from' => ['date' => '', 'time' => ''], 'to' => ['date' => '2026-03-04']], 'rem42_sylius_admin_kit.ui.active_filters.range.to{%to%=04/03/2026}'];
        yield 'date, no bound' => ['date', ['from' => ['date' => '', 'time' => ''], 'to' => ['date' => '', 'time' => '']], null];

        yield 'numeric range, inclusive by default' => ['numeric_range', ['greaterThan' => '10', 'lessThan' => '20'], '≥ 10 ≤ 20'];
        yield 'numeric range, exclusive' => ['numeric_range', ['greaterThan' => '10'], '> 10', [], ['inclusive_from' => false]];
        yield 'money, exclusive with currency' => ['money', ['greaterThan' => '', 'lessThan' => '20', 'currency' => 'EUR'], '< 20 EUR'];
        yield 'money, empty' => ['money', ['greaterThan' => '', 'lessThan' => '', 'currency' => ''], null];

        yield 'unknown type, scalar' => ['custom', 'foo', 'foo'];
        yield 'unknown type, nested array' => ['custom', ['a' => 'x', 'b' => ['c' => 'y', 'd' => '']], 'x, y'];
    }
}
