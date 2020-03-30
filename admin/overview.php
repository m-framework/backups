<?php

namespace modules\backups\admin;

use m\module;
use m\view;
use m\registry;
use m\config;
use m\i18n;
use m\functions;

class overview extends module {

    public function _init()
    {
        $items = [];

        $backups_path = config::get('root_path') . config::get('backups_path') . $this->site->id;

        if (is_dir($backups_path)) {
            $backups = array_diff(scandir($backups_path), ['.', '..']);
        }

        if (!empty($backups)) {
            foreach ($backups as $backup) {

                if (!is_file($backups_path . '/' . $backup))
                    continue;

                $items[] = $this->view->overview_item->prepare([
                    'file_path' => config::get('backups_path') . $this->site->id . '/' . $backup,
                    'file_name' => $backup,
                    'file_size' => functions::file_size($backups_path . '/' . $backup),
                    'file_date' => date('Y-m-d H:i:s', filemtime($backups_path . '/' . $backup)),
                ]);
            }
        }

        return view::set('content', $this->view->overview->prepare([
            'items' => implode('', $items),
        ]));
    }
}