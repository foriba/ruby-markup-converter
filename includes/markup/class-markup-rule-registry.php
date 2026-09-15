<?php
/**
 * ルビ・傍点記法のルール定義と参照
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Markup_Rule_Registry {
	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @var Markup_rule[] | null
	 */
	private $rules = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function reset(): void {
		self::$instance = null;
	}

	/**
	 * @return Markup_Rule[]
	 */
	public function all(): array {
		if ( null === $this->rules ) {
			$this->rules = $this->define();
		}

		return $this->rules;
	}

	/**
	 * @return Markup_Rule[]
	 */
	public function for_settings_view(): array {
		return $this->all();
	}

	/**
	 * @return string[]
	 */
	public function default_enabled_ids(): array {
		$ids = array();

		foreach ( $this->all() as $rule ) {
			if ( $rule->enabled_by_default ) {
				$ids[] = $rule->id;
			}
		}

		return $ids;
	}

	/**
	 * 親ルールの定義順、および transform_rules の定義順を維持する。
	 *
	 * @param string[]|array<int, mixed> $rule_ide
	 * @return Transform_Rule[]
	 */
	public function transform_rules_for( array $rule_ids ): array {
		$wanted = array();

		foreach ( $rule_ids as $rule_id ) {
			$rule_id = (string) $rule_id;
			if ( '' !== $rule_id ) {
				$wanted[ $rule_id ] = true;
			}
		}

		$transform_rules = array();

		foreach ( $this->all() as $rule ) {
			if ( ! isset( $wanted [ $rule->id ] ) ) {
				continue;
			}

			foreach ( $rule->transform_rules as $transform_rule ) {
				$transform_rules[] = $transform_rule;
			}
		}

		return $transform_rules;
	}

	/**
	 * @return Markup_Rule[]
	 */
	private function define(): array {
		return array(
			new Markup_Rule(
				'ruby_double_angle',
				Rule_Type::ruby(),
				array(
					__( '｜BaseText《RubyAnnotation》 Markup', 'ruby-markup-converter' ),
					__( 'BaseText《RubyAnnotation》 Markup', 'ruby-markup-converter' ),
				),
				array(
					'それが｜真実の愛《トゥルーラブ》です。',
					'それが真実《しんじつ》の愛《あい》です。',
				),
				__(
					'Supports both the "｜BaseText《RubyAnnotation》" and "BaseText《RubyAnnocation》" markup styles. The former explicitly specifies both the base text and ruby annotation, with no restrictions on character types. Both full-width and half-width vertical bars are supported. The latter automatically treats the preceding kanji characters as the base text, so the base text is limited to kanji.',
					'ruby-markup-converter'
				),
				true,
				array(
					new Transform_Rule(
						'ruby_double_angle_explicit',
						Rule_Type::ruby(),
						'/[|｜]([^<>|｜《》]+?)《([^<>《》]+?)》/u'
					),
					new Transform_Rule(
						'ruby_double_angle_implicit',
						Rule_Type::ruby(),
						'/([一-龯々〆〤]+)《([^<>《》]+?)》/u'
					),
				)
			),

			new Markup_Rule(
				'ruby_parenthesis',
				Rule_Type::ruby(),
				array(
					__( 'BaseText(RubyAnnotation) Markup', 'ruby-markup-converter' ),
				),
				array(
					'それが真実(しんじつ)の愛(あい)です。',
				),
				__(
					'Uses the "BaseText(RubyAnnotation)" markup style. Parentheses must be half-width characters. The kanji characters immediately preceding "(RubyAnnotation)" are automatically treated as the base text, so the base text is limited to kanji. Ruby annotation supports hiragana, katakana, prolonged sound marks, and middle dots.',
					'ruby-markup-converter'
				),
				false,
				array(
					new Transform_Rule(
						'ruby_parenthesis',
						Rule_Type::ruby(),
						'/([一-龯々〆〤]+)\(([ぁ-ゖァ-ヺー・]+)\)/u'
					),
				)
			),

			new Markup_Rule(
				'ruby_rb',
				Rule_Type::ruby(),
				array(
					__( '[[rb:BaseText > RubyAnnotation]] Markup', 'ruby-markup-converter' ),
				),
				array(
					'それが[[rb:真実の愛 > トゥルーラブ]]です。',
				),
				__(
					'Uses the "[[rb:BaseText > RubyAnnotation]]" markup style to explicitly specify both the base text and ruby annotation. There are no character type restrictions for either the base text or the ruby annotation.',
					'ruby-markup-converter'
				),
				false,
				array(
					new Transform_Rule(
						'ruby_rb',
						Rule_Type::ruby(),
						'/\[\[rb:([^>\[\]]+?)\s*>\s*([^\[\]]+?)\]\]/u'
					),
				)
			),

			new Markup_Rule(
				'ruby_double_underscore',
				Rule_Type::ruby(),
				array(
					__( '#BaseText__RubyAnnotation__# Markup', 'ruby-markup-converter' ),
				),
				array(
					'それが#真実の愛__トゥルーラブ__#です。',
				),
				__(
					'Uses the "#BaseText__RubyAnnotation__#" markup style to explicitly specify both the base text and ruby annotation. There are no character type restrictions for either the base text or the ruby annotation.',
					'ruby-markup-converter'
				),
				false,
				array(
					new Transform_Rule(
						'ruby_double_underscore',
						Rule_Type::ruby(),
						'/#(.+?)__(.+?)__#/u'
					),
				)
			),

			new Markup_Rule(
				'ruby_mediawiki',
				Rule_Type::ruby(),
				array(
					__( '{{ruby|BaseText|RubyAnnotation}} Markup', 'ruby-markup-converter' ),
				),
				array(
					'それが{{ruby|真実の愛|トゥルーラブ}}です。',
				),
				__(
					'Uses the "{{ruby|BaseText|RubyAnnotation}}" markup style to explicitly specify both the base text and ruby annotation. There are no character type restrictions for either the base text or the ruby annotation.',
					'ruby-markup-converter'
				),
				false,
				array(
					new Transform_Rule(
						'ruby_mediawiki',
						Rule_Type::ruby(),
						'/\{\{ruby\|(.+?)\|(.+?)\}\}/u'
					),
				)
			),

			new Markup_Rule(
				'bouten_double_bracket',
				Rule_Type::bouten(),
				array(
					__( '《《Emphasis》》 Markup', 'ruby-markup-converter' ),
				),
				array(
					'この部分が《《強調》》されます。',
				),
				__(
					'Adds bouten marks to each character enclosed by "《《" and "》》".',
					'ruby-markup-converter'
				),
				true,
				array(
					new Transform_Rule(
						'bouten_double_bracket',
						Rule_Type::bouten(),
						'/《《([^<>]+?)》》/u'
					),
				)
			),
		);
	}
}
