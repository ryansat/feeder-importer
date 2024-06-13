<?php
session_start();
include "../../inc/config.php";
session_check();

$action = $_GET["act"] ?? null;

switch ($action) {
  case "in":
    $data = [
      "username" => $_POST["username"],
      "password" => $_POST["password"]
    ];

    $inserted = $db->insert("config_user", $data);

    if ($inserted) {
      echo "good";
    } else {
      return false;
    }
    break;

  case "delete":
    $db->delete("config_user", "id", $_GET["id"]);
    break;

  case "up":
    $data = [
      "username" => $_POST["username"],
      "password" => $_POST["password"]
    ];

    $updated = $db->update("config_user", $data, "id", $_POST["id"]);

    if ($updated) {
      echo "good";
    } else {
      return false;
    }
    break;

  default:
    break;
}
