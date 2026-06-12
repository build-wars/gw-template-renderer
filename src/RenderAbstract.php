<?php
/**
 * Class RenderAbstract
 *
 * @created      11.06.2026
 * @author       smiley <smiley@chillerlan.net>
 * @copyright    2026 smiley
 * @license      MIT
 */
declare(strict_types=1);

namespace Buildwars\GWTemplateRenderer;

use InvalidArgumentException;
use function array_unique;
use function implode;
use function in_array;
use function sprintf;
use function strtolower;
use function trim;

abstract class RenderAbstract{

	public const LANG_DE = 'de';
	public const LANG_EN = 'en';

	public const LANGUAGES = [self::LANG_DE, self::LANG_EN];

	/** PvP redirect */
	protected readonly bool $pvp;
	/** the current language */
	protected readonly string $lang;

	/** HTML dataset values */
	protected array $dataset = [];
	/** CSS classes for the current element (team, build, skill) */
	protected array $cssClass = [];

	public function __construct(bool $pvp = false, string $lang = self::LANG_EN){
		$lang = trim(strtolower($lang));

		if(!in_array($lang, static::LANGUAGES, true)){
			throw new InvalidArgumentException('invalid language');
		}

		$this->pvp  = $pvp;
		$this->lang = $lang;
	}


	/*
	 * HTML related methods
	 */

	protected function addCssClass(string $cssClass):static{
		$this->cssClass[] = trim($cssClass);

		return $this;
	}

	protected function getCssClass():string{
		return implode(' ', array_unique($this->cssClass)); // array_filter...
	}

	protected function addDatasetAttribute(string $name, string $value):static{
		$this->dataset[$name] = $value;

		return $this;
	}

	protected function getDataset():string{
		$dataset = [];

		foreach($this->dataset as $k => $v){
			$dataset[] = sprintf('data-%s="%s"', $k, $v);
		}

		return implode(' ', $dataset);
	}


}
