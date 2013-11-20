DELETE FROM `#__raidplanner_class` WHERE `class_name` IN ('Warrior', 'Paladin', 'Hunter', 'Rogue', 'Priest', 'Death Knight', 'Shaman', 'Mage', 'Warlock', 'Druid', 'Monk');
DELETE FROM `#__raidplanner_race` WHERE `race_name` IN ('Human', 'Orc', 'Dwarf', 'Night Elf', 'Undead', 'Tauren', 'Gnome', 'Troll', 'Goblin', 'Blood Elf', 'Draenei', 'Worgen', 'Pandaren');
DELETE FROM `#__raidplanner_role` WHERE `role_name` IN ('Tank', 'Melee DPS', 'Ranged DPS', 'Healer');
