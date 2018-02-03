<?php

class Auth
{
    const SESSION_VAR = "id_user";
    private $database;
    private $id_user;
    private $email;
    private $username;
    private $displayname;

    public function __construct($database) {
        if ($database instanceof Database) {
            $this->database = $database;
        } else {
            return false;
        }
        if (isset($_SESSION[self::SESSION_VAR])) {
            $this->id_user = $_SESSION[self::SESSION_VAR];
            $this->verify_login();
        } else {
            unset($this->id_user);
            unset($_SESSION[self::SESSION_VAR]);
        }
        return true;
    }

    public function verify_login() {
        if (isset($this->id_user)) {
            if (!is_numeric($id = $this->id_user)) {
                return false;
            } else {
                $result = $this->database->prepared_query('SELECT * FROM users WHERE users.id=?', [$this->get_id()])->result[0];
                if ($result) {
                    $this->email = $result->email;
                    $this->username = $result->username;
                    $this->displayname = $result->displayname;
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    public function get_id() {
        if (isset($this->id_user)) {
            return $this->id_user;
        } else {
            return false;
        }

    }

    public function login($username, $password) {
        $output = new stdClass();
        $result = $this->database->prepared_query("SELECT * FROM users WHERE email=? OR username=?", [$username, $username])->result[0];
        if ($result) {
            $permissions = $this->database->prepared_query("SELECT * FROM permissions WHERE id_user", [$result->id])->result[0];
            if ($result->attempt >= 3 && $permissions->admin === 0) {
                $output->error = "ACCOUNT_LOCKED";
                $output->success = false;
            } elseif (password_verify($password, $result->password)) {
                $_SESSION[self::SESSION_VAR] = $result->id;
                $this->id_user = $result->id;
                $this->email = $result->email;
                $this->username = $result->username;
                $this->displayname = $result->displayname;
                $output->error = "NONE";
                $output->success = true;
                $this->database->prepared_query("UPDATE users SET users.attempt = 0 WHERE users.id = ?", [$this->id_user])->result;
            } else {
                $this->database->prepared_query("UPDATE users SET users.attempt = users.attempt + 1 WHERE email=? OR username=?", [$username, $username])->result;
                $output->error = "INVALID_PASSWORD";
                $output->success = false;
                if ($result->attempt >= 2 && $permissions->admin === 0) {
                    $output->error = "ACCOUNT_LOCKED";
                }
            }

        } else {
            $output->error = "INVALID_USERNAME";
            $output->success = false;
        }
        return $output;
    }

    public function logout() {
        unset($_SESSION[self::SESSION_VAR]);
    }

    public function has_permission($array, $all = false) {
        $result = $all;
        $permissions = $this->get_permissions();
        if(in_array("ADMIN",$permissions))return true;
        foreach ($array as $permission) {
            if ($all) {
                if (!in_array($permission, $permissions)) {
                    $result = false;
                }
            } else {
                if (in_array($permission, $permissions)) {
                    $result = true;
                }
            }
        }
        if($result)return 1;
        else return 0;

    }

    public function get_permissions() {
        if ($this->verify_login()) {
            $permissions = [];
            $result = $this->database->prepared_query("SELECT `permission` FROM permissions WHERE id_user=?", [$this->get_id()])->result;
            if(!$result)return [];
            foreach ($result as $k => $v) {
                $permissions[] = $v->permission;
            }
            return $permissions;

        } else {
            return [];
        }
    }

    public function add_user($username) {
        $output = new stdClass();
        $output->success = true;
        $output->error = "none";
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $password = $this->generate_password();
            $result = $this->database->prepared_query("SELECT * FROM users WHERE users.email=?", [$username])->result[0];
            if (!$result) {
                $this->database->prepared_query("INSERT INTO users (email, password) VALUES (?, ?);", [$username, $password->hash])->result;
                $email_check = $this->database->prepared_query("SELECT * FROM users WHERE email=?", [$username])->result[0];
                if ($email_check) {
                    $this->database->prepared_query("INSERT INTO permissions (permissions.id_user) VALUES (?);", [$email_check->id])->result;
                }
                $output->password = $password->string;
                $output->username = $username;
                return $output;
            } else {
                $output->error = "USER_ALREADY_EXISTS";
                $output->success = false;
                return $output;
            }
        } else {
            $output->error = "EMAIL_NOT_VALID";
            $output->success = false;
            return $output;
        }

    }

    public function generate_password() {
        $output = new stdClass();
        $output->success = true;
        $output->error = "none";

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        $output->string = $randomString;
        $output->hash = password_hash($randomString, PASSWORD_DEFAULT);
        return $output;
    }

    public function change_password($old_password, $new_password) {

        $output = new stdClass();
        $output->success = true;
        $output->error = "";

        $uppercase = preg_match('@[A-Z]@', $new_password);
        $lowercase = preg_match('@[a-z]@', $new_password);
        $number = preg_match('@[0-9]@', $new_password);
//        $sign = preg_match('@[^A-Za-z0-9]@', $new_password);
        $sign = true;
        if (!$uppercase || !$lowercase || !$number || strlen($new_password) < 8) {
            $output->success = false;
            $output->error .= "<style>.missing_requirement{font-size:15px;}</style>";
            if (!$uppercase) $output->error .= "<div class='missing_requirement'>- Missing Uppercase Character</div>";
            if (!$lowercase) $output->error .= "<div class='missing_requirement'>- Missing Lowercase Character</div>";
            if (!$number) $output->error .= "<div class='missing_requirement'>- Missing Numerical Character</div>";
            if (!$sign) $output->error .= "<div class='missing_requirement'>- Missing Special Character</div>";
            if (strlen($new_password) < 8) $output->error .= "<div class='missing_requirement'>- Password needs to be at least 8 characters long</div>";
        } else {

            $user = $this->database->prepared_query("SELECT * FROM users WHERE id=? ", [$this->get_id()])->result[0];
            $current_password = $user->password;
            if (password_verify($old_password, $current_password)) {
                $new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $this->database->prepared_query("UPDATE users SET password=? WHERE id=?", [$new_password, $this->get_id()])->result;
            } else {
                $output->error = "Current password does not match " . $old_password . "|" . $current_password;
                $output->success = false;
            }
        }
        return $output;


    }

    public function set_password($password, $id) {
        $new_password = password_hash($password, PASSWORD_DEFAULT);
        $this->database->prepared_query("UPDATE users SET password=? WHERE id=?", [$new_password, $id])->result;
        return true;
    }

    public function get_username() {
        return $this->username;
    }

    public function get_displayname() {
        return $this->displayname;
    }

    public function get_full_name() {
        $fullname = $this->displayname . "(" . $this->username . ")";
        return $fullname;
    }

}