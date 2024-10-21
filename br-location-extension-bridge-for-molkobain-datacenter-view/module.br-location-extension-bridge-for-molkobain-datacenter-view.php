<?php

/**
 * @copyright   Copyright (C) 2024 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2024-10-21
 *
 * iTop module definition file
 */

SetupWebPage::AddModule(
    __FILE__, // Path to the current file, all other file names are relative to the directory containing this file
    'br-location-extension-bridge-for-datacenterview/2.7.9',
    array(
        // Identification
        'label' => 'Bridge - Location Extension - Molkobain Datacenter View',
        'category' => 'business',

        // Setup
        'dependencies' => array(
            'br-location-extension/2.7.9||molkobain-datacenter-view/1.6.0',
            'br-location-extension/2.7.9',
        ),
        'mandatory' => false,
        'visible' => true,
        'auto_select' => 'SetupInfo::ModuleIsSelected("molkobain-datacenter-view") && SetupInfo::ModuleIsSelected("br-location-extension")',

        // Components
        'datamodel' => array(
            'model.br-location-extension-bridge-for-molkobain-datacenter-view.php',
        ),
        'webservice' => array(),
        'dictionary' => array(),
        'data.struct' => array(),
        'data.sample' => array(),

        // Documentation
        'doc.manual_setup' => '',
        'doc.more_information' => '',

        // Default settings
        'settings' => array(),
    )
);
