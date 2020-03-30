<?php

namespace modules\backups\admin;

use m\core;
use m\module;
use m\view;
use m\registry;
use m\config;
use m\i18n;
use m\functions;
use libraries\pclzip\PclZip;

class delete extends module {

    public function _init()
    {
        $backups_path = config::get('root_path') . config::get('backups_path') . $this->site->id . '/';

        if (!empty($this->get->delete) && is_file($backups_path . $this->get->delete)) {
            unlink($backups_path . $this->get->delete);
        }

        core::redirect(config::get('previous'), 301);
   }
}