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
    'br-location-extension/3.2.0',
    array(
        // Identification
        //
        'label' => 'Location enhancements',
        'category' => 'business',

        // Setup
        //
        'dependencies' => array(
            'itop-config-mgmt/3.2.0',
            'itop-structure/3.2.0',
        ),
        'mandatory' => false,
        'visible' => true,
        'installer' => 'LocationExtensionInstaller',

        // Components
        //
        'datamodel' => array(),
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


if (!class_exists('LocationExtensionInstaller')) {
    /**
     * Class LocationExtensionInstaller
     *
     * @since v3.1.9
     */
    class LocationExtensionInstaller extends ModuleInstallerAPI
    {

        public static function BeforeWritingConfig(Config $oConfiguration)
        {
            // If you want to override/force some configuration values, do it here
            return $oConfiguration;
        }
        public static function AfterDatabaseCreation(Config $oConfiguration, $sPreviousVersion, $sCurrentVersion)
        {
            if (version_compare($sPreviousVersion, '3.1.9', '<')) {

                SetupLog::Info("|- Upgrading br-location-extension from '$sPreviousVersion' to '$sCurrentVersion'.");

                $oSearch = DBSearch::FromOQL('SELECT Location WHERE parent_id = 0');
                $oSet = new DBObjectSet($oSearch, array(), array());
                if ($oSet->Count() > 0) {
                    while ($oLocation = $oSet->Fetch()) {
                        $sLocationName = $oLocation->Get('name');
                        $oLocation->i_NameChanged = true;
                        $oLocation->SetNicename();
                        $oLocation->DBUpdate();
                        $sLocationNicename = $oLocation->Get('nicename');
                        SetupLog::Info("|  |- Location '$sLocationName' Nicename changed to '$sLocationNicename'.");
                    }
                }
            }
        }
    }
}
