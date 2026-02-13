<?php

namespace Pboivin\Logtool\Tests;

use Pboivin\Logtool\Entry;
use PHPUnit\Framework\TestCase;

class EntryTest extends TestCase
{
    public function test_can_initialize_entry()
    {
        $entry = new Entry("[2001-01-01] Test\n\n");
        $entry->addLine("One\n\n");
        $entry->addLine("Two\n\n");
        $entry->addLine("Three\n\n");

        $this->assertEquals('[2001-01-01] Test', $entry->header());
        $this->assertEquals('2001-01-01', $entry->date());
        $this->assertEquals(3, count($entry->body()));
        $this->assertEquals("[2001-01-01] Test\nOne\nTwo\nThree\n\n", (string) $entry);
    }

    public function test_preserves_line_indentation()
    {
        $entry = new Entry("[2001-01-01] Test\n\n");
        $entry->addLine("    One\n\n");
        $entry->addLine("    Two\n\n");
        $entry->addLine("    Three\n\n");

        $this->assertEquals('[2001-01-01] Test', $entry->header());
        $this->assertEquals('2001-01-01', $entry->date());
        $this->assertEquals(3, count($entry->body()));
        $this->assertEquals("[2001-01-01] Test\n    One\n    Two\n    Three\n\n", (string) $entry);
    }
}
