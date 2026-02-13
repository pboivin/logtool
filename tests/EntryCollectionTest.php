<?php

namespace Pboivin\Logtool\Tests;

use Pboivin\Logtool\Entry;
use Pboivin\Logtool\EntryCollection;
use PHPUnit\Framework\TestCase;

class EntryCollectionTest extends TestCase
{
    private function getEntries(): array
    {
        return array_map(
            function ($i) {
                $entry = new Entry("[2001-01-0$i] Test$i\n\n");
                $entry->addLine("One\n\n");
                $entry->addLine("Two\n\n");
                $entry->addLine("Three\n\n");
                return $entry;
            },
            [1, 2, 3]
        );
    }

    public function test_can_initialize_empty_collection()
    {
        $collection = new EntryCollection();

        $this->assertEquals(0, $collection->count());
    }

    public function test_can_initialize_collection_with_entries()
    {
        $collection = new EntryCollection($this->getEntries());

        $this->assertEquals(3, $collection->count());
        $this->assertEquals(3, count($collection->all()));
    }

    public function test_entries_are_not_sorted_by_default()
    {
        $collection = new EntryCollection(array_reverse($this->getEntries()));

        $this->assertEquals(3, $collection->count());
        $this->assertEquals('2001-01-03', $collection->get(0)->date());
        $this->assertEquals('2001-01-02', $collection->get(1)->date());
        $this->assertEquals('2001-01-01', $collection->get(2)->date());
    }

    public function test_entries_can_be_sorted()
    {
        $collection = new EntryCollection(array_reverse($this->getEntries()));

        $collection->sort();

        $this->assertEquals(3, $collection->count());
        $this->assertEquals('2001-01-01', $collection->get(0)->date());
        $this->assertEquals('2001-01-02', $collection->get(1)->date());
        $this->assertEquals('2001-01-03', $collection->get(2)->date());
    }

    public function test_can_get_one_entry()
    {
        $collection = new EntryCollection($this->getEntries());

        $entry = $collection->get(0);

        $this->assertTrue($entry instanceof Entry);
        $this->assertEquals('2001-01-01', $entry->date());
    }

    public function test_can_search_entries()
    {
        $collection = new EntryCollection($this->getEntries());

        $result = $collection->search('Test1');

        $this->assertEquals(1, $result->count());
        $this->assertMatchesRegularExpression('/Test1/', $result->get(0)->header());
    }

    public function test_can_filter_date_start()
    {
        $collection = new EntryCollection($this->getEntries());

        $result = $collection->filterDates('2001-01-02');

        $this->assertEquals(2, $result->count());
        $this->assertMatchesRegularExpression('/Test2/', $result->get(0)->header());
        $this->assertMatchesRegularExpression('/Test3/', $result->get(1)->header());
    }

    public function test_can_filter_date_end()
    {
        $collection = new EntryCollection($this->getEntries());

        $result = $collection->filterDates('2001-01-02', '2001-01-03');

        $this->assertEquals(1, $result->count());
        $this->assertMatchesRegularExpression('/Test2/', $result->get(0)->header());
    }

    public function test_can_import_files()
    {
        $collection = new EntryCollection();

        $collection->importFiles([__DIR__ . '/Fixtures/logs/laravel.log']);
        $this->assertEquals(3, $collection->count());

        $collection->importFiles([__DIR__ . '/Fixtures/logs/symfony.log']);
        $this->assertEquals(6, $collection->count());

        $collection->importFiles([__DIR__ . '/Fixtures/logs/yii.log']);
        $this->assertEquals(9, $collection->count());
    }

    public function test_throws_file_not_found_exception()
    {
        $this->expectExceptionMessageMatches('/File not found: this_file_does_not_exist/');

        $collection = new EntryCollection();

        $collection->importFiles(['this_file_does_not_exist']);
    }
}
