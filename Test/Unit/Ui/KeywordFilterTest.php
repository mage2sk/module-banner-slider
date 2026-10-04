<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Ui;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Panth\BannerSlider\Ui\DataProvider\KeywordFilter;
use PHPUnit\Framework\TestCase;

class KeywordFilterTest extends TestCase
{
    private array $where = [];

    private function collection(): AbstractDb
    {
        $this->where = [];
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('quoteInto')->willReturnCallback(
            static fn($text, $value) => str_replace('?', "'" . $value . "'", $text)
        );
        $select = $this->createStub(Select::class);
        $select->method('where')->willReturnCallback(
            function ($condition) use (&$select) {
                $this->where[] = $condition;
                return $select;
            }
        );
        $collection = $this->createStub(AbstractDb::class);
        $collection->method('getConnection')->willReturn($connection);
        $collection->method('getSelect')->willReturn($select);
        return $collection;
    }

    private function filter(?string $value): Filter
    {
        $filter = new Filter();
        $filter->setField('fulltext');
        $filter->setValue($value);
        return $filter;
    }

    public function testMatchesAnyConfiguredColumnWithLike(): void
    {
        (new KeywordFilter(['title', 'identifier']))->apply($this->collection(), $this->filter('  Home Hero '));

        $this->assertSame(
            ["main_table.title LIKE '%Home Hero%' OR main_table.identifier LIKE '%Home Hero%'"],
            $this->where
        );
    }

    public function testLikeWildcardsInTheKeywordAreEscaped(): void
    {
        (new KeywordFilter(['title']))->apply($this->collection(), $this->filter('50%_off'));

        $this->assertSame(["main_table.title LIKE '%50\\%\\_off%'"], $this->where);
    }

    public function testEmptyKeywordAddsNoCondition(): void
    {
        (new KeywordFilter(['title']))->apply($this->collection(), $this->filter('   '));
        (new KeywordFilter(['title']))->apply($this->collection(), $this->filter(null));

        $this->assertSame([], $this->where);
    }

    public function testUnsafeColumnNamesAreIgnored(): void
    {
        (new KeywordFilter(['title; DROP TABLE x', 'Title', 'alt_text']))->apply($this->collection(), $this->filter('a'));

        $this->assertSame(["main_table.alt_text LIKE '%a%'"], $this->where);
    }

    public function testNoUsableColumnsAddsNoCondition(): void
    {
        (new KeywordFilter(['bad-name']))->apply($this->collection(), $this->filter('a'));

        $this->assertSame([], $this->where);
    }

    public function testNonDatabaseCollectionIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new KeywordFilter())->apply($this->createStub(Collection::class), $this->filter('a'));
    }
}
