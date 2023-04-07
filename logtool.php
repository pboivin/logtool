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
    protected ?string $header;
    protected ?string $date;
    protected ?array $body;

    public function __construct(string $header)
    {
        $this->header = $header;

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
        $this->body[] = $line;
    }

    public function __toString(): string
    {
        return $this->header() . implode('', $this->body()) . "\n";
    }
}

class EntryCollection
{
    protected array $entries = [];
    protected array $indexedEntries = [];

    public function __construct(?array $entries = null)
    {
        if ($entries) {
            $this->entries = $entries;
            $this->reindex();
        }
    }

    public function count(): int
    {
        return count($this->entries);
    }

    public function all(): array
    {
        return $this->entries;
    }

    public function list(bool $paginated = true): void
    {
        $n = 1;

        echo "\n";

        foreach ($this->entries as $header => $entry) {
            echo "{$n}. $header\n";

            if ($paginated && ($n % PAGE_SIZE === 0 || $n === count($this->entries))) {
                $next = true;

                while ($next) {
                    $input = readline("Type number to show entry, 'Enter' for next page, 'q' to go back ... ");
                    $input = trim($input);

                    echo "\n";

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

    public function show(int $position): void
    {
        if ($entry = $this->indexedEntries[$position - 1] ?? false) {
            echo "\n";
            echo "====================\n";
            echo " Entry {$position}\n";
            echo "====================\n";
            echo "{$entry}\n\n";
        } else {
            echo "\nNot found\n\n";
        }
    }

    public function search(string $term): EntryCollection
    {
        $results = [];

        foreach ($this->entries as $header => $entry) {
            if (preg_match("/$term/i", $header)) {
                $results[$header] = $entry;
            }
        }

        return new EntryCollection($results);
    }

    public function filterDates(string $start, ?string $end = null): EntryCollection
    {
        $results = [];

        foreach ($this->entries as $header => $entry) {
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

    public function importFile(string $path): void
    {
        $current = null;

        if ($file = fopen($path, 'r')) {
            while (!feof($file)) {
                $line = fgets($file);

                if (
                    preg_match('/^\d\d\d\d-\d\d-\d\d /', $line)
                    || preg_match('/^\[\d\d\d\d-\d\d-\d\d[T ]?/', $line)
                ) {
                    if ($current) {
                        $this->entries[$current->header()] = $current;
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

        $this->reindex();
    }

    public function reindex(): void
    {
        ksort($this->entries);

        $this->indexedEntries = array_values($this->entries);
    }
}

class LogTool
{
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
        'list' =>   ['description' => 'l, list [-a]             List entries [-a : list all, default is paginated]'],
        'show' =>   ['description' => 's, show [number]         Show entry'],
        'search' => ['description' => '/, search [term]         Search entries'],
        'date' =>   ['description' => 'd, date [start] [end]    Filter by date'],
        'export' => ['description' => 'e, export [-l]           Export entries [-l : export list, default includes all content]'],
        'reset' =>  ['description' => 'r, reset                 Reset original entries'],
        'help' =>   ['description' => 'h, help'],
        'quit' =>   ['description' => 'q, quit'],
    ];

    public function __construct()
    {
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
            echo "Usage: php logtool.php FILES\n";
            exit(1);
        }

        foreach ($files as $file) {
            $this->originalEntries->importFile($file);
        }

        $this->interactive();
    }

    protected function interactive(): void
    {
        $this->showCount();

        while (1) {
            $input = readline('logtool> ');
            $arguments = explode(' ', $input);

            if (!$this->handle($arguments)) {
                echo "Unknown command. Type 'help' to see available commands.\n\n";
            }

            readline_add_history($input);
        }
    }

    protected function showCount()
    {
        $count = $this->getEntries()->count();

        echo "\nFound $count entries.\n\n";
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
        echo "\nAvailable commands:\n";

        foreach ($this->commands as $command) {
            if ($desc = $command['description'] ?? false) {
                echo "  $desc\n";
            }
        }

        echo "\n";
    }

    protected function quitCommand(array $arguments = []): void
    {
        exit(0);
    }

    protected function listCommand(array $arguments = []): void
    {
        if ($arguments[0] ?? false === '-a') {
            $this->getEntries()->list(paginated: false);
        } else {
            $this->getEntries()->list(paginated: true);
        }
    }

    protected function showCommand(array $arguments = []): void
    {
        if ($arguments[0] ?? false) {
            $this->getEntries()->show((int) $arguments[0]);
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
                echo "\nNo results\n\n";
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
                echo "\nNo results\n\n";
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

        echo "\nDone\n\n";

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
