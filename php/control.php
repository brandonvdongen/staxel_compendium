<?php

require_once 'session.php';
require_once '../classes/Database.class.php';
require_once '../classes/ItemFinder.class.php';

$database = new Database();
$itemFinder = new ItemFinder($database);


if ($_POST) {
    if (isset($_POST["all_items"])) {
        $output = [];
        $output[] = $itemFinder->getAllItems();
        $output[] = $itemFinder->getAllRecipes();
        echo json_encode($output);
    }
    if (isset($_POST["language"])) {
        echo json_encode($itemFinder->getLanguageFiles($_POST["language"]));
    }
}

