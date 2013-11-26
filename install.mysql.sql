INSERT IGNORE INTO `#__raidplanner_class` (`class_color`, `class_name`, `class_css`) SELECT '#C79C6E', 'Warrior', 'class_wow_warrior' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_class` WHERE `class_name`='Warrior')=0;
INSERT IGNORE INTO `#__raidplanner_class` (`class_color`, `class_name`, `class_css`) SELECT '#F58CBA', 'Paladin', 'class_wow_paladin' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_class` WHERE `class_name`='Paladin')=0;
INSERT IGNORE INTO `#__raidplanner_class` (`class_color`, `class_name`, `class_css`) SELECT '#ABD473', 'Hunter', 'class_wow_hunter' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_class` WHERE `class_name`='Hunter')=0;
INSERT IGNORE INTO `#__raidplanner_class` (`class_color`, `class_name`, `class_css`) SELECT '#A49900', 'Rogue', 'class_wow_rogue' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_class` WHERE `class_name`='Rogue')=0;
INSERT IGNORE INTO `#__raidplanner_class` (`class_color`, `class_name`, `class_css`) SELECT '#8CA5A3', 'Priest', 'class_wow_priest' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_class` WHERE `class_name`='Priest')=0;
INSERT IGNORE INTO `#__raidplanner_class` (`class_color`, `class_name`, `class_css`) SELECT '#C41F3B', 'Death Knight', 'class_wow_death_knight' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_class` WHERE `class_name`='Death Knight')=0;
INSERT IGNORE INTO `#__raidplanner_class` (`class_color`, `class_name`, `class_css`) SELECT '#0070DE', 'Shaman', 'class_wow_shaman' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_class` WHERE `class_name`='Shaman')=0;
INSERT IGNORE INTO `#__raidplanner_class` (`class_color`, `class_name`, `class_css`) SELECT '#69CCF0', 'Mage', 'class_wow_mage' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_class` WHERE `class_name`='Mage')=0;
INSERT IGNORE INTO `#__raidplanner_class` (`class_color`, `class_name`, `class_css`) SELECT '#9482C9', 'Warlock', 'class_wow_warlock' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_class` WHERE `class_name`='Warlock')=0;
INSERT IGNORE INTO `#__raidplanner_class` (`class_color`, `class_name`, `class_css`) SELECT '#FF7D0A', 'Druid', 'class_wow_druid' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_class` WHERE `class_name`='Druid')=0;
INSERT IGNORE INTO `#__raidplanner_class` (`class_color`, `class_name`, `class_css`) SELECT '#558A84', 'Monk', 'class_wow_monk' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_class` WHERE `class_name`='Monk')=0;

INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Human' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Human')=0;
INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Orc' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Orc')=0;
INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Dwarf' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Dwarf')=0;
INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Night Elf' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Night Elf')=0;
INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Undead' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Undead')=0;
INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Tauren' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Tauren')=0;
INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Gnome' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Gnome')=0;
INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Troll' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Troll')=0;
INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Goblin' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Goblin')=0;
INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Blood Elf' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Blood Elf')=0;
INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Draenei' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Draenei')=0;
INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Worgen' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Worgen')=0;
INSERT IGNORE INTO `#__raidplanner_race` (`race_name`) SELECT 'Pandaren' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_race` WHERE `race_name`='Pandaren')=0;

INSERT IGNORE INTO `#__raidplanner_role` (`role_name`, `body_color`, `header_color`, `font_color`, `icon_name`) SELECT 'Tank',			'white',	'#93232b',	'black',	'role_tank.png' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_role` WHERE `role_name`='Tank')=0;
INSERT IGNORE INTO `#__raidplanner_role` (`role_name`, `body_color`, `header_color`, `font_color`, `icon_name`) SELECT 'Melee DPS',	'white',	'#a06729',	'black',	'role_melee_dps.png' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_role` WHERE `role_name`='Melee DPS')=0;
INSERT IGNORE INTO `#__raidplanner_role` (`role_name`, `body_color`, `header_color`, `font_color`, `icon_name`) SELECT 'Ranged DPS',	'white',	'#2983a0',	'black',	'role_ranged_dps.png' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_role` WHERE `role_name`='Ranged DPS')=0;
INSERT IGNORE INTO `#__raidplanner_role` (`role_name`, `body_color`, `header_color`, `font_color`, `icon_name`) SELECT 'Healer',		'white',	'#6aa64d',	'black',	'role_heal.png' FROM DUAL WHERE (SELECT COUNT(*) FROM `#__raidplanner_role` WHERE `role_name`='Healer')=0;