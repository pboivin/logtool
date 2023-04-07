# logtool

This is a work in progress.

## Usage

```sh
php logtool.php path/to/logs/*
```

This will read all log entries in memory and bring up an interactive prompt:

```sh
$ php logtool.php '/home/mysite-com/storage/logs/laravel.log' 

Found 42 entries.

logtool> h

Available commands:
  l, list [-a]             List entries [-a : list all, default is paginated]
  s, show [number]         Show entry
  /, search [term]         Search entries
  d, date [start] [end]    Filter by date
  e, export [-l]           Export entries [-l : export list, default includes all content]
  r, reset                 Reset original entries
  h, help
  q, quit

logtool>

```

## Caveats

This assumes your log entries start with the date in one of the following formats:
- YYYY-MM-DD ...
- [YYYY-MM-DD...] ...
