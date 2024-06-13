<?php

switch ($path_act) {
    case "tambah":
        $menu = $db->fetch_all("sys_menu");
        foreach ($menu as $item) {
            if ($path_url == $item->url && $path_act == "tambah") {
                if ($role_act["insert_act"] == "Y") {
                    include "hapus_akm_feeder_add.php";
                } else {
                    echo "permission denied";
                }
            }
        }
        break;
    case "edit":
        $user = $db->fetch_single_row("config_user", "id", $path_id);
        $menu = $db->fetch_all("sys_menu");
        foreach ($menu as $item) {
            if ($path_url == $item->url && $path_act == "edit") {
                if ($role_act["up_act"] == "Y") {
                    include "hapus_akm_feeder_edit.php";
                } else {
                    echo "permission denied";
                }
            }
        }
        break;
    case 'choose':
        include "hapus_akm_feeder_view.php";
        break;
    case "detail":
        $user = $db->fetch_single_row("config_user", "id", $path_id);
        include "hapus_akm_feeder_detail.php";
        break;
    default:
        include "hapus_akm_feeder_views.php";
        break;
}

