<?php
/**
 * Class Skill
 *
 * @created      01.06.2026
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2026 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace Buildwars\GWTemplateRenderer;

use Buildwars\GWSkillData\SkillDataAwareInterface;
use Buildwars\GWSkillData\SkillDataAwareTrait;
use Buildwars\GWSkillData\SkillDataInterface;
use Closure;
use function array_filter;
use function array_key_exists;
use function array_slice;
use function floor;
use function in_array;
use function intval;
use function is_int;
use function min;
use function pow;
use function preg_replace_callback;
use function round;
use function sprintf;
use const PHP_ROUND_HALF_EVEN;

/**
 * Renders a skill description complete with attribute levels and primary attribute effects
 */
class Skill extends TemplateRenderAbstract implements SkillDataAwareInterface{
	use SkillDataAwareTrait;

	public const ACTIVATION   = 'activation';
	public const RECHARGE     = 'recharge';
	public const ENERGY       = 'energy';
	public const UPKEEP       = 'upkeep';
	public const ADRENALINE   = 'adrenaline';
	public const SACRIFICE    = 'sacrifice';
	public const OVERCAST     = 'overcast';
	public const DESCRIPTION  = 'description';
	public const CONCISE      = 'concise';
	public const PRI_EFFECT   = 'pri_effect';
	public const OTHER_EFFECT = 'other_effect';

	/**
	 * The calculated effects result array
	 */
	protected const EFFECTS = [
		self::ACTIVATION   => 0,
		self::RECHARGE     => 0,
		self::ENERGY       => 0,
		self::UPKEEP       => 0,
		self::ADRENALINE   => 0,
		self::SACRIFICE    => 0,
		self::OVERCAST     => 0,
		self::DESCRIPTION  => '',
		self::CONCISE      => '',
		self::PRI_EFFECT   => '',
		self::OTHER_EFFECT => [],
	];

	/*
	 * Regex patterns for the several progression types and languages
	 *
	 * The named capture groups are:
	 *
	 *   - val0, val15: for skill progressions (attribute level 0 and 15)
	 *   - val: for fixed values, e.g. weapon spells and stances
	 *   - str1, str2: the leftover strings from the capture before (1) and after (2) the value
	 *
	 * A callback for `preg_replace_callback()`should return:
	 *
	 *  (str1 ?? '') . calculated_val . (str2 ?? '')
	 */

	protected const VAL015 = '((?<val0>\d+)[.]+(?<val15>\d+))';
	protected const VAL    = '(?<val>\d+)';

	// Regex for default progression replacement (0...15)
	protected const REGEX_DEFAULT = '/'.self::VAL015.'/i';

	// creature level: "level 0...15"
	protected const REGEX_CREATURE = [
		self::LANG_DE => '/(?<str1>Stufe\s+)'.self::VAL015.'/i',
		self::LANG_EN => '/(?<str1>level\s+)'.self::VAL015.'/i',
	];

	// time progression: "0...15 seconds"
	protected const REGEX_TIME_PROGRESSION = [
		self::LANG_DE => '/'.self::VAL015.'(?<str2>\s+Sekund)/i',
		self::LANG_EN => '/'.self::VAL015.'(?<str2>\s+second)/i',
	];

	// fixed time: "15 seconds"
	protected const REGEX_TIME_FIXED = [
		self::LANG_DE => '/'.self::VAL.'(?<str2>\s+Sekund)/i',
		self::LANG_EN => '/'.self::VAL.'(?<str2>\s+second)/i',
	];

	/** the current skill ID - this remains unchanged after a PvP redirect */
	protected readonly int $id;

	/** the given attribute level for the skill */
	protected int $attributeLevel = 0;
	/** the given primary attribute level */
	protected int $priAttributeLevel = 0;
	/** the context skill bar */
	protected array $contextSkillbar = [];

	/** the data array for the current skill */
	protected array $data = [];
	/** additional (primary) attribute effect info/adjusted values */
	protected array $effects = [];
	/** progression levels for the current skill */
	protected array $progressions = [];
	/** rendered flag to avoid multiple passes */
	protected bool $rendered = false;

	public function __construct(int $id, bool $pvp = false, string $lang = self::LANG_EN){
		parent::__construct($pvp, $lang);

		$this->setSkillDataLanguage($this->lang);

		$this->id   = $id;
		$this->data = $this->skillData->get($this->id, $this->pvp);

		foreach(static::EFFECTS as $k => $v){
			$this->effects[$k] = ($this->data[$k] ?? $v);
		}
	}

