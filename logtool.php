<?php

namespace Pboivin\Logtool;

/**
 * Interactive log file browser.
 *
 * Usage:
 *   php logtool.php path/to/logs/*
 */

define('PAGE_SIZE', 12);

class Entry
{
    protected ?string $date;
    protected ?array $body;

    public function __construct(protected string $header)
    {
        $this->header = trim($header);

        $this->date = substr(preg_replace('/^\[/', '', $header), 0, 10);

        $this->body = [];
    }

    public function header(): ?string
    {
        return $this->header;
    }

    public function date(): ?string
    {
        return $this->date;
    }

    public function body(): ?array
    {
        return $this->body;
    }

    public function addLine(string $line): void
    {
        $this->body[] = rtrim($line);
    }

    public function __toString(): string
    {
        return $this->header() . "\n" . implode("\n", $this->body()) . "\n\n";
    }

    public static function isHeader(string $line): bool
    {
        $line = trim($line);

        return preg_match('/^\d\d\d\d-\d\d-\d\d /', $line) || preg_match('/^\[\d\d\d\d-\d\d-\d\d[T ]?/', $line);
    }
}

class EntryCollection
{
    protected array $keyedEntries = [];
    protected array $indexedEntries = [];

    public function __construct(?array $entries = null)
    {
        if ($entries) {
            $this->keyedEntries = $entries;
            $this->reindex();
        }
    }

    public function count(): int
    {
        return count($this->keyedEntries);
    }

    public function all(): array
    {
        return $this->keyedEntries;
    }

    public function get(int $index): ?Entry
    {
        if ($entry = $this->indexedEntries[$index] ?? false) {
            return $entry;
        }

        return null;
    }

    public function search(string $term): EntryCollection
    {
        $results = [];

        foreach ($this->keyedEntries as $header => $entry) {
            if (preg_match("/$term/i", $header)) {
                $results[$header] = $entry;
            }
        }

        return new EntryCollection($results);
    }

    public function filterDates(string $start, ?string $end = null): EntryCollection
    {
        $results = [];

        foreach ($this->keyedEntries as $header => $entry) {
            if ($end && $entry->date() >= $end) {
                continue;
            }

            if ($entry->date() < $start) {
                continue;
            }

            $results[$header] = $entry;
        }

        return new EntryCollection($results);
    }

    public function importFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $this->importFile($path);
        }

        $this->reindex();
    }

    protected function importFile(string $path): void
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: $path");
        }

        $current = null;

        if ($file = fopen($path, 'r')) {
            while (!feof($file)) {
                $line = fgets($file);

                if (Entry::isHeader($line)) {
                    if ($current) {
                        $this->keyedEntries[$current->header()] = $current;
                    }
                    $current = new Entry($line);
                } else {
                    if ($current) {
                        $current->addLine($line);
                    }
                }
            }
            fclose($file);
        }

        if ($current) {
            $this->keyedEntries[$current->header()] = $current;
        }
    }

    protected function reindex(): void
    {
        $tmpEntries = [];

        foreach ($this->keyedEntries as $entry) {
            $key = $entry->date() . '|' . $entry->header();

            $tmpEntries[$key] = $entry;
        }

        ksort($tmpEntries);

        $this->keyedEntries = $tmpEntries;

        $this->indexedEntries = array_values($this->keyedEntries);
    }
}

class Printer
{
    public function __construct(
        protected EntryCollection $collection,
        protected Console $console,
    ) {
    }

    public function show(int $position): void
    {
        if ($entry = $this->collection->get($position - 1)) {
            $this->console->echo("\n");
            $this->console->echo("====================\n");
            $this->console->echo(" Entry {$position}\n");
            $this->console->echo("====================\n");
            $this->console->echo("{$entry}\n\n");
        } else {
            $this->console->echo("\nNot found\n\n");
        }
    }

    public function list(): void
    {
        $this->console->echo("\n");

        $n = 1;

        foreach ($this->collection->all() as $entry) {
            $this->console->echo("{$n}. {$entry->header()}\n\n");

            $n++;
        }
    }

    public function paginate(?int $pageSize = null): void
    {
        $pageSize = $pageSize ?: PAGE_SIZE;

        $this->console->echo("\n");

        $n = 1;

        foreach ($this->collection->all() as $entry) {
            $this->console->echo("{$n}. {$entry->header()}\n\n");

            if ($n % $pageSize === 0 || $n === $this->collection->count()) {
                $next = true;

                while ($next) {
                    $input = $this->console->input("Type number to show entry, 'Enter' for next page, 'q' to go back ... ");

                    $this->console->echo("\n");

                    if ($input == 'q') {
                        $next = false;
                        return;
                    } elseif ($input != '') {
                        $this->show((int) $input);
                    } else {
                        break;
                    }
                }
            }

            $n++;
        }
    }
}

class Console
{
    public function echo(string $s): void
    {
        echo $s;
    }

    public function input(string $prompt): string
    {
        return trim(readline($prompt));
    }

    public function readCommand(): array
    {
        $input = $this->input('logtool> ');

        readline_add_history($input);

        return explode(' ', $input);
    }

