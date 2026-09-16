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

/**
 * 記法の定義を保持し、設定表示や変換に必要なルールを取得する。
 *
 * 初回取得時に表示文言を翻訳し、インスタンス内に定義を保持する。
 * 返すルールオブジェクトは共有されるため、呼び出し側で変更しないこと。
 */
final class Markup_Rule_Registry {
	/**
	 * 共通の取得窓口で使用するインスタンス。
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * 初回取得時に生成する記法一覧。
	 *
	 * @var Markup_Rule[]|null
	 */
	private $rules = null;

	/**
	 * 共通のレジストリを取得する。
	 *
	 * @return self レジストリ。
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * 共通のインスタンスを解除し、次回取得時に再生成する。
	 *
	 * 既に取得されたインスタンスやルールは変更しない。
	 * ロケール変更後に文言を再取得する場合は、解除後に instance() を呼ぶ。
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$instance = null;
	}

	/**
	 * すべての記法を定義順に取得する。
	 *
	 * @return Markup_Rule[] 記法一覧。
	 */
	public function all(): array {
		if ( null === $this->rules ) {
			$this->rules = $this->define();
		}

		return $this->rules;
	}

	/**
	 * 設定画面で使用する記法一覧を取得する。
	 *
	 * 配列への変換や HTML エスケープは行わない。
	 *
	 * @return Markup_Rule[] 定義順の記法一覧。
	 */
	public function for_settings_view(): array {
		return $this->all();
	}

	/**
	 * すべての親ルールの ID を定義順に取得する。
	 *
	 * @return string[] 親ルールの ID 一覧。
	 */
	public function ids(): array {
		$ids = array();

		foreach ( $this->all() as $rule ) {
			$ids[] = $rule->id;
		}

		return $ids;
	}

	/**
	 * 初期状態で有効な記法の ID を定義順に取得する。
	 *
	 * @return string[] 親ルールの ID 一覧。
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
	 * 指定された記法に属する変換ルールを取得する。
	 *
	 * 親ルールの定義順、および transform_rules の定義順を維持する。
	 * 空文字列と未登録の ID は無視し、重複した ID は一度だけ扱う。
	 * 入力は文字列の一覧を前提とし、任意の値の検証は行わない。
	 *
	 * @param string[] $rule_ids 親ルールの ID 一覧.
	 * @return Transform_Rule[] 選択された変換ルール一覧。
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
			if ( ! isset( $wanted[ $rule->id ] ) ) {
				continue;
			}

			foreach ( $rule->transform_rules as $transform_rule ) {
				$transform_rules[] = $transform_rule;
			}
		}

		return $transform_rules;
	}

	/**
	 * 表示情報と変換ルールを含む記法一覧を生成する。
	 *
	 * 配列の順序は設定画面の表示順と変換の適用順を兼ねる。
	 * 表示文言は呼び出し時点のロケールで翻訳する。
	 *
	 * @return Markup_Rule[] 定義順の記法一覧。
	 */
	private function define(): array {
		return array(
			new Markup_Rule(
				'ruby_double_angle',
				Rule_Type::ruby(),
				array(
					__( '｜BaseText《RubyAnnotation》 Markup', 'ruby-markup-converter' ), // ja-jp: '｜親文字《ルビ》 記法'.
					__( 'BaseText《RubyAnnotation》 Markup', 'ruby-markup-converter' ), // ja-jp: '親文字《ルビ》 記法'.
				),
				array(
					'それが｜真実の愛《トゥルーラブ》です。',
					'それが真実《しんじつ》の愛《あい》です。',
				),
				__(
					'Supports both the "｜BaseText《RubyAnnotation》" and "BaseText《RubyAnnocation》" markup styles. The former explicitly specifies both the base text and ruby annotation, with no restrictions on character types. Both full-width and half-width vertical bars are supported. The latter automatically treats the preceding kanji characters as the base text, so the base text is limited to kanji.',
					'ruby-markup-converter'
				), // ja-jp: '「｜親文字《ルビ》」および「親文字《ルビ》」の2つの形式に対応します。前者は親文字とルビを明示的に指定する形式で、親文字・ルビともに文字種の制約はありません。縦棒は全角・半角のいずれにも対応します。後者は《ルビ》の直前の漢字を自動的に親文字として扱うため、親文字は漢字限定となります。'.
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
					__( 'BaseText(RubyAnnotation) Markup', 'ruby-markup-converter' ), // ja-jp: '親文字(ルビ) 記法'.
				),
				array(
					'それが真実(しんじつ)の愛(あい)です。',
				),
				__(
					'Uses the "BaseText(RubyAnnotation)" markup style. Parentheses must be half-width characters. The kanji characters immediately preceding "(RubyAnnotation)" are automatically treated as the base text, so the base text is limited to kanji. Ruby annotation supports hiragana, katakana, prolonged sound marks, and middle dots.',
					'ruby-markup-converter'
				), // ja-jp: '「親文字(ルビ)」の形式で指定します。()は半角限定です。(ルビ)の直前の漢字を自動的に親文字として扱うため、親文字は漢字限定となります。ルビはひらがな・カタカナ・長音符・中点に対応しています。'.
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
					__( '[[rb:BaseText > RubyAnnotation]] Markup', 'ruby-markup-converter' ), // ja-jp: '[[rb:親文字 > ルビ]] 記法'.
				),
				array(
					'それが[[rb:真実の愛 > トゥルーラブ]]です。',
				),
				__(
					'Uses the "[[rb:BaseText > RubyAnnotation]]" markup style to explicitly specify both the base text and ruby annotation. There are no character type restrictions for either the base text or the ruby annotation.',
					'ruby-markup-converter'
				), // ja-jp; '「[[rb:親文字 > ルビ]]」の形式で、親文字とルビを明示的に指定します。親文字・ルビともに文字種の制約はありません。'.
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
					__( '#BaseText__RubyAnnotation__# Markup', 'ruby-markup-converter' ), // ja-jp: '#親文字__ルビ__# 記法'.
				),
				array(
					'それが#真実の愛__トゥルーラブ__#です。',
				),
				__(
					'Uses the "#BaseText__RubyAnnotation__#" markup style to explicitly specify both the base text and ruby annotation. There are no character type restrictions for either the base text or the ruby annotation.',
					'ruby-markup-converter'
				), // ja-jp: '「#親文字__ルビ__#」の形式で、親文字とルビを明示的に指定します。親文字・ルビともに文字種の制約はありません。'.
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
					__( '{{ruby|BaseText|RubyAnnotation}} Markup', 'ruby-markup-converter' ), // ja-jp: '{{ruby|親文字|ルビ}} 記法'.
				),
				array(
					'それが{{ruby|真実の愛|トゥルーラブ}}です。',
				),
				__(
					'Uses the "{{ruby|BaseText|RubyAnnotation}}" markup style to explicitly specify both the base text and ruby annotation. There are no character type restrictions for either the base text or the ruby annotation.',
					'ruby-markup-converter'
				), // ja-jp: '「{{ruby|親文字|ルビ}}」の形式で、親文字とルビを明示的に指定します。親文字・ルビともに文字種の制約はありません。'.
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
					__( '《《Emphasis》》 Markup', 'ruby-markup-converter' ), // ja-jp: '《《強調》》 記法'.
				),
				array(
					'この部分が《《強調》》されます。',
				),
				__(
					'Adds bouten marks to each character enclosed by "《《" and "》》".',
					'ruby-markup-converter'
				), // ja-jp: '「《《」と「》》」で囲まれた文字列に対して、各文字に傍点を付与します。'.
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
