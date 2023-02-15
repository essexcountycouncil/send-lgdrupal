<?php

namespace Drupal\dbal;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\DriverManager;
use Drupal\Core\Database\Database;

/**
 * Provides a connection factory for connection to databases via doctrine/dbal.
 */
class ConnectionFactory {

  /**
   * Connection info.
   *
   * @var array
   */
  protected $info;

  /**
   * Connection cache.
   *
   * @var \Doctrine\DBAL\Connection[]
   */
  protected $cache;

  /**
   * Constructs a new ConnectionFactory object.
   */
  public function __construct() {
    $this->info = Database::getAllConnectionInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // We don't serialize the connection cache.
    return ['info'];
  }

  /**
   * Gets a DBAL connection to the given target.
   *
   * @param string $target
   *   Database connection as named in global $databases parameter.
   *
   * @return \Doctrine\DBAL\Connection
   *   Requested connection.
   */
  public function get($target = 'default') {
    if (!isset($this->cache[$target])) {
      if (!isset($this->info[$target])) {
        // Fallback to default connection.
        $target = 'default';
      }
      $info = $this->info[$target]['default'];
      $options = [
        'dbname' => $info['database'],
        'user' => $info['username'] ?? '',
        'password' => $info['password'] ?? '',
        'driver' => 'pdo_' . $info['driver'],
      ];
      if (isset($info['host'])) {
        $options['host'] = $info['host'];
      }
      if (isset($info['unix_socket'])) {
        $options['unix_socket'] = $info['unix_socket'];
      }
      if (isset($info['port'])) {
        $options['port'] = $info['port'];
      }
      if (isset($info['pdo'])) {
        $options['driverOptions'] = $info['pdo'];
      }
      $this->cache[$target] = DriverManager::getConnection($options, new Configuration());
      if ($info['driver'] == 'sqlite') {
        $this->sqliteDatabases($this->cache[$target], $info['prefix'], $info['database']);
      }
    }
    return $this->cache[$target];
  }

  /**
   * SQLite attach prefixes as databases.
   *
   * @param \Doctrine\DBAL\Driver\Connection $connection
   *   The connection to an SQLite database.
   * @param array $prefixes
   *   Drupal info array of database prefixes.
   * @param string $base_db
   *   The connected dbname.
   *
   * @see Drupal\Core\Database\Driver\sqlite\Connection::__construct()
   */
  protected function sqliteDatabases(Connection $connection, array $prefixes, $base_db) {
    $attached = [];
    foreach ($prefixes as $prefix) {
      if (!isset($attached[$prefix])) {
        $attached[$prefix] = TRUE;
        $query = $connection->prepare('ATTACH DATABASE :db AS :prefix');
        if ($base_db == ':memory:') {
          $query->execute([
            ':db' => $base_db,
            ':prefix' => $prefix,
          ]);
        }
        else {
          $query->execute([
            ':db' => $base_db . '-' . $prefix,
            ':prefix' => $prefix,
          ]);
        }
      }
    }
  }

}
