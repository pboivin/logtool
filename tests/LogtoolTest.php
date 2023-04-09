<?php

namespace Pboivin\Logtool\Tests;

use Mockery;
use Pboivin\Logtool\LogTool;
use Pboivin\Logtool\Tests\Fixtures\FakeConsole;
use PHPUnit\Framework\TestCase;

class LogtoolTest extends TestCase
{
    public function test_can_initialize_logtool()
    {
        $console = Mockery::mock(FakeConsole::class);

        $logtool = new LogTool($console);

        $this->assertEquals(0, $logtool->getEntries()->count());
    }

    public function test_can_run_logtool()
    {
        /** @var mixed $console */
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();
        $console
            ->shouldReceive('exit')
            ->once()
            ->with(1);

        $logtool = new LogTool($console);
        $logtool->run([]);

        $this->assertEquals('Usage: php logtool.php FILES', $console->getOutput());
    }

    public function test_can_run_logtool_with_files()
    {
        /** @var mixed $console */
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();
        $console->shouldReceive('readCommand')->once();

        $logtool = new LogTool($console);
        $logtool->run([__DIR__ . '/Fixtures/logs/laravel.log']);

        $this->assertEquals('Found 3 entries.', $console->getOutput());
    }

    public function test_can_handle_unknown_command()
    {
        /** @var mixed $console */
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();
        $console
            ->shouldReceive('readCommand')
            ->once()
            ->andReturn(['test']);

        $logtool = new LogTool($console);
        $logtool->run([__DIR__ . '/Fixtures/logs/laravel.log']);

        $this->assertMatchesRegularExpression('/Unknown command/', $console->getOutput());
    }

    public function test_can_handle_help_command()
    {
        /** @var mixed $console */
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();
        $console
            ->shouldReceive('readCommand')
            ->once()
            ->andReturn(['help']);

        $logtool = new LogTool($console);
        $logtool->run([__DIR__ . '/Fixtures/logs/laravel.log']);

        $this->assertMatchesRegularExpression('/Available commands/', $console->getOutput());
    }

    public function test_can_handle_quit_command()
    {
        /** @var mixed $console */
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();
        $console
            ->shouldReceive('readCommand')
            ->once()
            ->andReturn(['quit']);
        $console->shouldReceive('exit')->once();

        $logtool = new LogTool($console);
        $logtool->run([__DIR__ . '/Fixtures/logs/laravel.log']);

        $this->assertNotEmpty($console->getOutput());
    }

    public function test_can_handle_list_command()
    {
        /** @var mixed $console */
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();
        $console
            ->shouldReceive('readCommand')
            ->once()
            ->andReturn(['list', '-a']);

        $logtool = new LogTool($console);
        $logtool->run([__DIR__ . '/Fixtures/logs/laravel.log']);

        $this->assertDoesNotMatchRegularExpression('/Unknown command/', $console->getOutput());
    }

    public function test_can_handle_show_command()
    {
        /** @var mixed $console */
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();
        $console
            ->shouldReceive('readCommand')
            ->once()
            ->andReturn(['show', '1']);

        $logtool = new LogTool($console);
        $logtool->run([__DIR__ . '/Fixtures/logs/laravel.log']);

        $this->assertDoesNotMatchRegularExpression('/Unknown command/', $console->getOutput());
    }

    public function test_can_handle_search_command()
    {
        /** @var mixed $console */
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();
        $console
            ->shouldReceive('readCommand')
            ->once()
            ->andReturn(['search', '2001-01-01']);

        $logtool = new LogTool($console);
        $logtool->run([__DIR__ . '/Fixtures/logs/laravel.log']);

        $this->assertDoesNotMatchRegularExpression('/Unknown command/', $console->getOutput());
        $this->assertMatchesRegularExpression('/Found 1 entry/', $console->getOutput());
    }

    public function test_can_handle_date_command()
    {
        /** @var mixed $console */
        $console = Mockery::mock(FakeConsole::class);
        $console->makePartial();
        $console
            ->shouldReceive('readCommand')
            ->once()
            ->andReturn(['date', '2001-01-03']);

        $logtool = new LogTool($console);
        $logtool->run([__DIR__ . '/Fixtures/logs/laravel.log']);

        $this->assertDoesNotMatchRegularExpression('/Unknown command/', $console->getOutput());
        $this->assertMatchesRegularExpression('/Found 1 entry/', $console->getOutput());
    }
}
