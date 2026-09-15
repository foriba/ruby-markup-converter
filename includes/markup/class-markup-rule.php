<?php
/**
 * 管理画面の表示・保存単位となる親ルール
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Markup_Rule {
	/**
	 *  @var string
	 */
	public string $id;

	/**
	 * @var Rule_Type
	 */
	public Rule_Type $type;

	/**
	 * @var string[]
	 */
	public array $titles;

	/**
	 * @var string[]
	 */
	public array $examples;

	/**
	 * @var string
	 */
	public string $description;

	/**
	 * @var bool
	 */
	public bool $enabled_by_default;

	/**
	 * @var Transform_Rule[]
	 */
	public array $transform_rules;

	/**
	 * @param string[]         $titles
	 * @param string[]         $examples
	 * @param Transform_Rule[] $transform_rules
	 */
	public function __construct(
		string $id,
		Rule_Type $type,
		array $titles,
		array $examples,
		string $description,
		bool $enabled_by_default,
		array $transform_rules
	) {
		if ( '' === $id ) {
			throw new \InvalidArgumentException( 'MarkupRule id must not be empty.' );
		}

		$this->id                 = $id;
		$this->type               = $type;
		$this->titles             = $titles;
		$this->examples           = $examples;
		$this->description        = $description;
		$this->enabled_by_default = $enabled_by_default;
		$this->transform_rules    = $transform_rules;
	}

	/**
	 * 設定画面など、従来の配列形が必要な箇所向け。
	 *
	 * @return array(
	 *      id:string,
	 *      type:string,
	 *      title:string[],
	 *      example:string[],
	 *      description:string,
	 *      enabled_by_default:bool,
	 *      transform_rules:array<int, array{id:string, type:string, pattern:string}>
	 * )
	 */
	public function to_array(): array {
		return array(
			'id'                 => $this->id,
			'type'               => $this->type->get_value(),
			'title'              => $this->titles,
			'example'            => $this->examples,
			'description'        => $this->description,
			'enabled_by_default' => $this->enabled_by_default,
			'transform_rules'    => array_map(
				static fn( Transform_Rule $rule ): array => $rule->to_array(),
				$this->transform_rules
			),
		);
	}
}