	/**
	 * Sets an optional skill bar to help determine skills that influence the stats
	 */
	public function setContextSkillbar(array $skillbar):static{
		$this->contextSkillbar = array_slice(array_filter($skillbar, is_int(...)), 0, 8);

		return $this;
	}

	/**
	 * Adds a skill description that has all variable values calculated with the given attribute levels, and for the given primary class.
	 *
	 * The returned array is similar to that from `SkillDataInterface::get()` with 2 additional keys: `effects` and `progressions`.
	 */
	public function getDescription(int|null $attributeLevel = null, int|null $priAttributeLevel = null):array{

		if($this->rendered){
			return $this->data;
		}

		$prof = SkillDataInterface::PROFESSIONS[$this->pri];

		// get necessary attribute levels (skill attribute and primary attribute of the build)
		$this->attributeLevel    = $this->getAttributeLevel($this->data['attribute'], $attributeLevel);
		$this->priAttributeLevel = $this->getAttributeLevel($prof['pri'], $priAttributeLevel);

		// determine primary attribute effects
		$this->{$prof['name'][self::LANG_EN]}($this->priAttributeLevel);

		// summoned creature health & armor
		$this->progressionReplace(static::REGEX_CREATURE[$this->lang], $this->creatureReplacement(...));

		$this->handleMaxMinions();
		$this->handleDwarvenStability();
		$this->handleMantraOfInscriptions();
		$this->handleSignetOfIllusions();

		// render any remaining progression values
		$this->progressionReplace(static::REGEX_DEFAULT, $this->defaultProgressionReplacement(...));

		$this->data['effects']      = $this->effects;
		$this->data['progressions'] = $this->progressions;

		$this->rendered = true;

		return $this->data;
	}

	/**
	 * Checks whether the current skill type is affected by the current primary attribute
	 */
	protected function isAffectedSkillType(string $effectType):bool{
		return in_array($this->data['type'], (SkillDataMisc::SKILLYTPES_PRI_EFFECTS[$this->pri][$effectType] ?? []), true);
	}

	/**
	 * Checks whether the current skill is affected by an attribute
	 */
	protected function isAffectedSkill(string $effectType):bool{
		return in_array($this->id, (SkillDataMisc::SKILL_EFFECTS[$effectType] ?? []), true);
	}

	/**
	 * Sets the primary attribute effect description with the given values
	 */
	protected function setPriEffectText(string $type, string|int|float ...$values):void{
		$this->effects['pri_effect'] = sprintf(SkillDataMisc::DESC_PRI[$this->pri][$type][$this->lang], ...$values); // phpcs:ignore
	}

	/**
	 * Adds a line to the additional effects array
	 */
	protected function addEffectText(string $text):void{
		// check if the same text hasn't been added yet (may happen in progressionReplace)
		if(!in_array($text, $this->effects['other_effect'], true)){
			$this->effects['other_effect'][] = $text;
		}
	}

	/**
	 * Calculates the value for the given val0-val15 progression for the given attribute and level.
	 *
	 * Creates an optional table in the `progressions` array.
	 */
	protected function getProgressionValue(
		int|string $val0,
		int|string $val15,
		int        $attributeLevel,
		int        $attributeID,
		bool       $table = true,
	):int{
		// values might come in as strings from preg_match()
		$val0  = intval($val0);
		$val15 = intval($val15);

		$max = SkillDataInterface::ATTRIBUTES[$attributeID]['max'];
		$key = sprintf('%s: %s-%s', $max, $val0, $val15);

		// shortcut
		if($table === true && array_key_exists($key, $this->progressions)){
			return $this->progressions[$key][$attributeLevel];
		}

		$progression = (float)(($val15 - $val0) / 15);

		// determine the progression function with respect to PvE atrributes
		$value = $this->getProgressionFunction($attributeID, $progression, $val0);

		// skip progression table creation
		if($table === false){
			return (int)round($value($attributeLevel));
		}

		// collect the progression levels 0-21 for the table
		for($i = 0; $i <= $max; $i++){
			$this->progressions[$key][$i] = (int)round($value($i));
		}

		return $this->progressions[$key][$attributeLevel];
	}

