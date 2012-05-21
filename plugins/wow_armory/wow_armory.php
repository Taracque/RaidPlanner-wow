<?php
/*------------------------------------------------------------------------
# WoW Armory Sync Plugin
# com_raidplanner - RaidPlanner Component
# ------------------------------------------------------------------------
# author    Taracque
# copyright Copyright (C) 2011 Taracque. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Website: http://www.taracque.hu/raidplanner
-------------------------------------------------------------------------*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class RaidPlannerPluginWow_armory extends RaidPlannerPlugin
{
	function __construct( $guild_id, $guild_name, $params)
	{
		parent::__construct( $guild_id, $guild_name, $params);
		
		$this->provide_sync = true;
	}

	public function doSync( $showOkStatus = false )
	{
		$db = & JFactory::getDBO();

		$region = $this->params['guild_region'];
		$realm = $this->params['guild_realm'];

		/* load database ids race array */
		$races = array();
		$query = "SELECT race_id, race_name FROM #__raidplanner_race";
		$db->setQuery( $query );
		$tmp1 = $db->loadAssocList( 'race_name' );

		$url = "http://" . $region . ".battle.net/api/wow/data/character/races";
		$tmp = json_decode( $this->getData( $url ) ,true );

		foreach ($tmp['races'] as $race) {
			$races[ $race['id'] ] = $tmp1[ $race['name'] ]['race_id'];
		}
		
		/* load database ids race array */
		$classes = array();
		$query = "SELECT class_id, class_name FROM #__raidplanner_class";
		$db->setQuery( $query );
		$tmp1 = $db->loadAssocList( 'class_name' );

		$url = "http://" . $region . ".battle.net/api/wow/data/character/classes";
		$tmp = json_decode( $this->getData( $url ) ,true );

		foreach ($tmp['classes'] as $class) {
			$classes[ $class['id'] ] = $tmp1[ $class['name'] ]['class_id'];
		}

		$url = "http://" . $region . ".battle.net/api/wow/guild/";
		$url .= rawurlencode( $realm ) . "/";
		$url .= rawurlencode( $this->guild_name );
		$url = $url . "?fields=members";

		$data = json_decode( $this->getData( $url ) );
		if (function_exists('json_last_error')) {
			if (json_last_error() != JSON_ERROR_NONE)
			{
				JError::raiseWarning('100','ArmorySync data decoding error');
				return null;
			}
		}
		if (isset($data->status) && ($data->status=="nok"))
		{
			JError::raiseWarning('100','ArmorySync failed');
			return null;
		}

		if (($this->guild_name == @$data->name) && ($data->name!=''))
		{
			$params = array(
				'achievementPoints' => $data->achievementPoints,
				'side'		=> ($data->side==0)?"Alliance":"Horde",
				'emblem'	=> $data->emblem,
				'link'		=> "http://" . $region . ".battle.net/wow/guild/" . rawurlencode($realm) . "/" . rawurlencode($data->name) ."/",
				'char_link'	=> "http://" . $region . ".battle.net/wow/character/%s/%s/advanced",
				'guild_realm'	=>	$data->realm,
				'guild_region'	=>	$region,
				'guild_level'	=>	$data->level
			);

			$this->params = array_merge( $this->params, $params );
			
			$query = "UPDATE #__raidplanner_guild SET
							guild_name=".$db->Quote($data->name).",
							params=".$db->Quote(json_encode($params)).",
							lastSync=NOW()
							WHERE guild_id=".intval($this->guild_id);
			$db->setQuery($query);
			$db->query();

			/* detach characters from guild */
			$query = "UPDATE #__raidplanner_character SET guild_id=0 WHERE guild_id=".intval($this->guild_id)."";
			$db->setQuery($query);
			$db->query();

			foreach($data->members as $member)
			{
				// check if character exists
				$query = "SELECT character_id FROM #__raidplanner_character WHERE char_name LIKE BINARY ".$db->Quote($member->character->name)."";
				$db->setQuery($query);
				$char_id = $db->loadResult();
				// not found insert it
				if (!$char_id) {
					$query="INSERT INTO #__raidplanner_character SET char_name=".$db->Quote($member->character->name)."";
					$db->setQuery($query);
					$db->query();
					$char_id=$db->insertid();
				}
				$query = "UPDATE #__raidplanner_character SET class_id='" . $classes[ intval($member->character->class) ] . "'
															,race_id='" . $races[ intval($member->character->race) ] . "'
															,gender_id='" . (intval($member->character->gender) + 1) . "'
															,char_level='" . intval($member->character->level) . "'
															,rank='" . intval($member->rank) . "'
															,guild_id='" . intval($this->guild_id) . "'
															WHERE character_id=" . $char_id;
				$db->setQuery($query);
				$db->query();
			}

			/* delete all guildless characters */
			$query = "DELETE FROM #__raidplanner_character WHERE guild_id=0";
			$db->setQuery($query);
			$db->query();
			
			if ($showOkStatus)
			{
				JError::raiseNotice('0', 'ArmorySync successed');
			}
		} else {
			JError::raiseWarning('100', 'ArmorySync data doesn\'t match');
		}
	}

	public function characterLink( $char_name )
	{
		return sprintf($this->params['char_link'], rawurlencode($this->params['guild_realm']), rawurlencode($char_name) ) . '" target="_blank';
	}
	
	public function guildHeader()
	{
		JHTML::script('guild-tabard.js', 'images/raidplanner/wow_tabards/');
		
		$header = array();
		$header[] = '<canvas id="rp_guild_tabard" width="120" height="120" style="float:right;"></canvas>';
		$header[] = '<script type="text/javascript">';
		$header[] = '	window.addEvent("domready",function(){';
		$header[] = '		var tabard = new GuildTabard("rp_guild_tabard", {';
		$header[] = '			"ring": "' . $this->params['side'] . '",';
		$header[] = '			"bg": [ 0, "' . $this->params['emblem']['backgroundColor'] . '" ], ';
		$header[] = '			"border": [ "' . $this->params['emblem']['border'] . '", "' . $this->params['emblem']['borderColor'] . '" ], ';
		$header[] = '			"emblem": [ "' . $this->params['emblem']['icon'] . '", "' . $this->params['emblem']['iconColor'] . '" ], ';
		$header[] = '		}, "' . JURI::base() . 'images/raidplanner/wow_tabards/");';
		$header[] = '	});';
		$header[] = '</script>';
		$header[] = '<h2><a href="' . $this->params['link'] . '" target="_blank">' . $this->guild_name . '</a></h2>';
		$header[] = '<strong>' . JText::_('COM_RAIDPLANNER_LEVEL') . " " . $this->params['guild_level'] . " " . $this->params['side'] . " " . JText::_('COM_RAIDPLANNER_GUILD') . '<br />';
		$header[] = $this->params['guild_realm'] . " - " . strtoupper($this->params['guild_region']) . '</strong>';

		return implode("\n", $header);
	}

	public function loadCSS()
	{
		JHTML::stylesheet('raidplanner_wow.css', 'images/raidplanner/css/' );
		
		return true;
	}

}