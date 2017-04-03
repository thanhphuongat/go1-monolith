<?php

namespace go1\monolith;

use mysqli;

require_once __DIR__ . '/../php/user/domain/password.php';

# Make sure database 'go1_dev' is created.
$db = new mysqli('127.0.0.1', 'root', 'root');
if (!$db->select_db('go1_dev')) {
    if (true !== $db->query('CREATE DATABASE go1_dev')) {
        die("Failed to create 'go1_dev': {$db->error}\n");
    }
}
if ($db->select_db('go1_dev')) {
    # Make sure default portal 'default.go1.local' is created.
    $result = $db->query("SELECT * FROM gc_instance WHERE title = 'default.go1.local'");
    if ($result->num_rows === 0) {
        $data = '{"author":"admin@default.go1.local","configuration":{"is_virtual":1,"user_invite":1,"send_welcome_email":1},"features":{"marketplace":true,"user_invite":true,"auth0":false},"user_plan":{"license":10,"price":3620,"product":"marketplace"}}';
        $now = time();
        $sql = "INSERT INTO gc_instance (title, status, is_primary, version, data, timestamp, created)
            VALUES ('default.go1.local', 1, 1, 'v3.0.0', '{$data}', {$now}, {$now})";
        if (true !== $db->query($sql)) {
            die("Failed to insert 'default.go1.local': {$db->error}\n");
        }
    }
    $result->close();
    # Create user for #staff.
    $result = $db->query("SELECT * FROM gc_user WHERE mail = 'staff@local'");
    if ($result->num_rows === 0) {
        $pass = _password_crypt('sha512', 'root', _password_generate_salt(10));
        $now = time();
        $data = '{"roles":["Admin on #Accounts"]}';
        $sql = "INSERT INTO gc_user (uuid, name, instance, profile_id, mail, password, created, access, login, status, first_name, last_name, allow_public, data, timestamp)
            VALUES ('', null, 'accounts-dev.gocatalyze.com', 0, 'staff@local', '{$pass}', {$now}, {$now}, {$now}, 1, '', '', 0, '{$data}', {$now})";
        if (true !== $db->query($sql)) {
            die("Failed to create user 'staff@local': {$db->error}\n");
        }
    }
    $result->close();
}
$db->close();

# Make sure we have database for all services
$projects = require __DIR__ . '/_projects.php';
foreach (array_keys($projects['php']) as $name) {
    echo "[install] GET http://localhost/GO1/{$name}/install\n";
    @file_get_contents("http://localhost/GO1/{$name}/install");
}