	/**
	 * Returns the progression function for the given title attribute
	 */
	protected function getProgressionFunction(int $attribute, float $progression, int $val0):Closure{
		return match(SkillDataInterface::ATTRIBUTES[$attribute]['max']){
			8       => fn(int $l):float => (min(($l * 4), 15) * $progression + $val0),
			10      => fn(int $l):float => (min(($l * 3), 15) * $progression + $val0),
			12      => fn(int $l):float => (min(floor($l * 2.5), 15) * $progression + $val0),
			default => fn(int $l):float => ($l * $progression + $val0),
		};
	}

	/*
	 * Progression replacement and callbacks
	 */

	protected function progressionReplace(string $regex, Closure $callback):void{
		foreach(['description', 'concise'] as $type){
			$this->effects[$type] = preg_replace_callback($regex, $callback, $this->effects[$type]);
		}
	}

	/**
	 * excludes ambiguous progression values
	 *
	 * @see SkillDataMisc::SKILL_EXCLUDES
	 */
	protected function excludeMatch(array $match, array $exclude):bool{

		// current ID not in set
		if(!isset($exclude[$this->id])){
			return false;
		}

		foreach($exclude as $id => $v){

			if($id !== $this->id){
				continue;
			}

			// single value
			if(isset($match['val']) && in_array($match['val'], $v, true)){
				return true;
			}
			// progression value
			if(isset($match['val15']) && in_array($match['val15'], $v, true)){
				return true;
			}
		}

		return false;
	}

	/**
	 * @see Skill::REGEX_DEFAULT
	 */
	protected function defaultProgressionReplacement(array $match):string{
		$val1 = $this->getProgressionValue($match['val0'], $match['val15'], $this->attributeLevel, $this->data['attribute']);

		return sprintf('<green>%s</green>', $val1);
	}

	/**
	 * @see Skill::REGEX_TIME_FIXED
	 * @see Skill::REGEX_TIME_PROGRESSION
	 */
	protected function weaponSpellReplacement(array $match):string{

		if($this->excludeMatch($match, SkillDataMisc::SKILL_EXCLUDES['weapon_spell'])){
			return $match[0];
		}

		$str1 = ($match['str1'] ?? '');
		$str2 = ($match['str2'] ?? '');

		if(isset($match['val'])){
			$duration = round(intval($match['val']) * (1 + $this->priAttributeLevel * 0.04));

			return sprintf('%s<ritualist>%s</ritualist> (%s)%s', $str1, $duration, $match['val'], $str2);
		}

		$val      = $this->getProgressionValue($match['val0'], $match['val15'], $this->priAttributeLevel, 36);
		$duration = round($val * (1 + $this->priAttributeLevel * 0.04));

		return sprintf('%s<ritualist>%s</ritualist> (<green>%s</green>)%s', $str1, $duration, $val, $str2);
	}

	/**
	 * @see Skill::REGEX_CREATURE
	 */
	protected function creatureReplacement(array $match):string{

		$creatureLevel = $this->getProgressionValue(
			$match['val0'],
			$match['val15'],
			$this->attributeLevel,
			$this->data['attribute'],
		);

		// general creature stats
		$health = ($creatureLevel * 20);
		$armor  = (6 * $creatureLevel + 3); // gww says +2 which one is it???

		// creature is a minion
		if($this->isAffectedSkill('minion')){
			$health += 80;

			$armor = match($this->id){
				84      => (2.84 * $creatureLevel + 3.1), // Bone Fiend
				85      => (2.9 * $creatureLevel + 1.25), // Bone Minions
				default => (3.75 * $creatureLevel + 5),
			};
		}

		$armor = round($armor, 0, PHP_ROUND_HALF_EVEN);

		// primary ritualist, spawning power effect
		// a minion from Malign Intervention (122) is not affected by spawning power
		($this->pri === 8 && $this->id !== 122)
			? $this->setPriEffectText('creature', $health, $armor, round($health * (1 + $this->priAttributeLevel * 0.04)))
			: $this->addEffectText(sprintf(SkillDataMisc::DESC_OTHER_EFFECT['creature'][$this->lang], $health, $armor));

		return sprintf('%s<greeen>%s</green>%s', ($match['str1'] ?? ''), $creatureLevel, ($match['str2'] ?? ''));
	}

