<?php
/**
 * Class SkillDescriptionTest
 *
 * @created      01.06.2024
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2024 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace Buildwars\GWTemplateRendererTest;

use Buildwars\GWTemplateRenderer\SkillDataMisc;
use Buildwars\GWTemplateRenderer\Skill;
use BuildWars\GWTemplates\SkillTemplate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use function implode;

/**
 * Tests basic functions of the SkillDescription class
 */
final class SkillDescriptionTest extends TestCase{

	private const pve_titles = [102 => 10, 103 => 8, 104 => 12, 105 => 12, 106 => 10, 107 => 10, 108 => 10, 109 => 10];

	private Skill         $skill;
	private SkillTemplate $skillTemplate;

	protected function setUp():void{
		$this->skillTemplate = new SkillTemplate;
	}

	private function getReflectionPropertyValue(string $property):mixed{
		return (new ReflectionObject($this->skill))->getProperty($property)->getValue($this->skill);
	}

	private function invokeReflectionMethod(string $method, array $args = []):mixed{
		return (new ReflectionObject($this->skill))->getMethod($method)->invokeArgs($this->skill, $args);
	}

	public static function professionProvider():array{
		return [
			'invalid values' => [42, 69, 0, 0],
			'same values'    => [5, 5, 5, 0],
			'valid'          => [8, 1, 8, 1],
		];
	}

	#[Test]
	#[DataProvider('professionProvider')]
	public function setProfessions(int $pri, int $sec, int $expectedPri, int $expectedSec):void{
		$this->skill = new Skill(0);

		$this->skill->setProfessions($pri, $sec);

		$this::assertSame($expectedPri, $this->getReflectionPropertyValue('pri'));
		$this::assertSame($expectedSec, $this->getReflectionPropertyValue('sec'));
	}

	#[Test]
	public function setContextSkillbar():void{
		$actual   = [0, '1', null, 5, [], 345, 678, '0', 0, 1234, 42, 69, 0];
		$expected = [0, 5, 345, 678, 0, 1234, 42, 69];

		$this->skill = new Skill(0);
		$this->skill->setContextSkillbar($actual);

		$this::assertSame($expected, $this->getReflectionPropertyValue('contextSkillbar'));
	}

	#[Test]
	public function setAttributes():void{
		$actual   = [0 => 42, 1 => -1, 5 => 'aaa', 69 => 420, 101 => 666, 102 => 20, 103 => 20, 104 => 20, 109 => 20];
		$expected = [0 => 21, 1 => 0, 101 => 0, 102 => 10, 103 => 8, 104 => 12, 109 => 10];

		$this->skill = new Skill(0);
		$this->skill->setAttributes($actual);

		$this::assertSame($expected, $this->getReflectionPropertyValue('attributes'));
	}

	#[Test]
	public function getAttributeLevel():void{
		$this->skill = new Skill(0);
		$this->skill->setAttributes([0 => 10, 2 => 12, 3 => 8, 103 => 8]);

		// invalid attribute
		$this::assertSame(0, $this->invokeReflectionMethod('getAttributeLevel', [99]));
		// given attribute
		$this::assertSame(10, $this->invokeReflectionMethod('getAttributeLevel', [0]));

		// set attribute bonus
		$this->skill->setAttributeBonus(12); // will be clamped at 10

		$this::assertSame(20, $this->invokeReflectionMethod('getAttributeLevel', [0]));
		// clamped at internal max level
		$this::assertSame(21, $this->invokeReflectionMethod('getAttributeLevel', [2]));
		$this::assertSame(18, $this->invokeReflectionMethod('getAttributeLevel', [3]));
		// no bonus added to the PvE attribute
		$this::assertSame(8, $this->invokeReflectionMethod('getAttributeLevel', [103]));

		// override level (max level clamped)
		$this::assertSame(21, $this->invokeReflectionMethod('getAttributeLevel', [0, 42]));
		$this::assertSame(8, $this->invokeReflectionMethod('getAttributeLevel', [103, 69]));
	}

	public static function progressionValueProvider():array{
		return [
			// standard progression -> https://wiki.guildwars.com/wiki/Ineptitude
			'standard'     => [30, 135, 1, [0 => 30, 12 => 114, 15 => 135, 21 => 177]],
			// PvE attribute: luxon/kurzick -> https://wiki.guildwars.com/wiki/Summon_Spirits
			'factions'     => [60, 100, 104, [0 => 60, 3 => 79, 6 => 100, 12 => 100]],
			// PvE attribute: lightbringer -> https://wiki.guildwars.com/wiki/Lightbringer_Signet
			'lightbringer' => [16, 24, 103, [0 => 16, 3 => 22, 4 => 24, 8 => 24]],
			// PvE attribute: sunspear -> https://wiki.guildwars.com/wiki/Vampirism
			'pve1'         => [75, 150, 102, [0 => 75, 3 => 120, 5 => 150, 10 => 150]],
			// PvE attribute: eotn -> https://wiki.guildwars.com/wiki/Dwarven_Stability
			'pve2'         => [55, 100, 107, [0 => 55, 3 => 82, 5 => 100, 10 => 100]],
		];
	}

	#[Test]
	#[DataProvider('progressionValueProvider')]
	public function getProgressionValue(int $val0, int $val15, int $attribute, array $expected):void{
		$this->skill = new Skill(0);

		foreach($expected as $level => $expectedValue){
			$value = $this->invokeReflectionMethod('getProgressionValue', [$val0, $val15, $level, $attribute]);

			$this::assertSame($expectedValue, $value);
		}
	}

