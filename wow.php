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
	
	private function getAccessToken() {
		$client_id = $this->rp_params['client_id'];
		$client_secret = $this->rp_params['client_secret'];
		$region = $this->rp_params['guild_region'];

		$url = "https://" . $region . ".battle.net/oauth/token?grant_type=client_credentials&client_id=" . $client_id . "&client_secret=" . $client_secret;
		$token = json_decode( RaidPlannerHelper::downloadData( $url ) ,true );

		return $token['access_token'];
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
		$access_token = $this->getAccessToken();

		/* load database ids race array */
		$races = array();
		$query = "SELECT race_id, race_name FROM #__raidplanner_race";
		$db->setQuery( $query );
		$tmp1 = $db->loadAssocList( 'race_name' );

		$url = "https://" . $region . ".api.blizzard.com/data/wow/playable-race/index?namespace=static-" . $region . "&locale=en_GB&access_token=" . $access_token;
		$tmp = json_decode( RaidPlannerHelper::downloadData( $url ) ,true );

		foreach ($tmp['races'] as $race) {
			$races[ $race['id'] ] = $tmp1[ $race['name'] ]['race_id'];
		}
		
		/* load database ids race array */
		$classes = array();
		$query = "SELECT class_id, class_name FROM #__raidplanner_class";
		$db->setQuery( $query );
		$tmp1 = $db->loadAssocList( 'class_name' );

		$url = "https://" . $region . ".api.blizzard.com/data/wow/playable-class/index?namespace=static-" . $region . "&locale=en_GB&access_token=" . $access_token;
		$tmp = json_decode( RaidPlannerHelper::downloadData( $url ) ,true );

		foreach ($tmp['classes'] as $class) {
			$classes[ $class['id'] ] = $tmp1[ $class['name'] ]['class_id'];
		}

		$url = "https://" . $region . ".api.blizzard.com/data/wow/guild/";
		$url .= rawurlencode( strtolower( $realm) ) . "/";
		$url .= rawurlencode( strtolower( $this->guild_name ) );
		$url = $url . "?namespace=profile-" . $region . "&locale=en_GB&access_token=" . $access_token;
		$guild_data = json_decode( RaidPlannerHelper::downloadData( $url ) );


		$url = "https://" . $region . ".api.blizzard.com/data/wow/guild/";
		$url .= rawurlencode( strtolower( $realm) ) . "/";
		$url .= rawurlencode( strtolower( $this->guild_name ) );
		$url = $url . "/roster?namespace=profile-" . $region . "&locale=en_GB&access_token=" . $access_token;
		$data = json_decode( RaidPlannerHelper::downloadData( $url ) );
		if (function_exists('json_last_error')) {
			if (json_last_error() != JSON_ERROR_NONE)
			{
				JError::raiseWarning('100','ArmorySync data decoding error');
				return null;
			}
		}

		if (($this->guild_name == @$guild_data->name) && ($guild_data->name!=''))
		{
			$params = array(
				'achievementPoints' => $guild_data->achievementPoints,
				'side'		=> ($guild_data->faction->type=='HORDER')?"Horde":"Alliance",
				'emblem'	=> get_object_vars( $guild_data->crest->emblem ),
				'link'		=> "http://" . $region . ".battle.net/wow/guild/" . rawurlencode($realm) . "/" . rawurlencode($data->name) ."/",
				'char_link'	=> "http://" . $region . ".battle.net/wow/character/%s/%s/advanced",
				'guild_realm'	=>	$guild_data->realm->name,
				'guild_region'	=>	$region,
				'guild_level'	=>	$data->level,
				'client_id'		=>	$this->rp_params['client_id'],
				'client_secret'	=>	$this->rp_params['client_secret']
			);

			$this->rp_params = array_merge( $this->rp_params, $params );
			
			$query = "UPDATE #__raidplanner_guild SET
							guild_name=".$db->Quote($guild_data->name).",
							params=".$db->Quote(json_encode($params)).",
							lastSync=NOW()
							WHERE guild_id=".intval($this->guild_id);
			$db->setQuery($query);
			$db->query();
			
			/* if we atleast one member in listed */
			if ( (is_array($data->members)) && (count($data->members) > 0) && ($data->members[0]) && ($data->members[0]->character)  && ($data->members[0]->character->name != '') ) {	
				/* detach characters from guild */
				$query = "UPDATE #__raidplanner_character SET guild_id=-5 WHERE guild_id=".intval($this->guild_id)."";
				$db->setQuery($query);
				$db->query();

				/* decrease counter by one in case of previously detached characters */
				$query = "UPDATE #__raidplanner_character SET guild_id=guild_id+1 WHERE guild_id<0";
				$db->setQuery($query);
				$db->query();

				foreach($data->members as $member)
				{
					// append Realm to character name if not the same as guild realm.
					$name = $member->character->name;
					if ( strtoupper($member->character->realm->slug) != strtoupper($guild_data->realm->slug) ) {
						$name = $name . "-" . $member->character->realm->slug;
					}
					// check if character exists
					$query = "SELECT character_id FROM #__raidplanner_character WHERE char_name LIKE BINARY ".$db->Quote($name)."";
					$db->setQuery($query);
					$char_id = $db->loadResult();
					// not found insert it
					if (!$char_id) {
						$query="INSERT INTO #__raidplanner_character SET char_name=".$db->Quote($name)."";
						$db->setQuery($query);
						$db->query();
						$char_id=$db->insertid();
					}
					$query = "UPDATE #__raidplanner_character SET class_id='" . $classes[ intval($member->character->playable_class->id) ] . "'
																,race_id='" . $races[ intval($member->character->playable_race->id) ] . "'
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
		$realm = $this->rp_params['guild_realm'];
		$name = $char_name;
		if (strpos($char_name, '-') !== false) {
			$parts = explode( '-', $char_name );
			$name = $parts[0];
			$realm = $parts[1];
		}
	
		return sprintf($this->rp_params['char_link'], rawurlencode($realm), rawurlencode($name) ) . '" data-darktip="wow.character:'.$this->rp_params['guild_region'].'.'.$realm.'.'.$name.'(en)" target="_blank';
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