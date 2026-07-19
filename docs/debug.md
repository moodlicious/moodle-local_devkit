# Debug

A fluent `debug()` utility for development. Provides quick ways to inspect variables, measure performance, and inspect database tables.

> **Warning:** For development only. Do not use in production.

## Setup

Require `lib/debug.php` in your `config.php` after `setup.php`:

```php
require_once(__DIR__ . '/public/local/devkit/lib/debug.php');
```

## Usage

### Dump

```php
debug($var)->dump();    // var_dump
debug($var)->dd();      // var_dump + die
```

### JSON

```php
debug($var)->json();    // json_encode
debug($var)->jsond();   // json_encode + die
```

### Export

```php
debug($var)->export();  // var_export
debug($var)->exportd(); // var_export + die
```

### Measure

Benchmarks callable(s) in milliseconds:

```php
debug(fn() => heavy())->measure();             // Single callable
debug($fn1, $fn2, $fn3)->measure(iterations: 10); // Multiple, with iterations
```

### Database Tables

Inspect database table structure:

```php
debug()->table('user')->dump();
debug()->table('user', 'course', 'config')->dd();
```

### Performance

Get Moodle performance info:

```php
debug()->performance()->dump();
debug()->performance('dbqueries')->dump();
```

### Chaining

All methods return `$this` for chaining:

```php
debug($data)->json()->die();
debug($fn1, $fn2)->measure()->json()->dd();
```

## API

| Method | Description |
|--------|-------------|
| `dump()` | var_dump payload |
| `dd()` | var_dump + die |
| `json()` | JSON encode payload |
| `jsond()` | JSON encode + die |
| `export()` | var_export payload |
| `exportd()` | var_export + die |
| `measure()` | Benchmark callables (ms) |
| `table()` | Get database table info |
| `performance()` | Get Moodle performance info |
| `die()` | Terminate execution |
