# Vallen Stored Procedure Factory

A reusable PHP library for executing stored procedures on Microsoft SQL Server databases using both PDO and SqlSrv extensions.

## Features

- Support for both PDO and native SqlSrv connections
- Automatic UTF-8 encoding of results
- Parameter binding for secure query execution
- Error handling with Sentry integration
- Server override capability for multi-server environments
- Configurable database selection

## Requirements

- PHP 8.1 or higher
- PDO extension with SQL Server driver
- Sentry SDK for error logging
- Optional: SqlSrv extension for native SQL Server support

## Installation

### Via Composer (Local Package)

Add the package to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../vallen-stored-procedure-factory"
        }
    ],
    "require": {
        "vallen-webpack/stored-procedure-bundle": "*"
    }
}
```

Then run:

```bash
composer install
```

### Via Composer (Private Repository)

If you publish this to a private repository:

```bash
composer require vallen-webpack/stored-procedure-bundle
```

## Usage

### Basic Usage

```php
use Vallen\StoredProcedureFactory\StoredProcedureFactory;

// Initialize the factory
$factory = new StoredProcedureFactory(
    hostname: 'your-sql-server.com',
    username: 'your-username',
    pwd: 'your-password'
);

// Execute a stored procedure
$results = $factory->runProcedure(
    procedure: 'YourStoredProcedure',
    params: [
        'param1' => 'value1',
        'param2' => 'value2'
    ],
    database: 'YourDatabase'
);
```

### Advanced Usage

```php
// Using SqlSrv instead of PDO
$results = $factory->runProcedure(
    procedure: 'YourStoredProcedure',
    params: ['param1' => 'value1'],
    database: 'YourDatabase',
    useSqlSrv: true
);

// Using server override
$results = $factory->runProcedure(
    procedure: 'YourStoredProcedure',
    params: ['param1' => 'value1'],
    database: 'YourDatabase',
    serverOverride: 'backup-server.com'
);

// Enable debug messages
$results = $factory->runProcedure(
    procedure: 'YourStoredProcedure',
    params: ['param1' => 'value1'],
    database: 'YourDatabase',
    returnDebugMessage: true
);
```

### Symfony Integration

This package provides two ways to integrate with Symfony:

1. Register the bundle in your `config/bundles.php`:

```php
return [
    // ... other bundles
    Vallen\StoredProcedureFactory\VallenStoredProcedureBundle::class => ['all' => true],
];
```

2. Configure the bundle in `config/packages/vallen_webpack_stored_procedure_bundle.yaml`:

```yaml
vallen_stored_procedure:
    hostname: '%env(SP_HOST)%'
    username: '%env(SP_USER)%'
    password: '%env(SP_PASS)%'
```

3. The service will be automatically available for autowiring:

```php
use Vallen\StoredProcedureFactory\StoredProcedureFactory;

class YourService
{
    public function __construct(
        private readonly StoredProcedureFactory $storedProcedureFactory
    ) {}

    public function getData(): array
    {
        return $this->storedProcedureFactory->runProcedure(
            'GetData',
            ['userId' => 123]
        );
    }
}
```

## Configuration

### Environment Variables

Set the following environment variables:

```env
SP_HOST=your-sql-server.com
SP_USER=your-username
SP_PASS=your-password
```

### Connection Options

The factory supports several connection options:

- **hostname**: SQL Server hostname or IP address
- **username**: Database username
- **pwd**: Database password
- **database**: Target database name (default: 'Storeroom')
- **useSqlSrv**: Use native SqlSrv extension instead of PDO (default: false)
- **serverOverride**: Override the default hostname for specific calls
- **returnDebugMessage**: Return detailed error messages (default: false)

## Error Handling

The factory integrates with Sentry for error logging. All exceptions are automatically captured and logged. You can enable debug messages by setting `returnDebugMessage` to `true`.

## Return Values

- **Success**: Returns an array of results from the stored procedure
- **Failure**: Returns `false` (unless debug messages are enabled, then throws exception)
- **UTF-8 Encoding**: All string values in results are automatically UTF-8 encoded

## Migration from App\Factory\StoredProcedureFactory

If you're migrating from the original `App\Factory\StoredProcedureFactory`:

1. Update your import statements:
   ```php
   // Old
   use App\Factory\StoredProcedureFactory;
   
   // New
   use Vallen\StoredProcedureFactory\StoredProcedureFactory;
   ```

2. Update your service configuration in `services.yaml`:
   ```yaml
   # Old
   App\Factory\StoredProcedureFactory:
       bind:
           $hostname: '%env(resolve:SP_HOST)%'
           $pwd: '%env(resolve:SP_PASS)%'
           $username: '%env(resolve:SP_USER)%'
   
   # New
   Vallen\StoredProcedureFactory\StoredProcedureFactory:
       bind:
           $hostname: '%env(resolve:SP_HOST)%'
           $pwd: '%env(resolve:SP_PASS)%'
           $username: '%env(resolve:SP_USER)%'
   ```

3. The API remains the same, so no changes to method calls are required.

## License

MIT License

## Contributing

Please follow PSR-12 coding standards and include tests for any new features.

## Support

For issues and questions, please contact the Vallen development team.