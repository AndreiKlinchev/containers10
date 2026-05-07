<?php

class Database
{
    private PDO $pdo;

    public function __construct(string $dsn, string $username, string $password)
    {
        $this->pdo = new PDO($dsn, $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function Execute($sql)
    {
        return $this->pdo->exec($sql);
    }

    public function Fetch($sql)
    {
        $statement = $this->pdo->query($sql);
        return $statement->fetchAll();
    }

    public function Create($table, $data)
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($column) => ':' . $column, $columns);

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(fn($column) => '`' . $column . '`', $columns)),
            implode(', ', $placeholders)
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function Read($table, $id)
    {
        $sql = sprintf('SELECT * FROM `%s` WHERE id = :id LIMIT 1', $table);
        $statement = $this->pdo->prepare($sql);
        $statement->execute(['id' => $id]);

        $result = $statement->fetch();
        return $result ?: null;
    }

    public function Update($table, $id, $data)
    {
        $assignments = implode(
            ', ',
            array_map(fn($column) => '`' . $column . '` = :' . $column, array_keys($data))
        );

        $sql = sprintf('UPDATE `%s` SET %s WHERE id = :id', $table, $assignments);
        $statement = $this->pdo->prepare($sql);
        $data['id'] = $id;

        return $statement->execute($data);
    }

    public function Delete($table, $id)
    {
        $sql = sprintf('DELETE FROM `%s` WHERE id = :id', $table);
        $statement = $this->pdo->prepare($sql);

        return $statement->execute(['id' => $id]);
    }

    public function Count($table)
    {
        $sql = sprintf('SELECT COUNT(*) AS count FROM `%s`', $table);
        $statement = $this->pdo->query($sql);
        $result = $statement->fetch();

        return (int) $result['count'];
    }
}
