<?php
/**
 * Class SkillDataMisc
 *
 * @created      16.06.2024
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2024 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace Buildwars\GWTemplateRenderer;

final class SkillDataMisc{

	/**
	 * Primary attribute effect descriptions
	 */
	public const DESC_PRI = [
		0  => [
			'default' => [
				'de' => 'Keine Primärklasse angegeben',
				'en' => 'Primary profession not specified.',
			],
		],
		1  => [
			'default' => [
				'de' => 'Angriffsfertigkeiten haben +<green>%s</green>%% Rüstungsdurchdringung.',
				'en' => 'Attack skills have +<green>%s</green>%% armor penetration.',
			],
			'self'    => [
				'de' => 'Diese Angriffsfertigkeit hat +<green>%s</green>%% Rüstungsdurchdringung.',
				'en' => 'This attack skill has +<green>%s</green>%% armor penetration.',
			],
		],
		2  => [
			'default'   => [
				'de' => 'Waldläufer-, Angriffs- und Berührungsfertigkeiten, sowie Rituale kosten <green>%s</green>%% weniger Energie.',
				'en' => 'Ranger skills, attack skills, touch skills and rituals cost <green>%s</green>%% less energy.',
			],
			'self' => [
				'de' => 'Diese Fertigkeit kostet <green>%s</green>%% weniger Energie.',
				'en' => 'This skill costs <green>%s</green>%% less energy.',
			],
		],
		3  => [
			'default' => [
				'de' => 'Mönch-Zauber geben <green>%s</green> Lebenspunkte.',
				'en' => 'Monk spells give <green>%s</green> health.',
			],
			'add'     => [
				'de' => 'Das Ziel erhält zusätzlich <green>%s</green> Lebenspunkte.',
				'en' => 'Target ally gains an additional <green>%s</green> health.',
			],
			'self'    => [
				'de' => 'Ihr erhaltet <green>%s</green> Lebenspunkte.',
				'en' => 'You gain <green>%s</green> health.',
			],
			'target'  => [
				'de' => 'Das Ziel erhält <green>%s</green> Lebenspunkte.',
				'en' => 'Target ally gains <green>%s</green> health.',
			],
		],
		4  => [
			'default' => [
				'de' => 'Jedesmal, wenn eine Kreatur stirbt, die kein Geist ist, erhaltet Ihr <green>%s</green> Energie (max. 3x in 15s).',
				'en' => 'Whenever a non-spirit creature dies, You gain <green>%s</green> energy (max. 3x in 15s).',
			],
		],
		5  => [
			'default' => [
				'de' => 'Zauber haben noch <green>%1$s</green>%%, Siegel <green>%2$s</green>%% ihrer Aktivierungszeit (Nicht-Mesmer-Fertigkeiten ab 2 Sekunden Aktivierung). Mesmer-Zauber haben im PvE noch <green>%2$s</green>%% ihrer Aufladezeit.',
				'en' => 'Spells have <green>%1$s</green>%%, signets <green>%2$s</green>%% of their activation time (non Mesmer skills with activation of 2 seconds or greater). Mesmer spells have <green>%2$s</green>%% of their recharge time in PvE.',
			],
			'signet'  => [
				'de' => 'Dieses Siegel hat noch <green>%s</green>%% seiner Aktivierungszeit.',
				'en' => 'This signet has <green>%s</green>%% of its base activation time.',
			],
			'spell1'  => [
				'de' => 'Dieser Zauber hat noch <green>%s</green>%% seiner Aktivierungszeit.',
				'en' => 'This spell has <green>%s</green>%% of its base activation time.',
			],
			'spell2'  => [
				'de' => 'Dieser Zauber hat noch <green>%1$s</green>%% seiner Aktivierungszeit und <green>%2$s</green>%% seiner Aufladezeit im PvE.',
				'en' => 'This spell has <green>%1$s</green>%% of its base activation time and <green>%2$s</green>%% recharge in PvE.',
			],
		],
		6  => [
			'default' => [
				'de' => 'Eure maximalen Energiepunkte erhöhen sich um +<green>%s</green>.',
				'en' => 'You gain +<green>%s</green> maximum energy.',
			],
		],
		7  => [
			'default' => [
				'de' => 'Die Chance auf kritische Treffer ist um <green>%1$s</green>%% erhöht. Ihr erhaltet <green>%2$s</green> Energie für jeden kritischen Treffer.',
				'en' => 'Critical hit chance is increased by <green>%1$s</green>%%. You gain <green>%2$s</green> energy for each critical hit.',
			],
		],
		8  => [
			'default'     => [
				'de' => 'Belebte Kreaturen haben +<green>%1$s</green>%% maximale Lebenspunkte und Waffenzauber halten <green>%1$s</green>%% länger an.',
				'en' => 'Animated creatures have +<green>%1$s</green>%% maximum health and weapons spells last <green>%1$s</green>%% longer.',
			],
			'creature'    => [
				'de' => 'Diese Kreatur hat <ritualist>%3$s</ritualist> (<green>%1$s</green>) Lebenspunkte und <green>%2$s</green> Rüstung.',
				'en' => 'This creature has <ritualist>%3$s</ritualist> (<green>%1$s</green>) health and <green>%2$s</green> armor.',
			],
			'weaponspell' => [
				'de' => 'Dieser Waffenzauber hält um <green>%s</green>%% länger an.',
				'en' => 'This weapon spell lasts <green>%s</green>%% longer.',
			],
		],
		9  => [
			'default' => [
				'de' => 'Ihr erhaltet 2 Energiepunkte für jeden von Euren Anfeuerungsrufen und Schreien betroffenen Verbündeten (max. <green>%s</green>).',
				'en' => 'You gain 2 energy for each ally affected by your chants and shouts (max. <green>%s</green>).',
			],
			'self'    => [
				'de' => 'Ihr erhaltet 2 Energiepunkte für jeden von dieser Fertigkeit betroffenen Verbündeten (max. <green>%s</green>).',
				'en' => 'You gain 2 energy for each ally affected by this skill (max. <green>%s</green>).',
			],
		],
		10 => [
			'default' => [
				'de' => 'Derwisch-Verzauberungen kosten <green>%1$s</green>%% weniger Energie und Ihr habt +<green>%2$s</green> Rüstung, während Ihr verzaubert seid.',
				'en' => 'Dervish Enchantments cost <green>%1$s</green>%% less energy and you gain +<green>%2$s</green> armor while enchanted.',
			],
			'self'    => [
				'de' => 'Diese Fertigkeit kostet <green>%1$s</green>%% weniger Energie und Ihr habt +<green>%2$s</green> Rüstung, während Ihr verzaubert seid.',
				'en' => 'This skill costs <green>%1$s</green>%% less energy and you gain +<green>%2$s</green> armor while enchanted.',
			],
		],
	];

	/**
	 * Other effect descriptions
	 */
	public const DESC_OTHER_EFFECT = [
		'max_minions'             => [
			'de' => 'Ihr könnt maximal <green>%s</green> Diener kontrollieren.',
			'en' => 'You can control a maximum of <green>%s</green> minions.',
		],
		'creature'                => [
			'de' => 'Diese Kreatur hat <green>%1$s</green> Lebenspunkte und <green>%2$s</green> Rüstung.',
			'en' => 'This creature has <green>%1$s</green> health and <green>%2$s</green> armor.',
		],
		'mantra_of_inscriptions' => [
			'de' => 'Mantra der Inschriften reduziert die Aufladezeit dieses Siegels um <green>%s</green>%%.',
			'en' => 'Mantra of Inscriptions decreases the recharge of this signet by <green>%s</green>%%.',
		],
		'dwarven_stability' => [
			'de' => 'Zwergenstabilität verlängert die Dauer dieser Haltung um <green>%s</green>%%.',
			'en' => 'Dwarven Stability increases the duration of this stance by <green>%s</green>%%.',
		],
		'signet_of_illusions' => [
			'de' => 'Dieser Zauber benutzt das Illusionsmagie-Attribut, wenn Siegel der Illusionen aktiv ist.',
			'en' => 'This spell uses the Illusion Magic attribute while Signet of Illusions is active.',
		],
		'symbolic_celerity' => [
			'de' => 'Dieses Siegel benutzt das Schnellwirkungs-Attribut wenn Symbolische Schnelligkeit aktiv ist.',
			'en' => 'This signet uses the Fast Casting attribute while Symbolic Celerity is active.',
		],
	];

	/**
	 * Skill types affected by primary attributes
	 */
	public const SKILLYTPES_PRI_EFFECTS = [
		1 => [
			'strength' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 12],
		],
		2 => [
			'expertise' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 18, 19, 31, 32],
		],
		5 => [
			'fastcast'          => [22, 23, 24, 25, 26, 27, 28],
			'fastcast_recharge' => [22, 23, 24],
		],
		8 => [
			'spawning_weaponspell' => [27],
		],
		9 => [
			'leadership' => [13, 20],
		],
		10 => [
			'mysticism' => [23, 33],
		],
	];

	/**
	 * Skills that are affected by (primary) attributes
	 */
	public const SKILL_EFFECTS = [
		// expertise: touch skills
		'touch' => [
			29, 58, 154, 155, 156, 157, 158, 231, 232, 312, 313, 314, 424, 525, 786, 801, 918, 990, 1009, 1045, 1059,
			1077, 1078, 1079, 1095, 1131, 1146, 1155, 1263, 1328, 1401, 1406, 1439, 1447, 1528, 1534, 1545, 1619, 1645,
			1818, 1862, 1894, 2011, 2080, 2081, 2088, 2114, 2129, 2213, 2214, 2215, 2244, 2357, 2375, 2376, 2380, 2385,
			2492, 2501, 2506,
		],
		// divine favor: additional healing
		'df_add' => [
			281, 282, 283, 286, 313, 867, 941, 958, 959, 1120, 1121, 1396, 1686, 1687, 2062,
		],
		// divine favor: target gains health
		'df_target' => [
			241, 242, 243, 244, 245, 246, 248, 249, 250, 254, 255, 258, 259, 260, 261, 262, 263, 266, 267, 269, 270,
			272, 273, 274, 275, 276, 277, 278, 285, 288, 289, 290, 291, 292, 299, 301, 302, 303, 307, 308, 309, 311,
			838, 848, 885, 886, 942, 991, 1114, 1115, 1123, 1126, 1390, 1391, 1392, 1395, 1399, 1400, 1401, 1683,
			1691, 1692, 2003, 2004, 2007, 2061, 2063, 2064, 2065, 2887,
		],
		// divine favor: self targeted
		'df_self' => [
			247, 256, 257, 265, 268, 271, 279, 280, 284, 287, 298, 304, 310, 943, 957, 960, 1113, 1117, 1118, 1119,
			1262, 1393, 1394, 1397, 1684, 1685, 1952, 2005, 2095, 2105, 2857, 2871, 2890,
		],
		// spawning power, death magic: minion skills
		'minion' => [
			83, 84, 85, 114, 122, 805, 832, 1351, 1355,
		],
	];

	/**
	 * match exclusions for ambiguous progression values
	 *
	 *   skill id => "val" or "val15"
	 *
	 * @see Skill::excludeMatch()
	 */
	public const SKILL_EXCLUDES = [
		'dwarven_stability' => [
			373  => ['15'],
			454  => ['5'],
			572  => ['10'],
			1041 => ['10'],
			1209 => ['5'],
			1650 => ['1', '10'],
			2136 => ['6'],
		],
		'weapon_spell' => [
			983  => ['5'],  // weapon of shadow
			2148 => ['20'], // sundering weapon
		],
	];

}
