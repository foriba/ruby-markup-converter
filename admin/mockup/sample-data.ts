// デザイン検討用の固定データ。実際の設定やデータベースからは取得しない。
export type Rule = {
	id: string;
	type: 'ruby' | 'bouten';
	title: string;
	description: string;
	examples: string[];
	enabled: boolean;
};
export const rules: Rule[] = [
	{
		id: 'ruby_double_angle',
		type: 'ruby',
		enabled: true,
		title: '｜親文字《ルビ》\n親文字《ルビ》',
		description:
			'縦棒で親文字を指定する形式と、直前の漢字を親文字とする形式に対応します。縦棒は全角・半角のどちらも使用できます。縦棒を使う場合、親文字とルビの文字種に制約はありません。',
		examples: [
			'それが｜真実の愛《トゥルーラブ》です。',
			'それが真実《しんじつ》の愛《あい》です。',
		],
	},
	{
		id: 'ruby_parenthesis',
		type: 'ruby',
		enabled: false,
		title: '親文字(ルビ)',
		description:
			'半角の括弧を使用し、直前の漢字を親文字として扱います。ルビにはひらがな・カタカナ・長音符・中点が使えます。',
		examples: [ 'それが真実(しんじつ)の愛(あい)です。' ],
	},
	{
		id: 'ruby_rb',
		type: 'ruby',
		enabled: false,
		title: '[[rb:親文字 > ルビ]]',
		description:
			'親文字とルビを明示的に指定します。どちらも文字種の制約はありません。',
		examples: [ 'それが[[rb:真実の愛 > トゥルーラブ]]です。' ],
	},
	{
		id: 'ruby_double_underscore',
		type: 'ruby',
		enabled: false,
		title: '#親文字__ルビ__#',
		description:
			'親文字とルビを明示的に指定します。どちらも文字種の制約はありません。',
		examples: [ 'それが#真実の愛__トゥルーラブ__#です。' ],
	},
	{
		id: 'ruby_mediawiki',
		type: 'ruby',
		enabled: false,
		title: '{{ruby|親文字|ルビ}}',
		description:
			'親文字とルビを明示的に指定します。どちらも文字種の制約はありません。',
		examples: [ 'それが{{ruby|真実の愛|トゥルーラブ}}です。' ],
	},
	{
		id: 'bouten_double_bracket',
		type: 'bouten',
		enabled: true,
		title: '《《強調》》',
		description: '《《 と 》》で囲んだ文字列の各文字に傍点を付けます。',
		examples: [ 'この部分が《《強調》》されます。' ],
	},
];
