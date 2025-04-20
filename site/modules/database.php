<?php

class Database
{
    private $db;

    public function __construct($path)
    {
        $this->db = new PDO('sqlite:' . $path);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function Execute($sql)
    {
        return $this->db->exec($sql);
    }

    public function Fetch($sql)
    {
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function Create($table, $data)
    {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();
        return $this->db->lastInsertId();
    }

    public function Read($table, $id)
    {
        $sql = "SELECT * FROM {$table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function Update($table, $id, $data)
    {
        $updates = [];
        foreach (array_keys($data) as $field) {
            $updates[] = "{$field} = :{$field}";
        }
        $updatesStr = implode(', ', $updates);

        $sql = "UPDATE {$table} SET {$updatesStr} WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':id', $id);

        return $stmt->execute();
    }

    public function Delete($table, $id)
    {
        $sql = "DELETE FROM {$table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }

    public function Count($table)
    {
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}

?>