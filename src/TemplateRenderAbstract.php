<?php
/**
 * Class TemplateRenderAbstract
 *
 * @created      10.06.2026
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2026 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace Buildwars\GWTemplateRenderer;

use Buildwars\GWSkillData\SkillDataInterface;
use InvalidArgumentException;
use function array_key_exists;
use function implode;
use function is_int;
use function max;
use function min;
use function sprintf;

abstract class TemplateRenderAbstract extends RenderAbstract{

	/** the primary profession */
	protected int $pri = 0;
	/** the secondary profession */
	protected int $sec = 0;
	/** the given attributes */
	protected array $attributes = [];
	/** overall attribute bonus */
	protected int $attributeBonus = 0;
	/** bonus values for specific attributes, map of attribute ID => bonus value */
	protected array $attributeSpecificBonus = [];

	/**
	 * Sets the primary and secondary profession
	 */
	public function setProfessions(int $pri, int $sec = 0):static{

		// invalid primary profession
		if(!isset(SkillDataInterface::PROFESSIONS[$pri])){
			$pri = 0;
		}

		// invalid secondary profession or secondary profession is same as primary
		if(!isset(SkillDataInterface::PROFESSIONS[$sec]) || $sec === $pri){
			$sec = 0;
		}

		$this->pri = $pri;
		$this->sec = $sec;

		return $this;
	}

	/**
	 * Sets the attributes for the given skillbar
	 *
	 * (may include PvE attributes, levels are clamped to 0-30, PvE attributes are clamped to their maximum title ranks)
	 *
	 * @see \Buildwars\GWSkillData\SkillDataInterface::ATTRIBUTES
	 */
	public function setAttributes(array $attributes):static{
		$this->attributes = [];

		foreach($attributes as $attribute => $value){

			// invalid attribute
			if(!array_key_exists($attribute, SkillDataInterface::ATTRIBUTES)){
				continue;
			}

			// invalid value
			if(!is_int($value)){
				continue;
			}

			$this->attributes[$attribute] = $this->clampAttributeLevel($attribute, $value);
		}

		return $this;
	}

	/**
	 * Sets an additional bonus to all attributes, e.g. from Grail of Might, Gold Eggs, Candy Corn, etc...
	 *
	 * value clamped to 0-10
	 */
	public function setAttributeBonus(int $attributeBonus):static{
		$this->attributeBonus = max(0, min($attributeBonus, 10));

		return $this;
	}

	/**
	 * Sets an additional bonus for a specific attribute, excluding PvE ranks, e.g. from weapon modifiers or certain skills
	 *
	 * value clamped to 0-20
	 *
	 * @throws \InvalidArgumentException
	 */
	public function setAttributeSpecificBonus(int $attributeID, int $attributeBonus):static{

		// PvE title ranks can't have bonuses - we'll just ignore them
		if($attributeID > 100){
			return $this;
		}

		if(!array_key_exists($attributeID, SkillDataInterface::ATTRIBUTES)){
			throw new InvalidArgumentException('invalid attribute');
		}

		$this->attributeSpecificBonus[$attributeID] = max(0, min($attributeBonus, 20));

		return $this;
	}

	/**
	 * Clamps the attribute level
	 */
	protected function clampAttributeLevel(int $attributeID, int $attributeLevel, int|null $max = null):int{
		// the internal maximum attribute level for player characters is 20-21, monsters are capped at 30
		// fast cast levels > 33 result in negative activation & recharge for mesmer - THE CHRONOMANCER IS REAL
		$attr_max = (SkillDataInterface::ATTRIBUTES[$attributeID]['max'] ?? 0);
		$max      = min(($max ?? $attr_max), 30);

		// we'll clamp the PvE attributes to their respectime max title ranks
		if($attributeID > 100){
			$max = $attr_max;
		}

		return max(0, min($attributeLevel, $max));
	}

	/**
	 * Returns the level for the given attribute, including bonuses
	 */
	protected function getAttributeLevel(int $attributeID, int|null $overrideLevel = null):int{
		$level = ($overrideLevel ?? $this->attributes[$attributeID] ?? 0);

		// add the attribute bonus here, excluding PvE attributes
		if($attributeID < 100){
			$level += $this->attributeBonus;
		}

		if(array_key_exists($attributeID, $this->attributeSpecificBonus)){
			$level += $this->attributeSpecificBonus[$attributeID];
		}

		return $this->clampAttributeLevel($attributeID, $level);
	}

	/*
	 * HTML related methods
	 */

	protected function getAttributeString():string{
		$attributes = [];

		foreach($this->attributes as $id => $val){
			// @todo: calulated attributes in team
			$attributes[] = sprintf('%s:%s', $id, $val);
		}

		return implode(',', $attributes);
	}

}
