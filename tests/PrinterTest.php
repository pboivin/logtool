<?php

namespace Pboivin\Logtool\Tests;

use Mockery;
use Pboivin\Logtool\Entry;
use Pboivin\Logtool\EntryCollection;
use Pboivin\Logtool\Printer;
use Pboivin\Logtool\Tests\Fixtures\FakeConsole;
use PHPUnit\Framework\TestCase;

class PrinterTest extends TestCase
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

    private function getCollection(): EntryCollection
    {
        return new EntryCollection($this->getEntries());
    }

    public function test_can_show_one_entry()
    {
        $collection = $this->getCollection();

        /** @var mixed $console*/
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();

        $printer = new Printer($collection, $console);

        $printer->show(1);

        $this->assertEquals(
            trim('
====================
 Entry 1
====================
[2001-01-01] Test1
One
Two
Three'),
            $console->getOutput()
        );
    }

    public function test_handle_entry_not_found()
    {
        $collection = $this->getCollection();

        /** @var mixed $console*/
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();

        $printer = new Printer($collection, $console);

        $printer->show(999);

        $this->assertEquals('Not found', $console->getOutput()
        );
    }

    public function test_can_list_entries()
    {
        $collection = $this->getCollection();

        /** @var mixed $console*/
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();

        $printer = new Printer($collection, $console);

        $printer->list();

        $this->assertEquals(
            trim('
1. [2001-01-01] Test1

2. [2001-01-02] Test2

3. [2001-01-03] Test3'),
            $console->getOutput()
        );
    }

    public function test_can_paginate_entries()
    {
        $collection = $this->getCollection();

        /** @var mixed $console*/
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();
        $console->shouldReceive('input')->once();

        $printer = new Printer($collection, $console);

        $printer->paginate(2);

        $this->assertEquals(
            trim('
1. [2001-01-01] Test1

2. [2001-01-02] Test2


3. [2001-01-03] Test3'),
            $console->getOutput()
        );
    }
}