	/**
	 * @see Skill::REGEX_TIME_FIXED
	 * @see Skill::REGEX_TIME_PROGRESSION
	 */
	protected function dwarvenStabilityReplacement(array $match):string{

		if($this->excludeMatch($match, SkillDataMisc::SKILL_EXCLUDES['dwarven_stability'])){
			return $match[0];
		}

		$str1 = ($match['str1'] ?? '');
		$str2 = ($match['str2'] ?? '');

		$dwarvenStability = (1 + $this->getProgressionValue(55, 100, $this->getAttributeLevel(107), 107, false) / 100);

		// fixed time
		if(isset($match['val'])){
			return sprintf(
				'%s<green>%s</green> (%s)%s',
				$str1,
				round(intval($match['val']) * $dwarvenStability),
				$match['val'],
				$str2,
			);
		}

		$val = $this->getProgressionValue(
			$match['val0'],
			$match['val15'],
			$this->attributeLevel,
			$this->data['attribute'],
		);

		return sprintf('%s<green>%s</green> (<green>%s</green>)%s', $str1, round($val * $dwarvenStability), $val, $str2);
	}


	/*
	 * Skill and skill type related transformations
	 */

	/**
	 * minion skill: determine the maximum amount of minions
	 */
	protected function handleMaxMinions():void{

		if(!$this->isAffectedSkill('minion')){
			return;
		}

		$minions = (floor($this->getAttributeLevel(5) / 2) + 2);

		$this->addEffectText(sprintf(SkillDataMisc::DESC_OTHER_EFFECT['max_minions'][$this->lang], $minions));
	}

	/**
	 * the skill is a stance and we have dwarven stability in the skillbar
	 */
	protected function handleDwarvenStability():void{

		if($this->data['type'] !== 29 || !in_array(2423, $this->contextSkillbar, true)){
			return;
		}

		$this->progressionReplace(static::REGEX_TIME_PROGRESSION[$this->lang], $this->dwarvenStabilityReplacement(...));
		$this->progressionReplace(static::REGEX_TIME_FIXED[$this->lang], $this->dwarvenStabilityReplacement(...));

		$text = sprintf(
			SkillDataMisc::DESC_OTHER_EFFECT['dwarven_stability'][$this->lang],
			$this->getProgressionValue(55, 100, $this->getAttributeLevel(107), 107, false),
		);

		$this->addEffectText($text);
	}

	/**
	 * the skill is a signet and we have mantra of inscriptions in the skillbar
	 */
	protected function handleMantraOfInscriptions():void{

		if($this->data['type'] !== 21 || !in_array(15, $this->contextSkillbar, true)){
			return;
		}

		$reduction = $this->getProgressionValue(10, 40, $this->getAttributeLevel(3), 3, false);

		$this->effects['recharge'] = round(
			($this->effects['recharge'] - ($reduction / 100 * $this->effects['recharge'])),
			0,
			PHP_ROUND_HALF_EVEN,
		);

		$this->addEffectText(sprintf(SkillDataMisc::DESC_OTHER_EFFECT['mantra_of_inscriptions'][$this->lang], $reduction));
	}

	/**
	 * symbolic celerity is in the skill bar and the skill is not keystone signet and attribute is not "no attribute"
	 * "not keystone signet (63)" technically means "all signets on the fast cast attribute"
	 */
	protected function handleSymbolicCelerity():void{

		if(!in_array(1340, $this->contextSkillbar, true) || $this->id === 63 || $this->data['attribute'] === 101){
			return;
		}

		$this->handleSkillLevelByOtherAttribute($this->priAttributeLevel, 'symbolic_celerity', 'mesmer');
	}

	/**
	 * the skill type is a spell and Signet of Illusions is in the skillbar and the attribute is not "no attribute"
	 */
	protected function handleSignetOfIllusions():void{

		if(
			!in_array(1346, $this->contextSkillbar, true)
			|| !in_array($this->data['type'], [22, 23, 24, 25, 26, 27, 28, 33, 34], true)
			|| $this->data['attribute'] === 101
		){
			return;
		}

		$this->handleSkillLevelByOtherAttribute($this->getAttributeLevel(1), 'signet_of_illusions', 'mesmer');
	}

	/**
	 * handles skill progression level using a different attribute than the skill attribute
	 */
	protected function handleSkillLevelByOtherAttribute(int $attributeLevel, string $effect, string $profession):void{

		$this->progressionReplace(static::REGEX_DEFAULT, function(array $match) use ($attributeLevel, $profession):string{
			$val1 = $this->getProgressionValue($match['val0'], $match['val15'], $attributeLevel, 0);
			$val2 = $this->getProgressionValue($match['val0'], $match['val15'], $this->attributeLevel, $this->data['attribute']);

			return sprintf('<%3$s>%1$s</%3$s> (<green>%2$s</green>)', $val1, $val2, $profession);
		});

		$this->addEffectText(SkillDataMisc::DESC_OTHER_EFFECT[$effect][$this->lang]);
	}