	public static function effectDescriptionProvider():array{
		return [
			// primary Mesmer, Symbolic Celerity and Mantra of Inscriptions in the bar
			'Keystone Signet Mesmer' => [
				'OQdCA8wDP9Hw2yzmudAc+BA',
				[
					1340 => [
						'This spell has <green>57</green>% of its base activation time and <green>64</green>% recharge in PvE.',
					],
					63   => [
						'This signet has <green>64</green>% of its base activation time.',
					],
					15   => [
						'Spells have <green>64</green>%, signets <green>57</green>% of their activation time',
						'Mesmer spells have <green>57</green>% of their recharge time in PvE.',
					],
					876  => [
						SkillDataMisc::DESC_OTHER_EFFECT['symbolic_celerity']['en'],
						'Mantra of Inscriptions decreases the recharge of this signet by <green>34</green>%.',
					],
					1648 => [
						'Mantra of Inscriptions decreases the recharge of this signet by <green>34</green>%.',
					],
				],
				[
					1648 => [SkillDataMisc::DESC_OTHER_EFFECT['symbolic_celerity']['en']],
				],
			],
			// Signet of Illusions with several spells
			'Signet of Illusions' => [
				'OQNDATwDPCVZySAEESoH5D8B',
				[
					306 => [
						'All of target\'s skills are disabled for <mesmer>4</mesmer> (<green>10</green>) seconds.',
						SkillDataMisc::DESC_OTHER_EFFECT['signet_of_illusions']['en'],
					],
				],
				[
					75 => [SkillDataMisc::DESC_OTHER_EFFECT['signet_of_illusions']['en']],
				],
			],
			// primary Ritualist with minions, spawning power affects minion health
			'Ritualist MM' => [
				'OASiQyF8UFmCyB9AV4ouUBa',
				[
					85  => [
						'This creature has <ritualist>414</ritualist> (<green>280</green>) health and <green>30</green> armor.',
					],
					122 => [
						// a minion from Malign Intervention (122) is not affected by spawning power
						'Animated creatures have +<green>48</green>% maximum health and weapons spells last <green>48</green>% longer.',
						'This creature has <green>360</green> health and <green>58</green> armor.',
						'You can control a maximum of <green>8</green> minions.',
					],
					832 => [
						'This creature has <ritualist>740</ritualist> (<green>500</green>) health and <green>84</green> armor.',
					],
				],
				[
					122 => [
						'This creature has <ritualist>533</ritualist> (<green>360</green>) health and <green>43</green> armor',
					],
				],
			],
			// primary Ritualist with weapon spells, spawning power affects weapon duration
			'Ritualist Weapon Spells' => [
				'OAOiQyiMtxoxgxkBWtdttKWGC',
				[
					// variable duration
					2073 => [
						'For <ritualist>19</ritualist> (<green>13</green>) seconds, you attack 25% faster.',
						'This weapon spell lasts <green>48</green>% longer.',
					],
					// pve skill, fixed duration
					2219 => [
						'For <ritualist>30</ritualist> (20) seconds, target other ally\'s weapon strikes for +<green>20</green> damage and has a <green>40</green>% chance to cause knock down.',
						'This weapon spell lasts <green>48</green>% longer.',
					],
				],
				[],
			],
			// primary Ranger with secondary profession melee attacks, touch skill, ritual etc.
			'Ranger expertise' => [
				'OgcUc5O7lvQOMMMHMSMNQTG2lj1',
				[
					// Jagged Strike
					782  => ['This skill costs <green>48</green>% less energy.'],
					// Iron Palm
					786  => ['This skill costs <green>48</green>% less energy.'],
					// Dark Escape
					1037 => ['Ranger skills, attack skills, touch skills and rituals cost <green>48</green>% less energy.'],
					// Winds (PvE)
					2422 => ['This skill costs <green>48</green>% less energy.'],
					// Together as One (Ranger shout)
					3427 => ['This skill costs <green>48</green>% less energy.'],
				],
				[],
			],

/*
			'' => [
				'',
				[

				],
				[

				],
			],
*/

		];
	}

	#[Test]
	#[DataProvider('effectDescriptionProvider')]
	public function getEffectDescription(string $template, array $expectedEffects, array $notExpectedEffects):void{
		$build = $this->skillTemplate->decode($template);

		foreach($build['skills'] as $skillID){
			$this->skill = new Skill($skillID);

			$this->skill
				->setProfessions($build['prof_pri'], $build['prof_sec'])
				->setContextSkillbar($build['skills'])
				->setAttributes(($build['attributes'] + self::pve_titles));

			$effects       = $this->skill->getDescription()['effects'];
			$actualEffects = $effects['description'].' '.$effects['pri_effect'].' '.implode(' ', $effects['other_effect']);

			if(isset($expectedEffects[$skillID])){
				foreach($expectedEffects[$skillID] as $expectedEffect){
					$this::assertStringContainsString($expectedEffect, $actualEffects);
				}
			}

			if(isset($notExpectedEffects[$skillID])){
				foreach($notExpectedEffects[$skillID] as $notExpectedEffect){
					$this::assertStringNotContainsString($notExpectedEffect, $actualEffects);
				}
			}

#			var_dump($effects);
		}


	}

}
