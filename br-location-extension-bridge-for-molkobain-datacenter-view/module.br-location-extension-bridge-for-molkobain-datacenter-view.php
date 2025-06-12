<?php

/**
 * @copyright   Copyright (C) 2024-2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2025-06-12
 *
 * iTop module definition file
 */

/** @disregard P1009 Undefined type SetupWebPage */
SetupWebPage::AddModule(
    __FILE__, // Path to the current file, all other file names are relative to the directory containing this file
    'br-location-extension-bridge-for-datacenterview/3.2.0',
    array(
        // Identification
        'label' => 'Bridge - Location Extension - Molkobain Datacenter View',
        'category' => 'business',

        // Setup
        'dependencies' => array(
            'br-location-extension/3.2.0||molkobain-datacenter-view/1.6.0',
            'br-location-extension/3.2.0',
        ),
        'mandatory' => false,
        'visible' => true,
        'auto_select' => 'SetupInfo::ModuleIsSelected("molkobain-datacenter-view") && SetupInfo::ModuleIsSelected("br-location-extension")',

        // Components
        'datamodel' => array(),
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
