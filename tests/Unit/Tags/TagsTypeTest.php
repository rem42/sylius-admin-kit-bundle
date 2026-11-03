<?php

declare(strict_types=1);

namespace Tests\Rem42\SyliusAdminKit\Unit\Tags;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rem42\SyliusAdminKit\Tags\Form\TagsType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * @internal
 */
final class TagsTypeTest extends TestCase
{
    private FormFactoryInterface $factory;

    protected function setUp(): void
    {
        $this->factory = Forms::createFormFactory();
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('submissions')]
    public function testSubmit(string $submitted, array $expected): void
    {
        $form = $this->factory->create(TagsType::class);
        $form->submit($submitted);

        self::assertTrue($form->isSynchronized());
        self::assertSame($expected, $form->getData());
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function submissions(): iterable
    {
        yield 'JSON list of the controller' => ['["a","b"]', ['a', 'b']];
        yield 'text split by the default pattern' => ["a, b;c\nd", ['a', 'b', 'c', 'd']];
        yield 'trimmed, without empty entries' => ['[" a ","",  "b"]', ['a', 'b']];
        yield 'case-insensitive duplicates removed' => ['["Foo","foo","bar"]', ['Foo', 'bar']];
        yield 'unicode' => ['["éTé","ÉTÉ"]', ['éTé']];
        yield 'empty' => ['', []];
        yield 'invalid JSON is split' => ['[a, b', ['[a', 'b']];
    }

    public function testDuplicatesAreKeptWhenNotUnique(): void
    {
        $form = $this->factory->create(TagsType::class, null, ['unique' => false]);
        $form->submit('["a","A","a"]');

        self::assertSame(['a', 'A', 'a'], $form->getData());
    }

    public function testCustomSplitPattern(): void
    {
        $form = $this->factory->create(TagsType::class, null, ['split_pattern' => '[|/]+']);
        $form->submit('a|b/c, d');

        self::assertSame(['a', 'b', 'c, d'], $form->getData());
    }

    public function testView(): void
    {
        $view = $this->factory->create(TagsType::class, ['a', ' b', 'a', 'é/x'], [
            'tag_pattern' => '[a-z]+',
            'input_type' => 'email',
        ])->createView();

        self::assertSame('["a","b","é/x"]', $view->vars['value']);
        self::assertSame(['a', 'b', 'é/x'], $view->vars['tags']);
        self::assertSame('[a-z]+', $view->vars['tag_pattern']);
        self::assertSame('[,;\n]+', $view->vars['split_pattern']);
        self::assertSame('email', $view->vars['input_type']);
        self::assertTrue($view->vars['unique']);
        self::assertSame('rem42_sylius_admin_kit.ui.tags.placeholder', $view->vars['entry_placeholder']);
        self::assertIsArray($view->vars['block_prefixes']);
        self::assertContains('rem42_admin_kit_tags', $view->vars['block_prefixes']);
    }

    public function testInvalidSplitPatternIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->factory->create(TagsType::class, null, ['split_pattern' => '[a-']);
    }

    public function testEmptySplitPatternIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->factory->create(TagsType::class, null, ['split_pattern' => '']);
    }
}
