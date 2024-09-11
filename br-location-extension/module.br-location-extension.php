<?php

/**
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2024-09-11
 *
 * iTop module definition file
 */

SetupWebPage::AddModule(
    __FILE__, // Path to the current file, all other file names are relative to the directory containing this file
    'br-location-extension/2.7.8',
    array(
        // Identification
        //
        'label' => 'Location enhancements',
        'category' => 'business',

        // Setup
        //
        'dependencies' => array(
            '(itop-config-mgmt/2.5.0 & itop-config-mgmt/<3.0.0)||itop-structure/3.0.0',
        ),
        'mandatory' => false,
        'visible' => true,

        // Components
        //
        'datamodel' => array(
            'model.br-location-extension.php',
        ),
        'webservice' => array(),
        'data.struct' => array(),
        'data.sample' => array(),

        // Documentation
        //
        'doc.manual_setup' => '',
        'doc.more_information' => '',

        // Default settings
        //
        'settings' => array(),
    )
);
