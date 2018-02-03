<pre>
<?php
require_once '../classes/Database.class.php';

function logger($message) {
    echo $message . "<br>";
}

function setProp(&$obj, $arr, $val) {
    if (gettype($arr) == 'string') $arr = explode(".", $arr);
    if (!isset($obj[$arr[0]])) {
        $obj[$arr[0]] = [];
    }

    $tmp = &$obj[$arr[0]];
    if (count($arr) > 1) {
        array_shift($arr);
        setProp($tmp, $arr, $val);
    } else {
        $obj[$arr[0]] = $val;
    }
    return $obj;
}


function roundPrice($price) {
    if ($price <= 0) return 0;


    $scale = pow(10, floor(log10($price)) + 1);
    $rounded = $scale * round(ceil($price / $scale * 100) / 100, 2);
    return $rounded;
}

function fetch_language($database, $lang_directory, $language) {
    global $all_items;
    $lang_all_files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($lang_directory));
    $lang_files = new RegexIterator($lang_all_files, '/\.lang$/');
    $lang = [];

    if ($database instanceof Database) {
        $delete = 'DELETE FROM staxel.names WHERE language = ?';
        $reset = "ALTER TABLE staxel.names AUTO_INCREMENT = 1";
        $database->prepared_query($delete, [$language]);
        $database->prepared_query($reset);

    }
    $total_lines = 0;
    foreach ($lang_files as $lang_file) {
        $input = file($lang_file);
        foreach ($input as $line) {
            $parts = explode("=", $line);
            if (isset($parts[0], $parts[1])) {
                $parts[0] = trim(strtolower($parts[0]));
                if (in_array($parts[0], $all_items)) {
                    $lang[] = "(\"$language\",\"{$parts[0]}\",\"{$parts[1]}\")";
                }
                if (count($lang) > 1000) {
                    if ($database instanceof Database) {
                        $query = 'INSERT IGNORE INTO staxel.names (language, code, name)  VALUES ' . implode(',', $lang);
                        $database->prepared_query($query);
                        $lang = [];
                        $total_lines += 1000;
                    }
                }
            }
        }
    }
    if ($database instanceof Database) {
        $total_lines += count($lang);
        $query = 'INSERT IGNORE INTO staxel.names (language, code, name)  VALUES ' . implode(',', $lang);
        $database->prepared_query($query);
        logger("Processed localization '$language' with $total_lines lines");

    }
}

$database = new Database();
$game_directory = 'D:\steam\steamapps\common\Staxel\content\staxel';

$game_files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($game_directory));
$item_files = new RegexIterator($game_files, '/\.(item|tile|plant|accessory|recipe)$/');


$data = [];
global $all_items;
$categories = [];
$all_items = [];
$item_categories = [];
foreach ($item_files as $file) {

    $file_data = explode(".", $file->getFilename());
    $file_name = $file_data[0];
    $file_type = $file_data[1];

    $input = file_get_contents($file);
    $json = json_decode($input, true);

    if (isset($json["pricing"])) {
        $tags = explode(".", $json["code"]);
        if (!in_array($tags[1], $item_categories)) $item_categories[] = $tags[1];
        setProp($data, [$tags[1], $json["code"]], $json);
        $all_items[] = trim(strtolower($json["code"]));
        $all_items[] = trim(strtolower($json["code"]) . ".name");
        $all_items[] = trim(strtolower($json["code"]) . ".description");
        $categories[] = $tags[1];
    } else continue;
}

$query_insert_items = [];
$query_insert_categories = [];
$query_insert_recipes = [];
logger("Processed " . count($data) . " categories");

foreach ($data as $type => $items) {
    logger("- Found " . count($items) . " items of type $type");
    foreach ($items as $item) {
        $item["code"] = strtolower($item["code"]);
        $categories = "[]";
        if(isset($item["ingredients"])){
            $ingredients = json_encode($item["ingredients"]);
            $result = json_encode($item["result"]);
            $query_insert_recipes[] = "('{$item["code"]}','{$ingredients}','{$result}')";
        }
        if (isset($item["categories"])) $categories = json_encode($item["categories"]);
        $query_insert_items[] = "('{$item["code"]}','$type','{$categories}','{$item["pricing"]["value"]}','{$item["pricing"]["sellPrice"]}')";
        if (isset($item["categories"])) {
            foreach ($item["categories"] as $sub_category) {
                $entry = "('{$item["code"]}','$sub_category')";
                if (!in_array($entry, $query_insert_categories)) {
                    $query_insert_categories[] = "('{$item["code"]}','$sub_category')";
                }
            }
        }
    }
}
logger("Found a total of " . count($all_items) / 3 . " items");
logger("With a total of " . count($query_insert_categories) . " sub categories");

$languages = ["en-GB","fr-FR"];
$lang_directory = 'D:\steam\steamapps\common\Staxel\content\staxel\StaxelTranslations\\';

foreach ($languages as $language) {
    fetch_language($database, $lang_directory . $language, $language);
}

$queries = [];

$queries[] = 'DELETE FROM staxel.items WHERE 1';
$queries[] = 'DELETE FROM staxel.item_category WHERE 1';
$queries[] = 'DELETE FROM staxel.recipes WHERE 1';

$queries[] = "ALTER TABLE staxel.items AUTO_INCREMENT = 1";
$queries[] = "ALTER TABLE staxel.item_category AUTO_INCREMENT = 1";
$queries[] = "ALTER TABLE staxel.recipes AUTO_INCREMENT = 1";

$queries[] = 'INSERT IGNORE INTO staxel.items (name, type, categories, buy, sell)  VALUES ' . implode(',', $query_insert_items);
$queries[] = 'INSERT IGNORE INTO staxel.item_category (item, category)  VALUES ' . implode(',', $query_insert_categories);
$queries[] = 'INSERT IGNORE INTO staxel.recipes (name, ingredients, result)  VALUES ' . implode(',', $query_insert_recipes);

foreach ($queries as $query) {
    $database->prepared_query($query);
}


?>

    <script>console.log(<?=json_encode($all_items)?>);</script>
