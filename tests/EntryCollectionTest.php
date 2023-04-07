<?php

namespace Pboivin\Logtool\Tests;

use Pboivin\Logtool\Entry;
use Pboivin\Logtool\EntryCollection;
use PHPUnit\Framework\TestCase;

class EntryCollectionTest extends TestCase
{
    private function getEntries(): array
    {
        return array_map(function ($i) {
            $entry = new Entry("[2001-01-0$i] Test$i\n\n");
            $entry->addLine("One\n\n");
            $entry->addLine("Two\n\n");
            $entry->addLine("Three\n\n");
        }, [1, 2, 3]);
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
    }
}
