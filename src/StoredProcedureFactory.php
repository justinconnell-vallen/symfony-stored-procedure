<?php
/**
 * For Stored Procedure calls to MSSQL DB
 */

namespace Vallen\StoredProcedureFactory;

use Exception;
use PDO;
use PDOException;
use stdClass;
use Throwable;

use function Sentry\captureException;

class StoredProcedureFactory
{
    protected PDO $conn;
    /**
     * @var false|resource
     */
    private $client;

    public function __construct(
        private readonly string $hostname,
        private readonly string $username,
        private readonly string $pwd,
    ) {}

    /**
     * @throws Throwable
     * Always returns as an array
     */
    public function runProcedure(
        string $procedure,
        array $params = [],
        string $database = 'Storeroom',
        $useSqlSrv = false,
        $returnDebugMessage = false,
	    ?string $serverOverride = null,
    ): bool|array {
        if (!$useSqlSrv) {
            try {
                $this->getPdoConnection($database, $serverOverride);
                $properties = $this->formatPdoProperties($params);
                $pdoQuery = $this->formatPdoQuery($procedure, $properties);
                $stmt = $this->conn->prepare($pdoQuery);
                foreach ($properties as $i => $property) {
                    $stmt->bindParam($property->placeholder, $property->value);
                }
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($results as $key => $result) {
                    $results[$key] = $this->utf8EncodeArray(array_change_key_case($result));
                }

                return $results;
            } catch (Throwable $exception) {
                captureException($exception); // Capture in Sentry. If there's an issue we need to at least see it.
                if ($returnDebugMessage) {
                    throw new PDOException(message: 'Error running '.$procedure.', message: '.$exception->getMessage());
                }
                return false;
            }
        }
        // This works, just want to try binding parameters with PDO.
        // If you do use this, you MUST check the order of parameters used in $params and
        // the Stored Procedure match the order of parameters in that call.
        unset($query);
        unset($this->client);
        try {
            $this->getSqlSrvConnection($database);
            $query = $this->formatSqlSrvQuery($procedure, $params);
            $stmt = sqlsrv_prepare($this->client, $query, $params);
            if (!$stmt) {
                return false;
            }
            $result = sqlsrv_query($this->client, $query, $params);
            $results = [];
            do {
                $results[] = (array)sqlsrv_fetch_object($result);
            } while (sqlsrv_next_result($result));

            return $results;
        } catch (Throwable) {
            $errors = sqlsrv_errors();
            $message = [];
            foreach ($errors as $error) {
                $message[] = $error["SQLSTATE"].$error["code"].$error["message"];
            }
            throw new Exception("SQLSrv operation failed\n".implode("\n\n", $message));
        }
    }

    /**
     * UTF-8 encode an array recursively
     * 
     * @param array $array
     * @return array
     */
    private function utf8EncodeArray(array $array): array
    {
        array_walk_recursive($array, function (&$item) {
            if (!empty($item) && !mb_detect_encoding($item, 'utf-8', true)) {
                $item = utf8_encode($item);
            }
        });

        return $array;
    }

    private function getPdoConnection($database = 'Storeroom', ?string $serverOverride = null): void
    {
        try {
			$host = $this->hostname;
			if ($serverOverride !== null) {
				$host = $serverOverride;
			}
            // Temporarily Trust the Server Certificate
            $this->conn = new PDO(
                dsn: "sqlsrv:Server=$host;database=".$database.";TrustServerCertificate=1",
                username: $this->username,
                password: $this->pwd,
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            die("Database Connection Error. ERROR: ".$exception->getMessage());
        }
    }

    private function formatPdoProperties($params): array
    {
        $properties = [];
        if (count($params)) {
            foreach ($params as $key => $value) {
                $property = new stdClass();
                $property->parameter = "@".$key;
                $property->placeholder = ":".strtolower($key);
                $property->value = $value;
                $properties[] = $property;
                unset($property);
            }
        }

        return $properties;
    }

    private function formatPdoQuery($procedure, $properties): string
    {
        $query = "EXEC [dbo].[{$procedure}]";
        if (count($properties)) {
            $query .= " ";
            foreach ($properties as $i => $property) {
                $query .= $property->parameter.' = '.$property->placeholder;
                if ($i !== array_key_last($properties)) {
                    $query .= ",";
                }
            }
        }

        return $query;
    }

    private function getSqlSrvConnection($dbname): void
    {
        $this->client = sqlsrv_connect(
            $this->hostname,
            ['Database' => $dbname, 'UID' => $this->username, 'PWD' => $this->pwd],
        );
    }

    // get the database connection

    private function formatSqlSrvQuery($procedure, $properties): string
    {
        $query = "{call [dbo].[{$procedure}]";
        if (count($properties)) {
            $query .= "(";
            foreach ($properties as $i => $property) {
                $query .= '?';
                if ($i !== array_key_last($properties)) {
                    $query .= ",";
                }
            }
            $query .= ")}";
        }

        return $query;
    }
}