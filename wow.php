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

class PlgRaidplannerWow extends JPlugin
{
	private $guild_id = 0;
	private $rp_params = array();
	private $guild_name = '';
	
	public function onRPInitGuild( $guildId, $params )
	{
		$db = JFactory::getDBO();
		$query = "SELECT guild_name,guild_id FROM #__raidplanner_guild WHERE guild_id=" . intval($guildId); 
		$db->setQuery($query);
		if ( $data = $db->loadObject() )
		{
			$this->guild_name = $data->guild_name;
			$this->guild_id = $data->guild_id;
			$this->rp_params = $params;
		} else {
			$this->guild_id = 0;
		}
	}

	public function onRPBeforeSync()
	{
		return true;
	}

	public function onRPSyncGuild( $showOkStatus = false, $syncInterval = 4, $forceSync = false )
	{
		$db = JFactory::getDBO();

		$query = "SELECT IF(lastSync IS NULL,-1,DATE_ADD(lastSync, INTERVAL " . intval( $syncInterval ) . " HOUR)-NOW()) AS needSync,guild_name FROM #__raidplanner_guild WHERE guild_id=" . intval($this->guild_id); 
		$db->setQuery($query);
		if ( (!$forceSync) && ( !($needsync = $db->loadResult()) || ( $needsync>=0 ) ) )
		{
			/* Sync not needed, exit */
			return false;
		}

		JLoader::register('RaidPlannerHelper', JPATH_ADMINISTRATOR . '/components/com_raidplanner/helper.php' );

		$region = $this->rp_params['guild_region'];
		$realm = $this->rp_params['guild_realm'];
		$api_key = $this->rp_params['api_key'];

		/* load database ids race array */
		$races = array();
		$query = "SELECT race_id, race_name FROM #__raidplanner_race";
		$db->setQuery( $query );
		$tmp1 = $db->loadAssocList( 'race_name' );

		if ($api_key) {
			$url = "https://" . $region . ".api.battle.net/api/wow/data/character/races?locale=en_GB&apikey=" . $api_key;
		} else {
			$url = "http://" . $region . ".battle.net/api/wow/data/character/races";
		}
		$tmp = json_decode( RaidPlannerHelper::downloadData( $url ) ,true );

		foreach ($tmp['races'] as $race) {
			$races[ $race['id'] ] = $tmp1[ $race['name'] ]['race_id'];
		}
		
		/* load database ids race array */
		$classes = array();
		$query = "SELECT class_id, class_name FROM #__raidplanner_class";
		$db->setQuery( $query );
		$tmp1 = $db->loadAssocList( 'class_name' );

		if ($api_key) {
			$url = "https://" . $region . ".api.battle.net/api/wow/data/character/classes?locale=en_GB&apikey=" . $api_key;
		} else {
			$url = "http://" . $region . ".battle.net/api/wow/data/character/classes";
		}
		$tmp = json_decode( RaidPlannerHelper::downloadData( $url ) ,true );

		foreach ($tmp['classes'] as $class) {
			$classes[ $class['id'] ] = $tmp1[ $class['name'] ]['class_id'];
		}

		if ($api_key) {
			$url = "https://" . $region . ".api.battle.net/api/wow/guild/";
			$url .= rawurlencode( $realm ) . "/";
			$url .= rawurlencode( $this->guild_name );
			$url = $url . "?fields=members&locale=en_GB&apikey=" . $api_key;
		} else {
			$url = "http://" . $region . ".battle.net/api/wow/guild/";
			$url .= rawurlencode( $realm ) . "/";
			$url .= rawurlencode( $this->guild_name );
			$url = $url . "?fields=members";
		}

		$data = json_decode( RaidPlannerHelper::downloadData( $url ) );
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
				'emblem'	=> get_object_vars( $data->emblem ),
				'link'		=> "http://" . $region . ".battle.net/wow/guild/" . rawurlencode($realm) . "/" . rawurlencode($data->name) ."/",
				'char_link'	=> "http://" . $region . ".battle.net/wow/character/%s/%s/advanced",
				'guild_realm'	=>	$data->realm,
				'guild_region'	=>	$region,
				'guild_level'	=>	$data->level
			);

			$this->rp_params = array_merge( $this->rp_params, $params );
			
			$query = "UPDATE #__raidplanner_guild SET
							guild_name=".$db->Quote($data->name).",
							params=".$db->Quote(json_encode($params)).",
							lastSync=NOW()
							WHERE guild_id=".intval($this->guild_id);
			$db->setQuery($query);
			$db->query();
			
			/* if we atleast one member in listed */
			if ( (is_array($data->members)) && (count($data->members) > 0) ) {	
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
			}
			
			if ($showOkStatus)
			{
				JError::raiseNotice('0', 'ArmorySync successed');
			}
		} else {
			JError::raiseWarning('100', 'ArmorySync data doesn\'t match');
		}
	}

	public function onRPGetCharacterLink( $char_name )
	{
		return sprintf($this->rp_params['char_link'], rawurlencode($this->rp_params['guild_realm']), rawurlencode($char_name) ) . '" data-darktip="wow.character:'.$this->rp_params['guild_region'].'.'.$this->rp_params['guild_realm'].'.'.$char_name.'(en)" target="_blank';
	}
	
	public function onRPGetGuildHeader()
	{
		$document = JFactory::getDocument();
		$document->addScript('media/com_raidplanner/wow_tabards/guild-tabard.js');
		
		$header = array();
		if (($this->rp_params['side']) && ($this->rp_params['emblem'])) {
			$header[] = '<canvas id="rp_guild_tabard" width="120" height="120" style="float:right;"></canvas>';
			$header[] = '<script type="text/javascript">';
			$header[] = '	window.addEvent("domready",function(){';
			$header[] = '		var tabard = new GuildTabard("rp_guild_tabard", {';
			$header[] = '			"ring": "' . $this->rp_params['side'] . '",';
			$header[] = '			"bg": [ 0, "' . $this->rp_params['emblem']['backgroundColor'] . '" ], ';
			$header[] = '			"border": [ "' . $this->rp_params['emblem']['border'] . '", "' . $this->rp_params['emblem']['borderColor'] . '" ], ';
			$header[] = '			"emblem": [ "' . $this->rp_params['emblem']['icon'] . '", "' . $this->rp_params['emblem']['iconColor'] . '" ], ';
			$header[] = '		}, "' . JURI::base() . 'media/com_raidplanner/wow_tabards/");';
			$header[] = '	});';
			$header[] = '</script>';
		}
		$header[] = '<h2><a href="' . $this->rp_params['link'] . '" data-darktip="wow.guild:'.$this->rp_params['guild_region'].'.'.$this->rp_params['guild_realm'].'.'.$this->guild_name.'(en)" target="_blank">' . $this->guild_name . '</a></h2>';
		$header[] = '<strong>' . JText::_('COM_RAIDPLANNER_LEVEL') . " " . $this->rp_params['guild_level'] . " " . $this->rp_params['side'] . " " . JText::_('COM_RAIDPLANNER_GUILD') . '<br />';
		$header[] = '<span data-darktip="wow.realm:'.$this->rp_params['guild_region'].'.'.$this->rp_params['guild_realm'].'(en)">'.$this->rp_params['guild_realm'] . " - " . strtoupper($this->rp_params['guild_region']) . '</span></strong>';

		return implode("\n", $header);
	}

	public function onRPLoadCSS()
	{
		$document = JFactory::getDocument();
		$document->addStyleSheet( 'media/com_raidplanner/css/raidplanner_wow.css' );
		
		return true;
	}

}