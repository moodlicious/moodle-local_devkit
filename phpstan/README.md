# PHPStan Stubs

Stub files that provide type information for Moodle APIs to PHPStan.

## Structure

Stub files mirror Moodle's root directory structure under `phpstan/stubs/`.

```
phpstan/stubs/
├── filelib.stub
├── moodlelib.stub
└── lib/
    ├── classes/
    ├── dml/
    ├── dmllib.stub
    └── filestorage/
```

## Adding Stubs

1. Create a `.stub` file in the appropriate directory.
2. Run `scripts/phpstan/format-stubs.sh` to format the stub.
3. Commit the stub.

## Formatting

```bash
./scripts/phpstan/format-stubs.sh
```

This formats each stub file individually using Pint to handle large numbers of files.
