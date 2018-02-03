<?php

class ItemFinder
{
    private $database;

    public function __construct($database) {
        if ($database instanceof Database) {
            $this->database = $database;
        } else {
            return false;
        }
        return true;
    }

    public function getAllItems() {
        return $this->database->prepared_query("SELECT * FROM staxel.items ORDER BY name ASC", [])->result;
    }

    public function getAllRecipes() {
        return $this->database->prepared_query("SELECT * FROM staxel.recipes ORDER BY name ASC", [])->result;
    }

    public function getLanguageFiles($lang) {
        return $this->database->prepared_query("SELECT * FROM staxel.names WHERE language = ?", [$lang])->result;


    }


}