	/*
	 * Profession related transformations
	 */

	protected function none(int $priLevel):void{
		$this->setPriEffectText('default');
	}

	protected function Warrior(int $priLevel):void{

		$this->isAffectedSkillType('strength')
			? $this->setPriEffectText('self', $priLevel)
			: $this->setPriEffectText('default', $priLevel);
	}

	protected function Ranger(int $priLevel):void{
		$calc = ($priLevel * 4);
		$this->setPriEffectText('default', $calc);

		if($this->data['profession'] === 2 || $this->isAffectedSkillType('expertise') || $this->isAffectedSkill('touch')){
			$this->effects['energy'] = round(($this->effects['energy'] * (1 - 0.04 * $priLevel)));

			$this->setPriEffectText('self', $calc);
		}
	}

	protected function Monk(int $priLevel):void{

		$type = match(true){
			$this->isAffectedSkill('df_target') => 'target',
			$this->isAffectedSkill('df_add')    => 'add',
			$this->isAffectedSkill('df_self')   => 'self',
			default                             => 'default',
		};

		$this->setPriEffectText($type, round($priLevel * 3.2));
	}

	protected function Necromancer(int $priLevel):void{
		$this->setPriEffectText('default', $priLevel);
	}

	protected function Mesmer(int $priLevel):void{
		// signet activation and spell recharge
		$calc1 = (100 * (1 - ($priLevel * 0.03)));
		// spell activation
		$calc2 = (100 * pow(2, (($priLevel * -1) / 15)));

		$calc1r = round($calc1);
		$calc2r = round($calc2);

		// just start with the generic primary attribute info
		$this->setPriEffectText('default', $calc1r, $calc2r);

		// skill type = signet
		if($this->data['type'] === 21){

			// reduced activation time info
			if($this->data['profession'] === 5 || $this->data['activation'] >= 2){
				$this->effects['activation'] = round(($this->effects['activation'] / 100 * $calc1), 3);

				$this->setPriEffectText('signet', $calc1r);
			}

			$this->handleSymbolicCelerity();
		}
		// skill type = spell
		elseif($this->isAffectedSkillType('fastcast')){

			// reduced activation time info
			if($this->data['profession'] === 5 || $this->data['activation'] >= 2){
				$this->effects['activation'] = round(($this->effects['activation'] / 100 * $calc2), 3);

				$this->setPriEffectText('spell1', $calc2r);
			}

			// Mesmer spell recharge in PvE
			if(!$this->pvp && $this->data['profession'] === 5 && $this->isAffectedSkillType('fastcast_recharge')){
				$this->effects['recharge'] = round(($this->effects['recharge'] / 100 * $calc1), 3);

				$this->setPriEffectText('spell2', $calc2r, $calc1r);
			}

		}

	}

	protected function Elementalist(int $priLevel):void{
		$this->setPriEffectText('default', ($priLevel * 3));
	}

	protected function Assassin(int $priLevel):void{
		$this->setPriEffectText('default', $priLevel, floor(($priLevel + 2) / 5));
	}

	protected function Ritualist(int $priLevel):void{
		$this->setPriEffectText('default', ($priLevel * 4));

		// weapon spell effects
		if($this->isAffectedSkillType('spawning_weaponspell')){
			$this->progressionReplace(static::REGEX_TIME_PROGRESSION[$this->lang], $this->weaponSpellReplacement(...));
			$this->progressionReplace(static::REGEX_TIME_FIXED[$this->lang], $this->weaponSpellReplacement(...));

			$this->setPriEffectText('weaponspell', ($priLevel * 4));
		}
	}

	protected function Paragon(int $priLevel):void{
		$val = floor($priLevel / 2);

		$this->setPriEffectText('default', $val);

		if($this->isAffectedSkillType('leadership')){
			$this->setPriEffectText('self', $val);
		}
	}

	protected function Dervish(int $priLevel):void{
		$val = floor($priLevel * 4);

		$this->setPriEffectText('default', $val, $priLevel);

		if($this->isAffectedSkillType('mysticism') && $this->data['profession'] === $this->pri){
			$this->setPriEffectText('self', $val, $priLevel);
		}
	}

}
