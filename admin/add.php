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
use modules\modules\models\modules;

error_reporting(E_ALL);
ini_set('display_errors', 1);

class add extends module {

    public function _init()
    {
        $file_name = $this->site->host . date('_Y.m.d_H.i') . '.zip';

        $backups_path = config::get('root_path') . config::get('backups_path') . $this->site->id;

        if (!is_dir($backups_path)) {
            functions::build_dir(config::get('backups_path') . $this->site->id);
        }

        if (is_file($backups_path . '/' . $file_name)) {
            @unlink($backups_path . '/' . $file_name);
        }

        $zip = new \ZipArchive;
        $zip->open($backups_path . '/' . $file_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        /**
         * Add to backup a custom website template
         */
        $path_to_template = config::get('templates_path') . '/' . $this->site->id . '/' . $this->site->template;
        if (is_dir(config::get('root_path') . $path_to_template)) {

            chdir(config::get('root_path'));

            $zip->addGlob('*.*', GLOB_BRACE, ['add_path' => $path_to_template, 'remove_all_path' => TRUE]);
        }

        /**
         * Backup a data files
         */
        $path_to_data = config::get('data_path') . '/' . $this->site->id;

        if (is_dir(config::get('root_path') . $path_to_data)) {

            chdir(config::get('root_path'));

            $zip->addGlob('*.*', GLOB_BRACE, ['add_path' => $path_to_data, 'remove_all_path' => TRUE]);
        }

        /**
         * Store a DB records per each of model of each of modules (if model has a site parameter)
         */
        $tmp_db_path = config::get('root_path') . config::get('tmp_path') . '_db_' . $this->site->id;
        mkdir($tmp_db_path);
        $modules = modules::get_modules_paths();

        foreach ($modules as $module => $module_path) {

            if (!is_dir($module_path . '/models')) {
                continue;
            }

            foreach (array_diff(scandir($module_path . '/models'), ['.', '..']) as $model) {

                $model = pathinfo($model, PATHINFO_FILENAME);

                $model_class = $module_path . '/models/' . $model;
                $model_class = trim(str_replace(config::get('root_path') . '/m-framework/', '', $model_class), '/');
                $model_class = str_replace('/', '\\', $model_class);

                if (!class_exists($model_class)) {
                    continue;
                }

                $model_obj = model::call_static($model_class);

                $model_fields = !empty($model_obj) && method_exists($model_obj, 'get_fields') ?
                    $model_obj->get_fields() : [];

                if (empty($model_fields['site'])) {
                    continue;
                }

                $records_by_site = $model_obj->s([], ['site' => $this->site->id], [10000])->all();

                if (empty($records_by_site)) {
                    continue;
                }

                file_put_contents($tmp_db_path . '/' . str_replace('\\', '.', $model_class) . '.json',
                    json_encode($records_by_site, JSON_UNESCAPED_UNICODE));

                unset($model_obj);
                unset($model_fields);
            }
        }

        // TODO: find how to store a pages

        chdir(config::get('root_path') . config::get('tmp_path'));

        $zip->addFile('_db_' . $this->site->id);

        $zip->close();

        functions::delete_recursively($tmp_db_path);

        core::redirect('/' . $this->config->admin_panel_alias . '/backups');
   }
}