    public function exit(int $code): void
    {
        exit($code);
    }

    public function isInteractive(): bool
    {
        return true;
    }
}

class LogTool
{
    protected Console $console;

    protected EntryCollection $originalEntries;

    protected ?EntryCollection $filteredEntries = null;

    protected array $commands = [
        'l' => ['alias' => 'list'],
        's' => ['alias' => 'show'],
        '/' => ['alias' => 'search'],
        'd' => ['alias' => 'date'],
        'e' => ['alias' => 'export'],
        'r' => ['alias' => 'reset'],
        'h' => ['alias' => 'help'],
        'q' => ['alias' => 'quit'],
        'list'   => ['description' => 'l, list [-a]             List entries [-a : list all, default is paginated]'],
        'show'   => ['description' => 's, show [number]         Show entry'],
        'search' => ['description' => '/, search [term]         Search entries'],
        'date'   => ['description' => 'd, date [start] [end]    Filter by date'],
        'export' => ['description' => 'e, export [-l]           Export entries [-l : export list, default includes all content]'],
        'reset'  => ['description' => 'r, reset                 Reset original entries'],
        'help'   => ['description' => 'h, help'],
        'quit'   => ['description' => 'q, quit'],
    ];

    public function __construct(?Console $console = null)
    {
        $this->console = $console ?? new Console();

        $this->originalEntries = new EntryCollection();
    }

    public function getEntries(): EntryCollection
    {
        if ($this->filteredEntries) {
            return $this->filteredEntries;
        }

        return $this->originalEntries;
    }

    public function run(array $files): void
    {
        if (empty($files)) {
            $this->console->echo("Usage: php logtool.php FILES\n");
            $this->console->exit(1);
            return;
        }

        $this->originalEntries->importFiles($files);

        $this->interactive();
    }

    protected function interactive(): void
    {
        $this->showCount();

        while (1) {
            $arguments = $this->console->readCommand();

            if (!$this->handle($arguments)) {
                $this->console->echo("Unknown command. Type 'help' to see available commands.\n\n");
            }

            if (!$this->console->isInteractive()) {
                break;
            }
        }
    }

    protected function showCount()
    {
        $count = $this->getEntries()->count();

        $noun = $count === 1 ? 'entry' : 'entries';

        $this->console->echo("\nFound $count $noun.\n\n");
    }

    protected function handle(array $arguments): bool
    {
        $command = trim(array_shift($arguments));

        if (empty($command)) {
            return true;
        }

        if ($config = $this->commands[$command] ?? false) {
            if ($alias = $config['alias'] ?? false) {
                $command = $alias;
            }

            $handler = "{$command}Command";

            $this->$handler($arguments);

            return true;
        }

        return false;
    }

    protected function helpCommand(array $arguments = []): void
    {
        $this->console->echo("\nAvailable commands:\n");

        foreach ($this->commands as $command) {
            if ($desc = $command['description'] ?? false) {
                $this->console->echo("  $desc\n");
            }
        }

        $this->console->echo("\n");
    }

    protected function quitCommand(array $arguments = []): void
    {
        $this->console->exit(0);
    }

    protected function listCommand(array $arguments = []): void
    {
        if ($arguments[0] ?? false === '-a') {
            (new Printer($this->getEntries(), $this->console))->list();
        } else {
            (new Printer($this->getEntries(), $this->console))->paginate();
        }
    }

    protected function showCommand(array $arguments = []): void
    {
        if ($arguments[0] ?? false) {
            (new Printer($this->getEntries(), $this->console))->show((int) $arguments[0]);
        } else {
            $this->helpCommand([]);
        }
    }

    protected function searchCommand(array $arguments = []): void
    {
        if ($arguments[0] ?? false) {
            $results = $this->getEntries()->search($arguments[0]);

            if ($results->count() > 0) {
                $this->filteredEntries = $results;

                $this->showCount();
            } else {
                $this->console->echo("\nNo results\n\n");
            }
        } else {
            $this->helpCommand([]);
        }
    }

    protected function dateCommand(array $arguments = []): void
    {
        $start = $arguments[0] ?? null;
        $end = $arguments[1] ?? null;

        if ($start) {
            $results = $this->getEntries()->filterDates($start, $end);

            if ($results->count() > 0) {
                $this->filteredEntries = $results;

                $this->showCount();
            } else {
                $this->console->echo("\nNo results\n\n");
            }
        } else {
            $this->helpCommand([]);
        }
    }

    protected function exportCommand(array $arguments = []): void
    {
        $list = $arguments[0] ?? false === '-l';
        $output = [];

        foreach ($this->getEntries()->all() as $entry) {
            if ($list) {
                $output[] = (string) $entry->header();
            } else {
                $output[] = (string) $entry;
            }
        }

        $time = time();

        file_put_contents("./logtool-export-$time.txt", implode('', $output));

        $this->console->echo("\nDone\n\n");

        $this->quitCommand();
    }

    protected function resetCommand(array $arguments = []): void
    {
        $this->filteredEntries = null;

        $this->showCount();
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    array_shift($argv);

    (new LogTool())->run($argv);
}
