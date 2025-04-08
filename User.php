<?php

    namespace User;

    require_once __DIR__ . "/config/Database.php";
    require_once __DIR__ . "/config/config.php";

    use config\Database\Database;

    class User
    {

        public function __construct(
            private $id,
            private $username,
            private $email,
            private $password
        )
        {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        }

        public function save()
        {

            $db = Database::getInstance(
                DBHOST,
                DBNAME,
                DBUSERNAME,
                DBPASSWORD,

            );

            return $db->execute(
                "INSERT INTO user (username, email, password) VALUES (:username, :email, :password)",
                [
                    'username' => $this->username,
                    'email' => $this->email,
                    'password' => $this->password,
                ]
            );
        }
    }

    $user = new User(id: 10, username: 'reza@asd', email: 'samiuragha@gmail.com', password: '1234564');

    print_r($user->save());
