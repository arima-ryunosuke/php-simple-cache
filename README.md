simple cache
====

## Description

psr-16 を実装したシンプルなキャッシュパッケージです。
ストリームのみの実装ですが、php には強力な StreamWrapper があるので、（Wrapper があれば）実質的にあらゆる対象に書き込むことができます。

## Install

```json
{
    "require": {
        "ryunosuke/simple-cache": "dev-master"
    }
}
```

## Feature

（Wrapper があれば）あらゆる個所に出力できます。
例えば下記は S3 にキャッシュを出力します。

```
$s3client = new \Aws\S3\S3Client([
    'credentials' => [
        'key'    => 'foo',
        'secret' => 'bar',
    ],
    'region'      => 'ap-northeast-1',
    'version'     => 'latest',
]);
\Aws\S3\StreamWrapper::register($s3client);

$cache = new \ryunosuke\SimpleCache\StreamCache('s3://bucket-name/savedir');
$cache->set('cache-key', 'Hello, world');
var_dump($cache->get('cache-key', 'Hello, world')); // string(12) "Hello, world"
```

ArrayAccess を実装しています。
それ自体は特筆すべきではない実装ですが、ArrayAccess によって `??=` を利用した「あるなら取得、なかったら設定しつつ取得」が容易になります。

```
$cache = new \ryunosuke\SimpleCache\StreamCache('s3://bucket-name/savedir');
// あるなら取得、なかったら設定しつつ取得
$cache['cache-key'] ??= heavy_function();
// つまり下記と同義です
if (!$cache->has(['cache-key'])) {
    $cache->set('cache-key', heavy_function());
}
```

追加で下記のようなメソッドが生えています。

- fetch: 取得を試みて、無かったらクロージャの返り値を格納しつつ返します
- keys: キャッシュキーの一覧を取得します
- items: キャッシュキーとキャッシュアイテムの一覧を取得します
- gc: 有効期限の切れたアイテムや無効になっているアイテムを削除します

キーに特殊な拡張子を指定すると格納方法を指定できます。
組み込みで下記の拡張子が使えます。

- `php`: php のコードとして格納します。opcache が効くため高速です
- `php-serialize`: serialize で格納します。↑があるため出番はほぼありません

`php` は無名クラスやクロージャもキャッシュできます。
これらの拡張子・クラスは `itemClasses` オプションで拡張できます。

`defaultExtension` で拡張子がなかった場合のデフォルト格納方法を指定できます。

なお、 キーの `/` はディレクトリ区切りを意味し、新たにディレクトリが作成されます。
これは psr-16 違反なので `directorySeparator` オプションで明示的に指定できます。

## License

MIT

## FAQ

- Q. なんで車輪の再開発した？
  - A. 元々 symfony-cache を好んで使っていたんですが、少し仰々しく感じてきて、実際のところ adapter は PhpFilesAdapter しか使わないし見通し良くなるように自前実装したかったのです
- Q. いや、symfony-cache なら Redis とか APCu とか有用なのもあるよ？
  - A. 専用の adapter を書かずとも php には既に StreamWrapper という強力な抽象化レイヤーが存在します。adapter で使い分けるよりも `s3://hoge/cache` とか `redis://hoge/cache` とか書くだけでよしなに判断してくれる方が好みなのです
- Q. それにしたって自前実装しなくても…
  - A. PhpFilesAdapter の吐き出すファイル名が好みではない、というのも多分にありました。フラットかつ key から想起されるファイル名であって欲しかったのです

## Release

バージョニングは romantic versioning に準拠します（semantic versioning ではありません）。

- メジャー: 大規模な互換性破壊の際にアップします（アーキテクチャ、クラス構造の変更など）
- マイナー: 小規模な互換性破壊の際にアップします（引数の変更、タイプヒントの追加など）
- パッチ: 互換性破壊はありません（デフォルト引数の追加や、新たなクラスの追加、コードフォーマットなど）

### 1.0.8

- [composer] update

### 1.0.7

- [feature] All としてすべてを継承するインターフェースを追加
- [feature] Hashable を実装

### 1.0.6

- [feature] ArrayAccess を実装

### 1.0.5

- [feature] ロック機構を追加
- [fixbug] 軽微な不具合を修正

### 1.0.4

- [feature] ChainCache/NullCache もすべての interface を完備する
- [refactor] fetch を trait 化
- [refactor] getMultiple の実装が気持ち悪かったのでリファクタ

### 1.0.3

- [feature] メモ化にサイズ上限を実装

### 1.0.2

- [feature] debugInfo を実装

### 1.0.1

- [feature] NullCache を追加
- [refactor] 戻り値だけでも型宣言を合わせたいので psr を拡張した CacheInterface を用意
- [refactor] SingleTrait の導入と ChainCache への組み込み

### 1.0.0

- 公開
