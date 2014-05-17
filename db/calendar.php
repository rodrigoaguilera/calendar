<?php
/**
 * Copyright (c) 2014 Georg Ehrke <oc.list@georgehrke.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCA\Calendar\Db;

use \OCA\Calendar\Sabre\VObject\Component\VCalendar;

use \OCA\Calendar\Utility\CalendarUtility;
use \OCA\Calendar\Utility\RegexUtility;
use \OCA\Calendar\Utility\Utility;
use OCP\Calendar\ICalendar;

class Calendar extends Entity implements ICalendar {

	public $id;
	public $userId;
	public $ownerId;
	public $backend;
	public $uri;
	public $displayname;
	public $components;
	public $ctag;
	public $timezone;
	public $color;
	public $order;
	public $enabled;
	public $cruds;

	/**
	 * @brief init Calendar object with data from db row
	 * @param mixed (array / VCalendar) $createFrom
	 */
	public function __construct($createFrom=null){
		$this->addType('userId', 'string');
		$this->addType('ownerId', 'string');
		$this->addType('backend', 'string');
		$this->addType('uri', 'string');
		$this->addType('displayname', 'string');
		$this->addType('components', 'integer');
		$this->addType('ctag', 'integer');
		$this->addType('color', 'string');
		$this->addType('order', 'integer');
		$this->addType('enabled', 'boolean');
		$this->addType('cruds', 'integer');

		//fill-up default values
		$this->setCtag(0);
		$this->setTimezone(new Timezone('UTC'));
		$this->setColor('#FFFFFF');
		$this->setOrder(0);
		$this->setEnabled(true);

		//create from array
		if (is_array($createFrom)){
			$this->fromRow($createFrom);
		}

		//create from VCalendar
		if ($createFrom instanceof VCalendar) {
			$this->fromVObject($createFrom);
		}
	}


	/**
	 * @brief create calendar object from VCalendar
	 * @param VCalendar $vCalendar
	 * @return $this
	 */
	public function fromVObject(VCalendar $vCalendar) {
		if (isset($vCalendar->{'X-WR-CALNAME'})) {
			$this->setDisplayname($vCalendar->{'X-WR-CALNAME'});
		}

		/*if (isset($vCalendar->{'X-WR-TIMEZONE'})) {
			try {
				$this->setTimezone(new Timezone($vCalendar->{'X-WR-TIMEZONE'}));
			} catch(\Exception $ex) {}
		}*/

		if (isset($vCalendar->{'X-APPLE-CALENDAR-COLOR'})) {
			$this->setColor($vCalendar->{'X-APPLE-CALENDAR-COLOR'});
		}

		return $this;
	}


	/**
	 * @brief get VObject from Calendar Object
	 * @return VCalendar object
	 */
	public function getVObject() {
		$properties = array(
			'X-WR-CALNAME' => $this->getDisplayname(),
			'X-WR-TIMEZONE' => $this->getTimezone(),
			'X-APPLE-CALENDAR-COLOR' => $this->getColor(),
		);
		$vCalendar = new VCalendar($properties);
		//$vCalendar->addComponent($this->timezone->getVObject());

		return $vCalendar;
	}


	/**
	 * @brief does a calendar allow
	 * @param integer $cruds
	 * @return boolean
	 */
	public function doesAllow($cruds) {
		return ($this->cruds & $cruds);
	}


	/**
	 * @brief does a calendar allow a certain component
	 * @param integer $components
	 * @return boolean
	 */
	public function doesSupport($components) {
		return ($this->components & $components);
	}


	/**
	 * @brief increment ctag
	 * @return $this
	 */
	public function touch() {
		$this->ctag++;
		return $this;
	}


	/**
	 * @brief set uri property
	 */
	public function setUri($uri) {
		$slugified = CalendarUtility::slugify($uri);
		return parent::setUri($slugified);
	}


	/**
	 * @brief set timezone
	 * @param \OCA\Calendar\Db\Timezone $timezone
	 */
	public function setTimezone(Timezone $timezone) {
		return parent::setTimezone($timezone);
	}


	/**
	 * @brief get calendarId of object
	 * @return string
	 */
	public function getCalendarId(){
		$backend = $this->backend;
		$calendarURI = $this->uri;

		$calendarId = CalendarUtility::getURI($backend, $calendarURI);
		
		return $calendarId;
	}


	/**
	 * @brief update backend and uri by calendarId
	 * @param string $calendarId
	 * @return mixed
	 *         $this if calendarId was set
	 *         false if calendarId could not be set
	 */
	public function setCalendarId($calendarId) {
		list($backend, $calendarURI) = CalendarUtility::splitURI($calendarId);

		if ($backend !== false && $calendarURI !== false) {
			$this->backend = $backend;
			$this->uri = $calendarURI;

			return $this;
		}

		return false;
	}


	/**
	 * @brief check if object is valid
	 * @return boolean
	 */
	public function isValid() {
		$strings = array(
			$this->userId, $this->ownerId, $this->backend,
			$this->uri, $this->displayname, $this->color
		);

		foreach($strings as $string) {
			if (!is_string($string)) {
				return false;
			}
			if (trim($string) === '') {
				return false;
			}
		}

		$uInts = array(
			$this->components, $this->ctag,
			$this->order, $this->cruds
		);

		foreach($uInts as $integer) {
			if (!is_int($integer)) {
				return false;
			}
			if ($integer < 0) {
				return false;
			}
		}

		$booleans = array(
			$this->enabled
		);

		foreach($booleans as $boolean) {
			if (!is_bool($boolean)) {
				return false;
			}
		}

		if (preg_match(RegexUtility::URI, $this->uri) !== 1) {
			return false;
		}

		if (preg_match(RegexUtility::RGBA, $this->color) !== 1) {
			return false;
		}

		if ($this->components > ObjectType::ALL) {
			return false;
		}

		if ($this->cruds > Permissions::ALL) {
			return false;
		}
 
		if (($this->timezone instanceof Timezone) && !$this->timezone->isValid()) {
			return false;
		}

		return true;
	}


	/**
	 * @brief create string representation of object
	 * @return string
	 */
	public function __toString() {
		return $this->userId . '::' . $this->getCalendarId();
	}
}