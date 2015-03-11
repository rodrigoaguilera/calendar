<?php
/**
 * ownCloud - Calendar
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alessandro Cosentino <cosenal@gmail.com>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Alessandro Cosentino 2012
 * @copyright Bernhard Posselt 2012, 2014
 */

require_once __DIR__ . '/../3rdparty/VObject/includes.php';
//require_once __DIR__ . '/../../../tests/lib/appframework/db/MapperTestUtility.php';

// to execute without owncloud, we need to create our own classloader
spl_autoload_register(function ($className){
	if(strpos($className, 'OCA\\CalendarManager\\Utility\\Utility') === 0) {
		require_once __DIR__ . '/../utility/utility.php';
	} elseif(strpos($className, 'OCA\\CalendarManager\\Utility') === 0) {
		$path = strtolower(str_replace('Utility', '', str_replace('\\', '/', substr($className, 20))) . '.php');
		$relPath = __DIR__ . '/../utility' . $path;

		if (file_exists($relPath)) {
			require_once $relPath;
		}
	} else if (strpos($className, 'OCA\\') === 0) {

		$path = strtolower(str_replace('\\', '/', substr($className, 3)) . '.php');
		$relPath = __DIR__ . '/../..' . $path;

		if(file_exists($relPath)){
			require_once $relPath;
		}
	} else if(strpos($className, 'OCP\\CalendarManager\\IBackendAPI') === 0) {
		require_once __DIR__ . '/../backend/ibackendapi.php';
	} else if(strpos($className, 'OCP\\CalendarManager\\ICalendarAPI') === 0) {
		require_once __DIR__ . '/../backend/icalendarapi.php';
	} else if(strpos($className, 'OCP\\CalendarManager\\IObjectAPI') === 0) {
		require_once __DIR__ . '/../backend/iobjectapi.php';
	} else if(strpos($className, 'OCP\\CalendarManager') === 0) {
		$path = strtolower(str_replace('\\', '/', substr($className, 12)) . '.php');
		$relPath = __DIR__ . '/../public' . $path;

		if(file_exists($relPath)){
			require_once $relPath;
		}
	} else if(strpos($className, 'OCP\\') === 0) {
		$path = strtolower(str_replace('\\', '/', substr($className, 3)) . '.php');
		$relPath = __DIR__ . '/../../../lib/public' . $path;

		if(file_exists($relPath)){
			require_once $relPath;
		}
	}
});