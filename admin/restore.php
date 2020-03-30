<?php

namespace modules\backups\admin;

use m\core;
use m\model;
use m\module;
use m\view;
use m\registry;
use m\config;
use m\i18n;
use m\functions;
use libraries\pclzip\PclZip;

class restore extends module {

    public function _init()
    {
        $backups_path = config::get('root_path') . config::get('backups_path') . $this->site->id . '/';

        if (empty($this->get->restore) || !is_file($backups_path . $this->get->restore)) {
            core::redirect('/' . $this->config->admin_panel_alias . '/backups');
        }

        $tmp_dir = config::get('root_path') . config::get('tmp_path') . '_' . $this->site->id . '_' . time();
        chdir(config::get('root_path') . config::get('tmp_path'));

        $archive = new PclZip($backups_path . $this->get->restore);

        $archive->extract(PCLZIP_OPT_PATH, $tmp_dir);

        if (is_dir($tmp_dir . '/data')) {
            $path_to_data = config::get('data_path') . $this->site->id;
            functions::move_recursively($tmp_dir . '/data', config::get('root_path') . $path_to_data . '/');
        }

        if (is_dir($tmp_dir . '/template')) {
            $path_to_template = config::get('templates_path') . $this->site->id;
            functions::move_recursively($tmp_dir . '/template', config::get('root_path') . $path_to_template . '/');
        }

        if (is_dir($tmp_dir . '/db')) {
            foreach (array_diff(scandir($tmp_dir . '/db'), ['.', '..']) as $model) {
                $json_arr = (array)@json_decode((string)@file_get_contents($tmp_dir . '/db/' . $model), true);
                foreach ($json_arr as $item_arr) {
                    $model_obj = model::call_static(str_replace('.', '\\', pathinfo($model, PATHINFO_FILENAME)));
//                    $model_obj->_count = 1;
                    $model_obj->save($item_arr);
                }
            }
        }

        if (is_dir($tmp_dir)) {
            functions::delete_recursively($tmp_dir);
        }

        core::redirect('/' . $this->config->admin_panel_alias . '/backups');
   }
}