<?php

/**
 * @license     https://www.gnu.org/licenses/gpl-3.0.en.html
 * @version     2021-05-20
 *
 * Localized data
 */

Dict::Add('EN US', 'English', 'English', array(
    // - Location
    'Class:Location/Attribute:type' => 'Type',
    'Class:Location/Attribute:type+' => 'What kind of location is it or what purpose does it have?',
    'Class:Location/Attribute:parent_id' => 'Parent',
    'Class:Location/Attribute:parent_id+' => 'Location hosting this one (eg. For a \'room\', should be a \'floor\' or a \'building\')',
    'Class:Location/Attribute:locations_list' => 'Child locations',
    'Class:Location/Attribute:locations_list+' => 'List of all locations included in this one',
    'Class:Location/Attribute:type/Value:1' => 'Campus',
    'Class:Location/Attribute:type/Value:2' => 'Building',
    'Class:Location/Attribute:type/Value:3' => 'Floor',
    'Class:Location/Attribute:type/Value:4' => 'Room',
    'Class:Location/Attribute:type/Value:5' => 'Tile',
));
