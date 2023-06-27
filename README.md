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

### 1.0.0

- 公開
