<?php

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_configtext('filter_garavot/csk',
    get_string('csk', 'filter_garavot'),
    get_string('csk_desc', 'filter_garavot'), null, PARAM_NOTAGS, 32));

$settings->add(new admin_setting_configtextarea('filter_garavot/watermark',
    get_string('watermark', 'filter_garavot'),
    get_string('watermark_desc', 'filter_garavot'), null, PARAM_NOTAGS, 100, 10));
