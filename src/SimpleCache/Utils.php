<?php
# Don't touch this code. This is auto generated.
namespace ryunosuke\SimpleCache;

// @formatter:off

/**
 * @codeCoverageIgnore
 */
class Utils
{
    public const TOKEN_NAME = 2;

    /**
     * 全要素が true になるなら true を返す（1つでも false なら false を返す）
     *
     * $callback が要求するならキーも渡ってくる。
     *
     * Example:
     * ```php
     * that(array_all([true, true]))->isTrue();
     * that(array_all([true, false]))->isFalse();
     * that(array_all([false, false]))->isFalse();
     * ```
     *
     * @package ryunosuke\Functions\Package\array
     *
     * @param iterable $array 対象配列
     * @param ?callable $callback 評価クロージャ。 null なら値そのもので評価
     * @param bool|mixed $default 空配列の場合のデフォルト値
     * @return bool 全要素が true なら true
     */
    public static function array_all($array, $callback = null, $default = true)
    {
        if (\ryunosuke\SimpleCache\Utils::is_empty($array)) {
            return $default;
        }

        $callback = \ryunosuke\SimpleCache\Utils::func_user_func_array($callback);

        $n = 0;
        foreach ($array as $k => $v) {
            if (!$callback($v, $k, $n++)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 配列を指定条件で分割する
     *
     * 文字列の explode を更に一階層掘り下げたイメージ。
     * $condition で指定された要素は結果配列に含まれない。
     *
     * $condition にはクロージャが指定できる。クロージャの場合は true 相当を返した場合に分割要素とみなされる。
     * 引数は (値, キー)の順番。
     *
     * $limit に負数を与えると「その絶対値-1までを結合したものと残り」を返す。
     * 端的に言えば「正数を与えると後詰めでその個数で返す」「負数を与えると前詰めでその（絶対値）個数で返す」という動作になる。
     *
     * Example:
     * ```php
     * // null 要素で分割
     * that(array_explode(['a', null, 'b', 'c'], null))->isSame([['a'], [2 => 'b', 3 => 'c']]);
     * // クロージャで分割（大文字で分割）
     * that(array_explode(['a', 'B', 'c', 'D', 'e'], fn($v) => ctype_upper($v)))->isSame([['a'], [2 => 'c'], [4 => 'e']]);
     * // 負数指定
     * that(array_explode(['a', null, 'b', null, 'c'], null, -2))->isSame([[0 => 'a', 1 => null, 2 => 'b'], [4 => 'c']]);
     * ```
     *
     * @package ryunosuke\Functions\Package\array
     *
     * @param iterable $array 対象配列
     * @param mixed $condition 分割条件
     * @param int $limit 最大分割数
     * @return array 分割された配列
     */
    public static function array_explode($array, $condition, $limit = \PHP_INT_MAX)
    {
        $array = \ryunosuke\SimpleCache\Utils::arrayval($array, false);

        $limit = (int) $limit;
        if ($limit < 0) {
            // キーまで考慮するとかなりややこしくなるので富豪的にやる
            $reverse = \ryunosuke\SimpleCache\Utils::array_explode(array_reverse($array, true), $condition, -$limit);
            $reverse = array_map(fn($v) => array_reverse($v, true), $reverse);
            return array_reverse($reverse);
        }
        // explode において 0 は 1 と等しい
        if ($limit === 0) {
            $limit = 1;
        }

        $result = [];
        $chunk = [];
        $n = -1;
        foreach ($array as $k => $v) {
            $n++;

            if ($limit === 1) {
                $chunk = array_slice($array, $n, null, true);
                break;
            }

            if ($condition instanceof \Closure) {
                $match = $condition($v, $k, $n);
            }
            else {
                $match = $condition === $v;
            }

            if ($match) {
                $limit--;
                $result[] = $chunk;
                $chunk = [];
            }
            else {
                $chunk[$k] = $v;
            }
        }
        $result[] = $chunk;
        return $result;
    }

    /**
     * array_search のクロージャ版のようなもの
     *
     * コールバックの返り値が true 相当のものを返す。
     * $is_key に true を与えるとそのキーを返す（デフォルトの動作）。
     * $is_key に false を与えるとコールバックの結果を返す。
     *
     * この関数は論理値 FALSE を返す可能性がありますが、FALSE として評価される値を返す可能性もあります。
     *
     * Example:
     * ```php
     * // 最初に見つかったキーを返す
     * that(array_find(['a', '8', '9'], 'ctype_digit'))->isSame(1);
     * that(array_find(['a', 'b', 'b'], fn($v) => $v === 'b'))->isSame(1);
     * // 最初に見つかったコールバック結果を返す（最初の数字の2乗を返す）
     * $ifnumeric2power = fn($v) => ctype_digit($v) ? $v * $v : false;
     * that(array_find(['a', '8', '9'], $ifnumeric2power, false))->isSame(64);
     * ```
     *
     * @package ryunosuke\Functions\Package\array
     *
     * @param iterable $array 調べる配列
     * @param callable $callback 評価コールバック
     * @param bool $is_key キーを返すか否か
     * @return mixed コールバックが true を返した最初のキー。存在しなかったら false
     */
    public static function array_find($array, $callback, $is_key = true)
    {
        $callback = \ryunosuke\SimpleCache\Utils::func_user_func_array($callback);

        $n = 0;
        foreach ($array as $k => $v) {
            $result = $callback($v, $k, $n++);
            if ($result) {
                if ($is_key) {
                    return $k;
                }
                return $result;
            }
        }
        return false;
    }

    /**
     * 引数の配列を生成する。
     *
     * 配列以外を渡すと配列化されて追加される。
     * 連想配列は未対応。あくまで普通の配列化のみ。
     * iterable や Traversable は考慮せずあくまで「配列」としてチェックする。
     *
     * Example:
     * ```php
     * that(arrayize(1, 2, 3))->isSame([1, 2, 3]);
     * that(arrayize([1], [2], [3]))->isSame([1, 2, 3]);
     * $object = new \stdClass();
     * that(arrayize($object, false, [1, 2, 3]))->isSame([$object, false, 1, 2, 3]);
     * ```
     *
     * @package ryunosuke\Functions\Package\array
     *
     * @param mixed ...$variadic 生成する要素（可変引数）
     * @return array 引数を配列化したもの
     */
    public static function arrayize(...$variadic)
    {
        $result = [];
        foreach ($variadic as $arg) {
            if (!is_array($arg)) {
                $result[] = $arg;
            }
            elseif (!\ryunosuke\SimpleCache\Utils::is_hasharray($arg)) {
                $result = array_merge($result, $arg);
            }
            else {
                $result += $arg;
            }
        }
        return $result;
    }

    /**
     * 配列が連想配列か調べる
     *
     * 空の配列は普通の配列とみなす。
     *
     * Example:
     * ```php
     * that(is_hasharray([]))->isFalse();
     * that(is_hasharray([1, 2, 3]))->isFalse();
     * that(is_hasharray(['x' => 'X']))->isTrue();
     * ```
     *
     * @package ryunosuke\Functions\Package\array
     *
     * @param array $array 調べる配列
     * @return bool 連想配列なら true
     */
    public static function is_hasharray(array $array)
    {
        $i = 0;
        foreach ($array as $k => $dummy) {
            if ($k !== $i++) {
                return true;
            }
        }
        return false;
    }

    /**
     * 配列の最後のキーを返す
     *
     * 空の場合は $default を返す。
     *
     * Example:
     * ```php
     * that(last_key(['a', 'b', 'c']))->isSame(2);
     * that(last_key([], 999))->isSame(999);
     * ```
     *
     * @package ryunosuke\Functions\Package\array
     *
     * @param iterable $array 対象配列
     * @param mixed $default 無かった場合のデフォルト値
     * @return mixed 最後のキー
     */
    public static function last_key($array, $default = null)
    {
        if (\ryunosuke\SimpleCache\Utils::is_empty($array)) {
            return $default;
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$k, $v] = \ryunosuke\SimpleCache\Utils::last_keyvalue($array);
        return $k;
    }

    /**
     * 配列の最後のキー/値ペアをタプルで返す
     *
     * 空の場合は $default を返す。
     *
     * Example:
     * ```php
     * that(last_keyvalue(['a', 'b', 'c']))->isSame([2, 'c']);
     * that(last_keyvalue([], 999))->isSame(999);
     * ```
     *
     * @package ryunosuke\Functions\Package\array
     *
     * @param iterable|object $array 対象配列
     * @param mixed $default 無かった場合のデフォルト値
     * @return array [最後のキー, 最後の値]
     */
    public static function last_keyvalue($array, $default = null)
    {
        if (\ryunosuke\SimpleCache\Utils::is_empty($array)) {
            return $default;
        }
        if (is_array($array)) {
            $k = array_key_last($array);
            return [$k, $array[$k]];
        }
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        foreach ($array as $k => $v) {
            // dummy
        }
        // $k がセットされてるなら「ループが最低でも1度回った（≠空）」とみなせる
        if (isset($k)) {
            /** @noinspection PhpUndefinedVariableInspection */
            return [$k, $v];
        }
        return $default;
    }

    /**
     * オブジェクトのプロパティを可視・不可視を問わず取得する
     *
     * get_object_vars + no public プロパティを返すイメージ。
     * クロージャだけは特別扱いで this + use 変数を返す。
     *
     * Example:
     * ```php
     * $object = new \Exception('something', 42);
     * $object->oreore = 'oreore';
     *
     * // get_object_vars はそのスコープから見えないプロパティを取得できない
     * // var_dump(get_object_vars($object));
     *
     * // array キャストは全て得られるが null 文字を含むので扱いにくい
     * // var_dump((array) $object);
     *
     * // この関数を使えば不可視プロパティも取得できる
     * that(get_object_properties($object))->subsetEquals([
     *     'message' => 'something',
     *     'code'    => 42,
     *     'oreore'  => 'oreore',
     * ]);
     *
     * // クロージャは this と use 変数を返す
     * that(get_object_properties(fn() => $object))->is([
     *     'this'   => $this,
     *     'object' => $object,
     * ]);
     * ```
     *
     * @package ryunosuke\Functions\Package\classobj
     *
     * @param object $object オブジェクト
     * @param array $privates 継承ツリー上の private が格納される
     * @return array 全プロパティの配列
     */
    public static function get_object_properties($object, &$privates = [])
    {
        if ($object instanceof \Closure) {
            $ref = new \ReflectionFunction($object);
            $uses = method_exists($ref, 'getClosureUsedVariables') ? $ref->getClosureUsedVariables() : $ref->getStaticVariables();
            return ['this' => $ref->getClosureThis()] + $uses;
        }

        $fields = [];
        foreach ((array) $object as $name => $field) {
            $cname = '';
            $names = explode("\0", $name);
            if (count($names) > 1) {
                $name = array_pop($names);
                $cname = $names[1];
            }
            $fields[$cname][$name] = $field;
        }

        $classname = get_class($object);
        $parents = array_values(['', '*', $classname] + class_parents($object));
        uksort($fields, function ($a, $b) use ($parents) {
            return array_search($a, $parents, true) <=> array_search($b, $parents, true);
        });

        $result = [];
        foreach ($fields as $cname => $props) {
            foreach ($props as $name => $field) {
                if ($cname !== '' && $cname !== '*' && $classname !== $cname) {
                    $privates[$cname][$name] = $field;
                }
                if (!array_key_exists($name, $result)) {
                    $result[$name] = $field;
                }
            }
        }

        return $result;
    }

    /**
     * 指定 callable を指定クロージャで実行するクロージャを返す
     *
     * ほぼ内部向けで外から呼ぶことはあまり想定していない。
     *
     * @package ryunosuke\Functions\Package\funchand
     *
     * @param \Closure $invoker クロージャを実行するためのクロージャ（実処理）
     * @param callable $callable 最終的に実行したいクロージャ
     * @param ?int $arity 引数の数
     * @return callable $callable を実行するクロージャ
     */
    public static function delegate($invoker, $callable, $arity = null)
    {
        $arity ??= \ryunosuke\SimpleCache\Utils::parameter_length($callable, true, true);

        if (\ryunosuke\SimpleCache\Utils::reflect_callable($callable)->isInternal()) {
            static $cache = [];
            $cache[(string) $arity] ??= \ryunosuke\SimpleCache\Utils::evaluate('return new class()
            {
                private $invoker, $callable;

                public function spawn($invoker, $callable)
                {
                    $that = clone($this);
                    $that->invoker = $invoker;
                    $that->callable = $callable;
                    return $that;
                }

                public function __invoke(' . implode(',', is_infinite($arity)
                    ? ['...$_']
                    : array_map(fn($v) => '$_' . $v, array_keys(array_fill(1, $arity, null)))
                ) . ')
                {
                    return ($this->invoker)($this->callable, func_get_args());
                }
            };');
            return $cache[(string) $arity]->spawn($invoker, $callable);
        }

        switch (true) {
            case $arity === 0:
                return fn() => $invoker($callable, func_get_args());
            case $arity === 1:
                return fn($_1) => $invoker($callable, func_get_args());
            case $arity === 2:
                return fn($_1, $_2) => $invoker($callable, func_get_args());
            case $arity === 3:
                return fn($_1, $_2, $_3) => $invoker($callable, func_get_args());
            case $arity === 4:
                return fn($_1, $_2, $_3, $_4) => $invoker($callable, func_get_args());
            case $arity === 5:
                return fn($_1, $_2, $_3, $_4, $_5) => $invoker($callable, func_get_args());
            case is_infinite($arity):
                return fn(...$_) => $invoker($callable, func_get_args());
            default:
                $args = implode(',', array_map(fn($v) => '$_' . $v, array_keys(array_fill(1, $arity, null))));
                $stmt = 'return function (' . $args . ') use ($invoker, $callable) { return $invoker($callable, func_get_args()); };';
                return eval($stmt);
        }
    }

    /**
     * パラメータ定義数に応じて呼び出し引数を可変にしてコールする
     *
     * デフォルト引数はカウントされない。必須パラメータの数で呼び出す。
     *
     * $callback に null を与えると例外的に「第1引数を返すクロージャ」を返す。
     *
     * php の標準関数は定義数より多い引数を投げるとエラーを出すのでそれを抑制したい場合に使う。
     *
     * Example:
     * ```php
     * // strlen に2つの引数を渡してもエラーにならない
     * $strlen = func_user_func_array('strlen');
     * that($strlen('abc', null))->isSame(3);
     * ```
     *
     * @package ryunosuke\Functions\Package\funchand
     *
     * @param callable|null $callback 呼び出すクロージャ
     * @return callable 引数ぴったりで呼び出すクロージャ
     */
    public static function func_user_func_array($callback)
    {
        // null は第1引数を返す特殊仕様
        if ($callback === null) {
            return fn($v) => $v;
        }
        // クロージャはユーザ定義しかありえないので調べる必要がない
        if ($callback instanceof \Closure) {
            // と思ったが、\Closure::fromCallable で作成されたクロージャは内部属性が伝播されるようなので除外
            if (\ryunosuke\SimpleCache\Utils::reflect_callable($callback)->isUserDefined()) {
                return $callback;
            }
        }

        // 上記以外は「引数ぴったりで削ぎ落としてコールするクロージャ」を返す
        $plength = \ryunosuke\SimpleCache\Utils::parameter_length($callback, true, true);
        return \ryunosuke\SimpleCache\Utils::delegate(function ($callback, $args) use ($plength) {
            if (is_infinite($plength)) {
                return $callback(...$args);
            }
            return $callback(...array_slice($args, 0, $plength));
        }, $callback, $plength);
    }

    /**
     * $this を bind 可能なクロージャか調べる
     *
     * Example:
     * ```php
     * that(is_bindable_closure(function () {}))->isTrue();
     * that(is_bindable_closure(static function () {}))->isFalse();
     * ```
     *
     * @package ryunosuke\Functions\Package\funchand
     *
     * @param \Closure $closure 調べるクロージャ
     * @return bool $this を bind 可能なクロージャなら true
     */
    public static function is_bindable_closure(\Closure $closure)
    {
        return !!@$closure->bindTo(new \stdClass());
    }

    /**
     * eval のプロキシ関数
     *
     * 一度ファイルに吐いてから require した方が opcache が効くので抜群に速い。
     * また、素の eval は ParseError が起こったときの表示がわかりにくすぎるので少し見やすくしてある。
     *
     * 関数化してる以上 eval におけるコンテキストの引き継ぎはできない。
     * ただし、引数で変数配列を渡せるようにしてあるので get_defined_vars を併用すれば基本的には同じ（$this はどうしようもない）。
     *
     * 短いステートメントだと opcode が少ないのでファイルを経由せず直接 eval したほうが速いことに留意。
     * 一応引数で指定できるようにはしてある。
     *
     * Example:
     * ```php
     * $a = 1;
     * $b = 2;
     * $phpcode = ';
     * $c = $a + $b;
     * return $c * 3;
     * ';
     * that(evaluate($phpcode, get_defined_vars()))->isSame(9);
     * ```
     *
     * @package ryunosuke\Functions\Package\misc
     *
     * @param string $phpcode 実行する php コード
     * @param array $contextvars コンテキスト変数配列
     * @param int $cachesize キャッシュするサイズ
     * @return mixed eval の return 値
     */
    public static function evaluate($phpcode, $contextvars = [], $cachesize = 256)
    {
        $cachefile = null;
        if ($cachesize && strlen($phpcode) >= $cachesize) {
            $cachefile = \ryunosuke\SimpleCache\Utils::function_configure('cachedir') . '/' . rawurlencode(__FUNCTION__) . '-' . sha1($phpcode) . '.php';
            if (!file_exists($cachefile)) {
                file_put_contents($cachefile, "<?php $phpcode", LOCK_EX);
            }
        }

        try {
            if ($cachefile) {
                /** @noinspection PhpMethodParametersCountMismatchInspection */
                return (static function () {
                    extract(func_get_arg(1));
                    return require func_get_arg(0);
                })($cachefile, $contextvars);
            }
            else {
                /** @noinspection PhpMethodParametersCountMismatchInspection */
                return (static function () {
                    extract(func_get_arg(1));
                    return eval(func_get_arg(0));
                })($phpcode, $contextvars);
            }
        }
        catch (\ParseError $ex) {
            $errline = $ex->getLine();
            $errline_1 = $errline - 1;
            $codes = preg_split('#\\R#u', $phpcode);
            $codes[$errline_1] = '>>> ' . $codes[$errline_1];

            $N = 5; // 前後の行数
            $message = $ex->getMessage();
            $message .= "\n" . implode("\n", array_slice($codes, max(0, $errline_1 - $N), $N * 2 + 1));
            if ($cachefile) {
                $message .= "\n in " . realpath($cachefile) . " on line " . $errline . "\n";
            }
            throw new \ParseError($message, $ex->getCode(), $ex);
        }
    }

    /**
     * php のコードのインデントを調整する
     *
     * baseline で基準インデント位置を指定する。
     * その基準インデントを削除した後、指定したインデントレベルでインデントするようなイメージ。
     *
     * Example:
     * ```php
     * $phpcode = '
     *     echo 123;
     *
     *     if (true) {
     *         echo 456;
     *     }
     * ';
     * // 数値指定は空白換算
     * that(indent_php($phpcode, 8))->isSame('
     *         echo 123;
     *
     *         if (true) {
     *             echo 456;
     *         }
     * ');
     * // 文字列を指定すればそれが使用される
     * that(indent_php($phpcode, "  "))->isSame('
     *   echo 123;
     *
     *   if (true) {
     *       echo 456;
     *   }
     * ');
     * // オプション指定
     * that(indent_php($phpcode, [
     *     'baseline'  => 1,    // 基準インデントの行番号（負数で下からの指定になる）
     *     'indent'    => 4,    // インデント指定（上記の数値・文字列指定はこれの糖衣構文）
     *     'trimempty' => true, // 空行を trim するか
     *     'heredoc'   => true, // Flexible Heredoc もインデントするか
     * ]))->isSame('
     *     echo 123;
     *
     *     if (true) {
     *         echo 456;
     *     }
     * ');
     * ```
     *
     * @package ryunosuke\Functions\Package\misc
     *
     * @param string $phpcode インデントする php コード
     * @param array|int|string $options オプション
     * @return string インデントされた php コード
     */
    public static function indent_php($phpcode, $options = [])
    {
        if (!is_array($options)) {
            $options = ['indent' => $options];
        }
        $options += [
            'baseline'  => 1,
            'indent'    => 0,
            'trimempty' => true,
            'heredoc'   => true,
        ];
        if (is_int($options['indent'])) {
            $options['indent'] = str_repeat(' ', $options['indent']);
        }

        $lines = preg_split('#\\R#u', $phpcode);
        $baseline = $options['baseline'];
        if ($baseline < 0) {
            $baseline = count($lines) + $baseline;
        }
        preg_match('@^[ \t]*@u', $lines[$baseline] ?? '', $matches);
        $indent = $matches[0] ?? '';

        $tmp = token_get_all("<?php $phpcode");
        array_shift($tmp);

        // トークンの正規化
        $tokens = [];
        for ($i = 0; $i < count($tmp); $i++) {
            if (is_string($tmp[$i])) {
                $tmp[$i] = [-1, $tmp[$i], null];
            }

            // 行コメントの分割（T_COMMENT には改行が含まれている）
            if ($tmp[$i][0] === T_COMMENT && preg_match('@^(#|//).*?(\\R)@um', $tmp[$i][1], $matches)) {
                $tmp[$i][1] = trim($tmp[$i][1]);
                if (($tmp[$i + 1][0] ?? null) === T_WHITESPACE) {
                    $tmp[$i + 1][1] = $matches[2] . $tmp[$i + 1][1];
                }
                else {
                    array_splice($tmp, $i + 1, 0, [[T_WHITESPACE, $matches[2], null]]);
                }
            }

            if ($options['heredoc']) {
                // 行コメントと同じ（T_START_HEREDOC には改行が含まれている）
                if ($tmp[$i][0] === T_START_HEREDOC && preg_match('@^(<<<).*?(\\R)@um', $tmp[$i][1], $matches)) {
                    $tmp[$i][1] = trim($tmp[$i][1]);
                    if (($tmp[$i + 1][0] ?? null) === T_ENCAPSED_AND_WHITESPACE) {
                        $tmp[$i + 1][1] = $matches[2] . $tmp[$i + 1][1];
                    }
                    else {
                        array_splice($tmp, $i + 1, 0, [[T_ENCAPSED_AND_WHITESPACE, $matches[2], null]]);
                    }
                }
                // php 7.3 において T_END_HEREDOC は必ず単一行になる
                if ($tmp[$i][0] === T_ENCAPSED_AND_WHITESPACE) {
                    if (($tmp[$i + 1][0] ?? null) === T_END_HEREDOC && preg_match('@^(\\s+)(.*)@um', $tmp[$i + 1][1], $matches)) {
                        $tmp[$i][1] = $tmp[$i][1] . $matches[1];
                        $tmp[$i + 1][1] = $matches[2];
                    }
                }
            }

            $tokens[] = $tmp[$i] + [3 => token_name($tmp[$i][0])];
        }

        // 改行を置換してインデント
        $hereing = false;
        foreach ($tokens as $i => $token) {
            if ($options['heredoc']) {
                if ($token[0] === T_START_HEREDOC) {
                    $hereing = true;
                }
                if ($token[0] === T_END_HEREDOC) {
                    $hereing = false;
                }
            }
            if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true) || ($hereing && $token[0] === T_ENCAPSED_AND_WHITESPACE)) {
                $token[1] = preg_replace("@(\\R)$indent@um", '$1' . $options['indent'], $token[1]);
            }
            if ($options['trimempty']) {
                if ($token[0] === T_WHITESPACE) {
                    $token[1] = preg_replace("@(\\R)[ \\t]+(\\R)@um", '$1$2', $token[1]);
                }
            }

            $tokens[$i] = $token;
        }
        return implode('', array_column($tokens, 1));
    }

    /**
     * php ファイルをパースして名前空間配列を返す
     *
     * ファイル内で use/use const/use function していたり、シンボルを定義していたりする箇所を検出して名前空間単位で返す。
     *
     * Example:
     * ```php
     * // このような php ファイルをパースすると・・・
     * file_set_contents(sys_get_temp_dir() . '/namespace.php', '
     * <?php
     * namespace NS1;
     * use ArrayObject as AO;
     * use function strlen as SL;
     * function InnerFunc(){}
     * class InnerClass{}
     * define("OUTER\\\\CONST", "OuterConst");
     *
     * namespace NS2;
     * use RuntimeException as RE;
     * use const COUNT_RECURSIVE as CR;
     * class InnerClass{}
     * const InnerConst = 123;
     * ');
     * // このような名前空間配列が得られる
     * that(parse_namespace(sys_get_temp_dir() . '/namespace.php'))->isSame([
     *     'NS1' => [
     *         'const'    => [],
     *         'function' => [
     *             'SL'        => 'strlen',
     *             'InnerFunc' => 'NS1\\InnerFunc',
     *         ],
     *         'alias'    => [
     *             'AO'         => 'ArrayObject',
     *             'InnerClass' => 'NS1\\InnerClass',
     *         ],
     *     ],
     *     'OUTER' => [
     *         'const'    => [
     *             'CONST' => 'OUTER\\CONST',
     *         ],
     *         'function' => [],
     *         'alias'    => [],
     *     ],
     *     'NS2' => [
     *         'const'    => [
     *             'CR'         => 'COUNT_RECURSIVE',
     *             'InnerConst' => 'NS2\\InnerConst',
     *         ],
     *         'function' => [],
     *         'alias'    => [
     *             'RE'         => 'RuntimeException',
     *             'InnerClass' => 'NS2\\InnerClass',
     *         ],
     *     ],
     * ]);
     * ```
     *
     * @package ryunosuke\Functions\Package\misc
     *
     * @param string $filename ファイル名
     * @param array $options オプション配列
     * @return array 名前空間配列
     */
    public static function parse_namespace($filename, $options = [])
    {
        $filename = realpath($filename);
        $filemtime = filemtime($filename);
        $options += [
            'cache' => null,
        ];
        if ($options['cache'] === null) {
            $options['cache'] = \ryunosuke\SimpleCache\Utils::cache($filename, fn() => $filemtime, 'filemtime') >= $filemtime;
        }
        if (!$options['cache']) {
            \ryunosuke\SimpleCache\Utils::cache($filename, null, 'filemtime');
            \ryunosuke\SimpleCache\Utils::cache($filename, null, __FUNCTION__);
        }
        return \ryunosuke\SimpleCache\Utils::cache($filename, function () use ($filename) {
            $stringify = function ($tokens) {
                // @codeCoverageIgnoreStart
                if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
                    return trim(implode('', array_column(array_filter($tokens, function ($token) {
                        /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
                        return in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE, T_STRING], true);
                    }), 1)), '\\');
                }
                // @codeCoverageIgnoreEnd
                return trim(implode('', array_column(array_filter($tokens, function ($token) {
                    return $token[0] === T_NS_SEPARATOR || $token[0] === T_STRING;
                }), 1)), '\\');
            };

            $keys = [
                0           => 'alias', // for use
                T_CLASS     => 'alias',
                T_INTERFACE => 'alias',
                T_TRAIT     => 'alias',
                T_STRING    => 'const', // for define
                T_CONST     => 'const',
                T_FUNCTION  => 'function',
            ];

            $contents = "?>" . file_get_contents($filename);
            $namespace = '';
            $tokens = [-1 => null];
            $result = [];
            while (true) {
                $tokens = \ryunosuke\SimpleCache\Utils::parse_php($contents, [
                    'flags'  => TOKEN_PARSE,
                    'begin'  => ["define", T_NAMESPACE, T_USE, T_CONST, T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT],
                    'end'    => ['{', ';', '(', T_EXTENDS, T_IMPLEMENTS],
                    'offset' => \ryunosuke\SimpleCache\Utils::last_key($tokens) + 1,
                ]);
                if (!$tokens) {
                    break;
                }
                $token = reset($tokens);
                // define は現在の名前空間とは無関係に名前空間定数を宣言することができる
                if ($token[0] === T_STRING && $token[1] === "define") {
                    $tokens = \ryunosuke\SimpleCache\Utils::parse_php($contents, [
                        'flags'  => TOKEN_PARSE,
                        'begin'  => [T_CONSTANT_ENCAPSED_STRING],
                        'end'    => [T_CONSTANT_ENCAPSED_STRING],
                        'offset' => \ryunosuke\SimpleCache\Utils::last_key($tokens),
                    ]);
                    $cname = substr(implode('', array_column($tokens, 1)), 1, -1);
                    $define = trim(json_decode("\"$cname\""), '\\');
                    [$ns, $nm] = \ryunosuke\SimpleCache\Utils::namespace_split($define);
                    if (!isset($result[$ns])) {
                        $result[$ns] = [
                            'const'    => [],
                            'function' => [],
                            'alias'    => [],
                        ];
                    }
                    $result[$ns][$keys[$token[0]]][$nm] = $define;
                }
                switch ($token[0]) {
                    case T_NAMESPACE:
                        $namespace = $stringify($tokens);
                        $result[$namespace] = [
                            'const'    => [],
                            'function' => [],
                            'alias'    => [],
                        ];
                        break;
                    case T_USE:
                        $tokenCorF = \ryunosuke\SimpleCache\Utils::array_find($tokens, fn($token) => ($token[0] === T_CONST || $token[0] === T_FUNCTION) ? $token[0] : 0, false);

                        $prefix = '';
                        if (end($tokens)[1] === '{') {
                            $prefix = $stringify($tokens);
                            $tokens = \ryunosuke\SimpleCache\Utils::parse_php($contents, [
                                'flags'  => TOKEN_PARSE,
                                'begin'  => ['{'],
                                'end'    => ['}'],
                                'offset' => \ryunosuke\SimpleCache\Utils::last_key($tokens),
                            ]);
                        }

                        $multi = \ryunosuke\SimpleCache\Utils::array_explode($tokens, fn($token) => $token[1] === ',');
                        foreach ($multi as $ttt) {
                            $as = \ryunosuke\SimpleCache\Utils::array_explode($ttt, fn($token) => $token[0] === T_AS);

                            $alias = $stringify($as[0]);
                            if (isset($as[1])) {
                                $result[$namespace][$keys[$tokenCorF]][$stringify($as[1])] = \ryunosuke\SimpleCache\Utils::concat($prefix, '\\') . $alias;
                            }
                            else {
                                $result[$namespace][$keys[$tokenCorF]][\ryunosuke\SimpleCache\Utils::namespace_split($alias)[1]] = \ryunosuke\SimpleCache\Utils::concat($prefix, '\\') . $alias;
                            }
                        }
                        break;
                    case T_CONST:
                    case T_FUNCTION:
                    case T_CLASS:
                    case T_INTERFACE:
                    case T_TRAIT:
                        $alias = $stringify($tokens);
                        if (strlen($alias)) {
                            $result[$namespace][$keys[$token[0]]][$alias] = \ryunosuke\SimpleCache\Utils::concat($namespace, '\\') . $alias;
                        }
                        // ブロック内に興味はないので進めておく（function 内 function などはあり得るが考慮しない）
                        if ($token[0] !== T_CONST) {
                            $tokens = \ryunosuke\SimpleCache\Utils::parse_php($contents, [
                                'flags'  => TOKEN_PARSE,
                                'begin'  => ['{'],
                                'end'    => ['}'],
                                'offset' => \ryunosuke\SimpleCache\Utils::last_key($tokens),
                            ]);
                            break;
                        }
                }
            }
            return $result;
        }, __FUNCTION__);
    }

    /**
     * php のコード断片をパースする
     *
     * 結果配列は token_get_all したものだが、「字句の場合に文字列で返す」仕様は適用されずすべて配列で返す。
     * つまり必ず `[TOKENID, TOKEN, LINE, POS]` で返す。
     *
     * @todo 現在の仕様では php タグが自動で付与されるが、標準と異なり直感的ではないのでその仕様は除去する
     * @todo そもそも何がしたいのかよくわからない関数になってきたので動作の洗い出しが必要
     *
     * Example:
     * ```php
     * $phpcode = 'namespace Hogera;
     * class Example
     * {
     *     // something
     * }';
     *
     * // namespace ～ ; を取得
     * $part = parse_php($phpcode, [
     *     'begin' => T_NAMESPACE,
     *     'end'   => ';',
     * ]);
     * that(implode('', array_column($part, 1)))->isSame('namespace Hogera;');
     *
     * // class ～ { を取得
     * $part = parse_php($phpcode, [
     *     'begin' => T_CLASS,
     *     'end'   => '{',
     * ]);
     * that(implode('', array_column($part, 1)))->isSame("class Example\n{");
     * ```
     *
     * @package ryunosuke\Functions\Package\misc
     *
     * @param string $phpcode パースする php コード
     * @param array|int $option パースオプション
     * @return array トークン配列
     */
    public static function parse_php($phpcode, $option = [])
    {
        if (is_int($option)) {
            $option = ['flags' => $option];
        }

        $default = [
            'phptag'         => true, // 初めに php タグを付けるか
            'short_open_tag' => null, // ショートオープンタグを扱うか（null だと余計なことはせず ini に従う）
            'line'           => [],   // 行の範囲（以上以下）
            'position'       => [],   // 文字位置の範囲（以上以下）
            'begin'          => [],   // 開始トークン
            'end'            => [],   // 終了トークン
            'offset'         => 0,    // 開始トークン位置
            'flags'          => 0,    // token_get_all の $flags. TOKEN_PARSE を与えると ParseError が出ることがあるのでデフォルト 0
            'cache'          => true, // キャッシュするか否か
            'greedy'         => false,// end と nest か一致したときに処理を継続するか
            'nest_token'     => [
                ')' => '(',
                '}' => '{',
                ']' => '[',
            ],
        ];
        $option += $default;

        $cachekey = \ryunosuke\SimpleCache\Utils::var_hash($phpcode) . $option['flags'] . '-' . $option['phptag'] . '-' . var_export($option['short_open_tag'], true);
        static $cache = [];
        if (!($option['cache'] && isset($cache[$cachekey]))) {
            $phptag = $option['phptag'] ? '<?php ' : '';
            $phpcode = $phptag . $phpcode;
            $position = -strlen($phptag);

            $tokens = [];
            $tmp = token_get_all($phpcode, $option['flags']);
            for ($i = 0; $i < count($tmp); $i++) {
                $token = $tmp[$i];

                // token_get_all の結果は微妙に扱いづらいので少し調整する（string/array だったり、名前変換の必要があったり）
                if (!is_array($token)) {
                    $last = $tokens[count($tokens) - 1] ?? [null, 1, 0];
                    $token = [ord($token), $token, $last[2] + preg_match_all('/(?:\r\n|\r|\n)/', $last[1])];
                }

                // @codeCoverageIgnoreStart
                if ($option['short_open_tag'] === true && $token[0] === T_INLINE_HTML && ($p = strpos($token[1], '<?')) !== false) {
                    $newtokens = [];
                    $nlcount = 0;

                    if ($p !== 0) {
                        $html = substr($token[1], 0, $p);
                        $nlcount = preg_match_all('#\r\n|\r|\n#u', $html);
                        $newtokens[] = [T_INLINE_HTML, $html, $token[2]];
                    }

                    $code = substr($token[1], $p + 2);
                    $subtokens = token_get_all("<?php $code");
                    $subtokens[0][1] = '<?';
                    foreach ($subtokens as $subtoken) {
                        if (is_array($subtoken)) {
                            $subtoken[2] += $token[2] + $nlcount - 1;
                        }
                        $newtokens[] = $subtoken;
                    }

                    array_splice($tmp, $i + 1, 0, $newtokens);
                    continue;
                }
                if ($option['short_open_tag'] === false && $token[0] === T_OPEN_TAG && $token[1] === '<?') {
                    for ($j = $i + 1; $j < count($tmp); $j++) {
                        if ($tmp[$j][0] === T_CLOSE_TAG) {
                            break;
                        }
                    }
                    $html = implode('', array_map(fn($token) => is_array($token) ? $token[1] : $token, array_slice($tmp, $i, $j - $i + 1)));
                    array_splice($tmp, $i + 1, $j - $i, [[T_INLINE_HTML, $html, $token[2]]]);
                    continue;
                }
                // @codeCoverageIgnoreEnd

                $token[] = $position;
                if ($option['flags'] & \ryunosuke\SimpleCache\Utils::TOKEN_NAME) {
                    $token[] = token_name($token[0]);
                }

                $position += strlen($token[1]);
                $tokens[] = $token;
            }
            // @codeCoverageIgnoreStart
            if ($option['short_open_tag'] === false) {
                for ($i = 0; $i < count($tokens); $i++) {
                    if ($tokens[$i][0] === T_INLINE_HTML && isset($tokens[$i + 1]) && $tokens[$i + 1][0] === T_INLINE_HTML) {
                        $tokens[$i][1] .= $tokens[$i + 1][1];
                        array_splice($tokens, $i + 1, 1, []);
                        $i--;
                    }
                }
            }
            // @codeCoverageIgnoreEnd
            $cache[$cachekey] = $tokens;
        }
        $tokens = $cache[$cachekey];

        $lines = $option['line'] + [-PHP_INT_MAX, PHP_INT_MAX];
        $positions = $option['position'] + [-PHP_INT_MAX, PHP_INT_MAX];
        $begin_tokens = (array) $option['begin'];
        $end_tokens = (array) $option['end'];
        $nest_tokens = $option['nest_token'];
        $greedy = $option['greedy'];

        $result = [];
        $starting = !$begin_tokens;
        $nesting = 0;
        $offset = is_array($option['offset']) ? ($option['offset'][0] ?? 0) : $option['offset'];
        $endset = is_array($option['offset']) ? ($option['offset'][1] ?? count($tokens)) : count($tokens);

        for ($i = $offset; $i < $endset; $i++) {
            $token = $tokens[$i];

            if ($lines[0] > $token[2]) {
                continue;
            }
            if ($lines[1] < $token[2]) {
                continue;
            }
            if ($positions[0] > $token[3]) {
                continue;
            }
            if ($positions[1] < $token[3]) {
                continue;
            }

            foreach ($begin_tokens as $t) {
                if ($t === $token[0] || $t === $token[1]) {
                    $starting = true;
                    break;
                }
            }
            if (!$starting) {
                continue;
            }

            $result[$i] = $token;

            foreach ($nest_tokens as $end_nest => $start_nest) {
                if ($token[0] === $start_nest || $token[1] === $start_nest) {
                    $nesting++;
                }
                if ($token[0] === $end_nest || $token[1] === $end_nest) {
                    $nesting--;
                }
            }

            foreach ($end_tokens as $t) {
                if ($t === $token[0] || $t === $token[1]) {
                    if ($nesting <= 0 || ($nesting === 1 && in_array($t, $nest_tokens, true))) {
                        if ($nesting === 0 && $greedy && isset($nest_tokens[$t])) {
                            break;
                        }
                        break 2;
                    }
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * エイリアス名を完全修飾名に解決する
     *
     * 例えばあるファイルのある名前空間で `use Hoge\Fuga\Piyo;` してるときの `Piyo` を `Hoge\Fuga\Piyo` に解決する。
     *
     * Example:
     * ```php
     * // このような php ファイルがあるとして・・・
     * file_set_contents(sys_get_temp_dir() . '/symbol.php', '
     * <?php
     * namespace vendor\NS;
     *
     * use ArrayObject as AO;
     * use function strlen as SL;
     *
     * function InnerFunc(){}
     * class InnerClass{}
     * ');
     * // 下記のように解決される
     * that(resolve_symbol('AO', sys_get_temp_dir() . '/symbol.php'))->isSame('ArrayObject');
     * that(resolve_symbol('SL', sys_get_temp_dir() . '/symbol.php'))->isSame('strlen');
     * that(resolve_symbol('InnerFunc', sys_get_temp_dir() . '/symbol.php'))->isSame('vendor\\NS\\InnerFunc');
     * that(resolve_symbol('InnerClass', sys_get_temp_dir() . '/symbol.php'))->isSame('vendor\\NS\\InnerClass');
     * ```
     *
     * @package ryunosuke\Functions\Package\misc
     *
     * @param string $shortname エイリアス名
     * @param string|array $nsfiles ファイル名 or [ファイル名 => 名前空間名]
     * @param array $targets エイリアスタイプ（'const', 'function', 'alias' のいずれか）
     * @return string|null 完全修飾名。解決できなかった場合は null
     */
    public static function resolve_symbol(string $shortname, $nsfiles, $targets = ['const', 'function', 'alias'])
    {
        // 既に完全修飾されている場合は何もしない
        if (($shortname[0] ?? null) === '\\') {
            return $shortname;
        }

        // use Inner\Space のような名前空間の use の場合を考慮する
        $parts = explode('\\', $shortname, 2);
        $prefix = isset($parts[1]) ? array_shift($parts) : null;

        if (is_string($nsfiles)) {
            $nsfiles = [$nsfiles => []];
        }

        $targets = (array) $targets;
        foreach ($nsfiles as $filename => $namespaces) {
            $namespaces = array_flip(array_map(fn($n) => trim($n, '\\'), (array) $namespaces));
            foreach (\ryunosuke\SimpleCache\Utils::parse_namespace($filename) as $namespace => $ns) {
                /** @noinspection PhpIllegalArrayKeyTypeInspection */
                if (!$namespaces || isset($namespaces[$namespace])) {
                    if (isset($ns['alias'][$prefix])) {
                        return $ns['alias'][$prefix] . '\\' . implode('\\', $parts);
                    }
                    foreach ($targets as $target) {
                        if (isset($ns[$target][$shortname])) {
                            return $ns[$target][$shortname];
                        }
                    }
                }
            }
        }
        return null;
    }

    /**
     * callable のコードブロックを返す
     *
     * 返り値は2値の配列。0番目の要素が定義部、1番目の要素が処理部を表す。
     *
     * Example:
     * ```php
     * list($meta, $body) = callable_code(function (...$args) {return true;});
     * that($meta)->isSame('function (...$args)');
     * that($body)->isSame('{return true;}');
     *
     * // ReflectionFunctionAbstract を渡しても動作する
     * list($meta, $body) = callable_code(new \ReflectionFunction(function (...$args) {return true;}));
     * that($meta)->isSame('function (...$args)');
     * that($body)->isSame('{return true;}');
     * ```
     *
     * @package ryunosuke\Functions\Package\reflection
     *
     * @param callable|\ReflectionFunctionAbstract $callable コードを取得する callable
     * @return array ['定義部分', '{処理コード}']
     */
    public static function callable_code($callable)
    {
        $ref = $callable instanceof \ReflectionFunctionAbstract ? $callable : \ryunosuke\SimpleCache\Utils::reflect_callable($callable);
        $contents = file($ref->getFileName());
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();
        $codeblock = implode('', array_slice($contents, $start - 1, $end - $start + 1));

        $meta = \ryunosuke\SimpleCache\Utils::parse_php("<?php $codeblock", [
            'begin' => [T_FN, T_FUNCTION],
            'end'   => ['{', T_DOUBLE_ARROW],
        ]);
        $end = array_pop($meta);

        if ($end[0] === T_DOUBLE_ARROW) {
            $body = \ryunosuke\SimpleCache\Utils::parse_php("<?php $codeblock", [
                'begin'  => T_DOUBLE_ARROW,
                'end'    => [';', ',', ')'],
                'offset' => \ryunosuke\SimpleCache\Utils::last_key($meta),
                'greedy' => true,
            ]);
            $body = array_slice($body, 1, -1);
        }
        else {
            $body = \ryunosuke\SimpleCache\Utils::parse_php("<?php $codeblock", [
                'begin'  => '{',
                'end'    => '}',
                'offset' => \ryunosuke\SimpleCache\Utils::last_key($meta),
            ]);
        }

        return [trim(implode('', array_column($meta, 1))), trim(implode('', array_column($body, 1)))];
    }

    /**
     * callable の引数の数を返す
     *
     * クロージャはキャッシュされない。毎回リフレクションを生成し、引数の数を調べてそれを返す。
     * （クロージャには一意性がないので key-value なキャッシュが適用できない）。
     * ので、ループ内で使ったりすると目に見えてパフォーマンスが低下するので注意。
     *
     * Example:
     * ```php
     * // trim の引数は2つ
     * that(parameter_length('trim'))->isSame(2);
     * // trim の必須引数は1つ
     * that(parameter_length('trim', true))->isSame(1);
     * ```
     *
     * @package ryunosuke\Functions\Package\reflection
     *
     * @param callable $callable 対象 callable
     * @param bool $require_only true を渡すと必須パラメータの数を返す
     * @param bool $thought_variadic 可変引数を考慮するか。 true を渡すと可変引数の場合に無限長を返す
     * @return int 引数の数
     */
    public static function parameter_length($callable, $require_only = false, $thought_variadic = false)
    {
        // クロージャの $call_name には一意性がないのでキャッシュできない（spl_object_hash でもいいが、かなり重複するので完全ではない）
        if ($callable instanceof \Closure) {
            /** @var \ReflectionFunctionAbstract $ref */
            $ref = \ryunosuke\SimpleCache\Utils::reflect_callable($callable);
            if ($thought_variadic && $ref->isVariadic()) {
                return INF;
            }
            elseif ($require_only) {
                return $ref->getNumberOfRequiredParameters();
            }
            else {
                return $ref->getNumberOfParameters();
            }
        }

        // $call_name 取得
        is_callable($callable, false, $call_name);

        $cache = \ryunosuke\SimpleCache\Utils::cache($call_name, function () use ($callable) {
            /** @var \ReflectionFunctionAbstract $ref */
            $ref = \ryunosuke\SimpleCache\Utils::reflect_callable($callable);
            return [
                '00' => $ref->getNumberOfParameters(),
                '01' => $ref->isVariadic() ? INF : $ref->getNumberOfParameters(),
                '10' => $ref->getNumberOfRequiredParameters(),
                '11' => $ref->isVariadic() ? INF : $ref->getNumberOfRequiredParameters(),
            ];
        }, __FUNCTION__);
        return $cache[(int) $require_only . (int) $thought_variadic];
    }

    /**
     * callable から ReflectionFunctionAbstract を生成する
     *
     * Example:
     * ```php
     * that(reflect_callable('sprintf'))->isInstanceOf(\ReflectionFunction::class);
     * that(reflect_callable('\Closure::bind'))->isInstanceOf(\ReflectionMethod::class);
     * ```
     *
     * @package ryunosuke\Functions\Package\reflection
     *
     * @param callable $callable 対象 callable
     * @return \ReflectionFunction|\ReflectionMethod リフレクションインスタンス
     */
    public static function reflect_callable($callable)
    {
        // callable チェック兼 $call_name 取得
        if (!is_callable($callable, true, $call_name)) {
            throw new \InvalidArgumentException("'$call_name' is not callable");
        }

        if ($callable instanceof \Closure || strpos($call_name, '::') === false) {
            return new \ReflectionFunction($callable);
        }
        else {
            [$class, $method] = explode('::', $call_name, 2);
            // for タイプ 5: 相対指定による静的クラスメソッドのコール (PHP 5.3.0 以降)
            if (strpos($method, 'parent::') === 0) {
                [, $method] = explode('::', $method);
                return (new \ReflectionClass($class))->getParentClass()->getMethod($method);
            }
            return new \ReflectionMethod($class, $method);
        }
    }

    /**
     * strcat の空文字回避版
     *
     * 基本は strcat と同じ。ただし、**引数の内1つでも空文字を含むなら空文字を返す**。
     *
     * 「プレフィックスやサフィックスを付けたいんだけど、空文字の場合はそのままで居て欲しい」という状況はまれによくあるはず。
     * コードで言えば `strlen($string) ? 'prefix-' . $string : '';` のようなもの。
     * 可変引数なので 端的に言えば mysql の CONCAT みたいな動作になる（あっちは NULL だが）。
     *
     * ```php
     * that(concat('prefix-', 'middle', '-suffix'))->isSame('prefix-middle-suffix');
     * that(concat('prefix-', '', '-suffix'))->isSame('');
     * ```
     *
     * @package ryunosuke\Functions\Package\strings
     *
     * @param mixed ...$variadic 結合する文字列（可変引数）
     * @return string 結合した文字列
     */
    public static function concat(...$variadic)
    {
        $result = '';
        foreach ($variadic as $s) {
            if (strlen($s = (string) $s) === 0) {
                return '';
            }
            $result .= $s;
        }
        return $result;
    }

    /**
     * 文字列を名前空間とローカル名に区切ってタプルで返す
     *
     * class_namespace/class_shorten や function_shorten とほぼ同じだが下記の違いがある。
     *
     * - あくまで文字列として処理する
     *     - 例えば class_namespace は get_class されるが、この関数は（いうなれば） strval される
     * - \\ を trim しないし、特別扱いもしない
     *     - `ns\\hoge` と `\\ns\\hoge` で返り値が微妙に異なる
     *     - `ns\\` のような場合は名前空間だけを返す
     *
     * Example:
     * ```php
     * that(namespace_split('ns\\hoge'))->isSame(['ns', 'hoge']);
     * that(namespace_split('hoge'))->isSame(['', 'hoge']);
     * that(namespace_split('ns\\'))->isSame(['ns', '']);
     * that(namespace_split('\\hoge'))->isSame(['', 'hoge']);
     * ```
     *
     * @package ryunosuke\Functions\Package\strings
     *
     * @param string $string 対象文字列
     * @return array [namespace, localname]
     */
    public static function namespace_split($string)
    {
        $pos = strrpos($string, '\\');
        if ($pos === false) {
            return ['', $string];
        }
        return [substr($string, 0, $pos), substr($string, $pos + 1)];
    }

    /**
     * 指定文字列で始まるか調べる
     *
     * $with に配列を渡すといずれかで始まるときに true を返す。
     *
     * Example:
     * ```php
     * that(starts_with('abcdef', 'abc'))->isTrue();
     * that(starts_with('abcdef', 'ABC', true))->isTrue();
     * that(starts_with('abcdef', 'xyz'))->isFalse();
     * that(starts_with('abcdef', ['a', 'b', 'c']))->isTrue();
     * that(starts_with('abcdef', ['x', 'y', 'z']))->isFalse();
     * ```
     *
     * @package ryunosuke\Functions\Package\strings
     *
     * @param string $string 探される文字列
     * @param string|string[] $with 探す文字列
     * @param bool $case_insensitivity 大文字小文字を無視するか
     * @return bool 指定文字列で始まるなら true を返す
     */
    public static function starts_with($string, $with, $case_insensitivity = false)
    {
        assert(\ryunosuke\SimpleCache\Utils::is_stringable($string));

        foreach ((array) $with as $w) {
            assert(strlen($w));

            if (\ryunosuke\SimpleCache\Utils::str_equals(substr($string, 0, strlen($w)), $w, $case_insensitivity)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 文字列比較の関数版
     *
     * 文字列以外が与えられた場合は常に false を返す。ただし __toString を実装したオブジェクトは別。
     *
     * Example:
     * ```php
     * that(str_equals('abc', 'abc'))->isTrue();
     * that(str_equals('abc', 'ABC', true))->isTrue();
     * that(str_equals('\0abc', '\0abc'))->isTrue();
     * ```
     *
     * @package ryunosuke\Functions\Package\strings
     *
     * @param string $str1 文字列1
     * @param string $str2 文字列2
     * @param bool $case_insensitivity 大文字小文字を無視するか
     * @return bool 同じ文字列なら true
     */
    public static function str_equals($str1, $str2, $case_insensitivity = false)
    {
        // __toString 実装のオブジェクトは文字列化する（strcmp がそうなっているから）
        if (is_object($str1) && method_exists($str1, '__toString')) {
            $str1 = (string) $str1;
        }
        if (is_object($str2) && method_exists($str2, '__toString')) {
            $str2 = (string) $str2;
        }

        // この関数は === の関数版という位置づけなので例外は投げないで不一致とみなす
        if (!is_string($str1) || !is_string($str2)) {
            return false;
        }

        if ($case_insensitivity) {
            return strcasecmp($str1, $str2) === 0;
        }

        return $str1 === $str2;
    }

    /**
     * シンプルにキャッシュする
     *
     * この関数は get/set/delete を兼ねる。
     * キャッシュがある場合はそれを返し、ない場合は $provider を呼び出してその結果をキャッシュしつつそれを返す。
     *
     * $provider に null を与えるとキャッシュの削除となる。
     *
     * Example:
     * ```php
     * $provider = fn() => rand();
     * // 乱数を返す処理だが、キャッシュされるので同じ値になる
     * $rand1 = cache('rand', $provider);
     * $rand2 = cache('rand', $provider);
     * that($rand1)->isSame($rand2);
     * // $provider に null を与えると削除される
     * cache('rand', null);
     * $rand3 = cache('rand', $provider);
     * that($rand1)->isNotSame($rand3);
     * ```
     *
     * @package ryunosuke\Functions\Package\utility
     *
     * @param string $key キャッシュのキー
     * @param ?callable $provider キャッシュがない場合にコールされる callable
     * @param ?string $namespace 名前空間
     * @return mixed キャッシュ
     */
    public static function cache($key, $provider, $namespace = null)
    {
        static $cacheobject;
        $cacheobject ??= new class(\ryunosuke\SimpleCache\Utils::function_configure('cachedir')) {
            const CACHE_EXT = '.php-cache';

            /** @var string キャッシュディレクトリ */
            private $cachedir;

            /** @var array 内部キャッシュ */
            private $cache;

            /** @var array 変更感知配列 */
            private $changed;

            public function __construct($cachedir)
            {
                $this->cachedir = $cachedir;
                $this->cache = [];
                $this->changed = [];
            }

            public function __destruct()
            {
                // 変更されているもののみ保存
                foreach ($this->changed as $namespace => $dummy) {
                    $filepath = $this->cachedir . '/' . rawurlencode($namespace) . self::CACHE_EXT;
                    $content = "<?php\nreturn " . var_export($this->cache[$namespace], true) . ";\n";

                    $temppath = tempnam(sys_get_temp_dir(), 'cache');
                    if (file_put_contents($temppath, $content) !== false) {
                        @chmod($temppath, 0644);
                        if (!@rename($temppath, $filepath)) {
                            @unlink($temppath); // @codeCoverageIgnore
                        }
                    }
                }
            }

            public function has($namespace, $key)
            {
                // ファイルから読み込む必要があるので get しておく
                $this->get($namespace, $key);
                return array_key_exists($key, $this->cache[$namespace]);
            }

            public function get($namespace, $key)
            {
                // 名前空間自体がないなら作る or 読む
                if (!isset($this->cache[$namespace])) {
                    $nsarray = [];
                    $cachpath = $this->cachedir . '/' . rawurldecode($namespace) . self::CACHE_EXT;
                    if (file_exists($cachpath)) {
                        $nsarray = require $cachpath;
                    }
                    $this->cache[$namespace] = $nsarray;
                }

                return $this->cache[$namespace][$key] ?? null;
            }

            public function set($namespace, $key, $value)
            {
                // 新しい値が来たら変更フラグを立てる
                if (!isset($this->cache[$namespace]) || !array_key_exists($key, $this->cache[$namespace]) || $this->cache[$namespace][$key] !== $value) {
                    $this->changed[$namespace] = true;
                }

                $this->cache[$namespace][$key] = $value;
            }

            public function delete($namespace, $key)
            {
                $this->changed[$namespace] = true;
                unset($this->cache[$namespace][$key]);
            }

            public function clear()
            {
                // インメモリ情報をクリアして・・・
                $this->cache = [];
                $this->changed = [];

                // ファイルも消す
                foreach (glob($this->cachedir . '/*' . self::CACHE_EXT) as $file) {
                    unlink($file);
                }
            }
        };

        // flush (for test)
        if ($key === null) {
            if ($provider === null) {
                $cacheobject->clear();
            }
            $cacheobject = null;
            return;
        }

        $namespace ??= __FILE__;

        $exist = $cacheobject->has($namespace, $key);
        if ($provider === null) {
            $cacheobject->delete($namespace, $key);
            return $exist;
        }
        if (!$exist) {
            $cacheobject->set($namespace, $key, $provider());
        }
        return $cacheobject->get($namespace, $key);
    }

    /**
     * 本ライブラリの設定を行う
     *
     * 各関数の挙動を変えたり、デフォルトオプションを設定できる。
     *
     * @package ryunosuke\Functions\Package\utility
     *
     * @param array|string $option 設定。文字列指定時はその値を返す
     * @return array|string 設定値
     */
    public static function function_configure($option)
    {
        static $config = [];

        // default
        $config['cachedir'] ??= sys_get_temp_dir() . DIRECTORY_SEPARATOR . strtr(__NAMESPACE__, ['\\' => '%']);
        $config['placeholder'] ??= '';
        $config['var_stream'] ??= get_cfg_var('rfunc.var_stream') ?: 'VarStreamV010000';          // for compatible
        $config['memory_stream'] ??= get_cfg_var('rfunc.memory_stream') ?: 'MemoryStreamV010000'; // for compatible
        $config['chain.version'] ??= 1;
        $config['chain.nullsafe'] ??= false;

        // setting
        if (is_array($option)) {
            foreach ($option as $name => $entry) {
                $option[$name] = $config[$name] ?? null;
                switch ($name) {
                    default:
                        $config[$name] = $entry;
                        break;
                    case 'cachedir':
                        $entry ??= $config[$name];
                        if (!file_exists($entry)) {
                            @mkdir($entry, 0777 & (~umask()), true);
                        }
                        $config[$name] = realpath($entry);
                        break;
                    case 'placeholder':
                        if (strlen($entry)) {
                            $entry = ltrim($entry[0] === '\\' ? $entry : __NAMESPACE__ . '\\' . $entry, '\\');
                            if (!defined($entry)) {
                                define($entry, tmpfile() ?: [] ?: '' ?: 0.0 ?: null ?: false);
                            }
                            if (!is_resource(constant($entry))) {
                                // もしリソースじゃないと一意性が保てず致命的になるので例外を投げる
                                throw new \RuntimeException('placeholder is not resource'); // @codeCoverageIgnore
                            }
                            $config[$name] = $entry;
                        }
                        break;
                }
            }
            return $option;
        }

        // getting
        if (is_string($option)) {
            switch ($option) {
                default:
                    return $config[$option] ?? null;
                case 'cachedir':
                    $dirname = $config[$option];
                    if (!file_exists($dirname)) {
                        @mkdir($dirname, 0777 & (~umask()), true); // @codeCoverageIgnore
                    }
                    return realpath($dirname);
            }
        }

        throw new \InvalidArgumentException(sprintf('$option is unknown type(%s)', gettype($option)));
    }

    /**
     * array キャストの関数版
     *
     * intval とか strval とかの array 版。
     * ただキャストするだけだが、関数なのでコールバックとして使える。
     *
     * $recursive を true にすると再帰的に適用する（デフォルト）。
     * 入れ子オブジェクトを配列化するときなどに使える。
     *
     * Example:
     * ```php
     * // キャストなので基本的には配列化される
     * that(arrayval(123))->isSame([123]);
     * that(arrayval('str'))->isSame(['str']);
     * that(arrayval([123]))->isSame([123]); // 配列は配列のまま
     *
     * // $recursive = false にしない限り再帰的に適用される
     * $stdclass = stdclass(['key' => 'val']);
     * that(arrayval([$stdclass], true))->isSame([['key' => 'val']]); // true なので中身も配列化される
     * that(arrayval([$stdclass], false))->isSame([$stdclass]);       // false なので中身は変わらない
     * ```
     *
     * @package ryunosuke\Functions\Package\var
     *
     * @param mixed $var array 化する値
     * @param bool $recursive 再帰的に行うなら true
     * @return array array 化した配列
     */
    public static function arrayval($var, $recursive = true)
    {
        // return json_decode(json_encode($var), true);

        // 無駄なループを回したくないので非再帰で配列の場合はそのまま返す
        if (!$recursive && is_array($var)) {
            return $var;
        }

        if (\ryunosuke\SimpleCache\Utils::is_primitive($var)) {
            return (array) $var;
        }

        $result = [];
        foreach ($var as $k => $v) {
            if ($recursive && !\ryunosuke\SimpleCache\Utils::is_primitive($v)) {
                $v = \ryunosuke\SimpleCache\Utils::arrayval($v, $recursive);
            }
            $result[$k] = $v;
        }
        return $result;
    }

    /**
     * 値が空か検査する
     *
     * `empty` とほぼ同じ。ただし
     *
     * - string: "0"
     * - countable でない object
     * - countable である object で count() > 0
     *
     * は false 判定する。
     * ただし、 $empty_stcClass に true を指定すると「フィールドのない stdClass」も true を返すようになる。
     * これは stdClass の立ち位置はかなり特殊で「フィールドアクセスできる組み込み配列」のような扱いをされることが多いため。
     * （例えば `json_decode('{}')` は stdClass を返すが、このような状況は空判定したいことが多いだろう）。
     *
     * なお、関数の仕様上、未定義変数を true 判定することはできない。
     * 未定義変数をチェックしたい状況は大抵の場合コードが悪いが `$array['key1']['key2']` を調べたいことはある。
     * そういう時には使えない（?? する必要がある）。
     *
     * 「 `if ($var) {}` で十分なんだけど "0" が…」という状況はまれによくあるはず。
     *
     * Example:
     * ```php
     * // この辺は empty と全く同じ
     * that(is_empty(null))->isTrue();
     * that(is_empty(false))->isTrue();
     * that(is_empty(0))->isTrue();
     * that(is_empty(''))->isTrue();
     * // この辺だけが異なる
     * that(is_empty('0'))->isFalse();
     * // 第2引数に true を渡すと空の stdClass も empty 判定される
     * $stdclass = new \stdClass();
     * that(is_empty($stdclass, true))->isTrue();
     * // フィールドがあれば empty ではない
     * $stdclass->hoge = 123;
     * that(is_empty($stdclass, true))->isFalse();
     * ```
     *
     * @package ryunosuke\Functions\Package\var
     *
     * @param mixed $var 判定する値
     * @param bool $empty_stdClass 空の stdClass を空とみなすか
     * @return bool 空なら true
     */
    public static function is_empty($var, $empty_stdClass = false)
    {
        // object は is_countable 次第
        if (is_object($var)) {
            // が、 stdClass だけは特別扱い（stdClass は継承もできるので、クラス名で判定する（継承していたらそれはもう stdClass ではないと思う））
            if ($empty_stdClass && get_class($var) === 'stdClass') {
                return !(array) $var;
            }
            if (is_countable($var)) {
                return !count($var);
            }
            return false;
        }

        // "0" は false
        if ($var === '0') {
            return false;
        }

        // 上記以外は empty に任せる
        return empty($var);
    }

    /**
     * 値が複合型でないか検査する
     *
     * 「複合型」とはオブジェクトと配列のこと。
     * つまり
     *
     * - is_scalar($var) || is_null($var) || is_resource($var)
     *
     * と同義（!is_array($var) && !is_object($var) とも言える）。
     *
     * Example:
     * ```php
     * that(is_primitive(null))->isTrue();
     * that(is_primitive(false))->isTrue();
     * that(is_primitive(123))->isTrue();
     * that(is_primitive(STDIN))->isTrue();
     * that(is_primitive(new \stdClass))->isFalse();
     * that(is_primitive(['array']))->isFalse();
     * ```
     *
     * @package ryunosuke\Functions\Package\var
     *
     * @param mixed $var 調べる値
     * @return bool 複合型なら false
     */
    public static function is_primitive($var)
    {
        return is_scalar($var) || is_null($var) || is_resource($var);
    }

    /**
     * 変数が文字列化できるか調べる
     *
     * 「配列」「__toString を持たないオブジェクト」が false になる。
     * （厳密に言えば配列は "Array" になるので文字列化できるといえるがここでは考えない）。
     *
     * Example:
     * ```php
     * // こいつらは true
     * that(is_stringable(null))->isTrue();
     * that(is_stringable(true))->isTrue();
     * that(is_stringable(3.14))->isTrue();
     * that(is_stringable(STDOUT))->isTrue();
     * that(is_stringable(new \Exception()))->isTrue();
     * // こいつらは false
     * that(is_stringable(new \ArrayObject()))->isFalse();
     * that(is_stringable([1, 2, 3]))->isFalse();
     * ```
     *
     * @package ryunosuke\Functions\Package\var
     *
     * @param mixed $var 調べる値
     * @return bool 文字列化できるなら true
     */
    public static function is_stringable($var)
    {
        if (is_array($var)) {
            return false;
        }
        if (is_object($var) && !method_exists($var, '__toString')) {
            return false;
        }
        return true;
    }

    /**
     * var_export を色々と出力できるようにしたもの
     *
     * php のコードに落とし込むことで serialize と比較してかなり高速に動作する。
     *
     * 各種オブジェクトやクロージャ、循環参照を含む配列など様々なものが出力できる。
     * ただし、下記は不可能あるいは復元不可（今度も対応するかは未定）。
     *
     * - Generator クラス
     * - 特定の内部クラス（PDO など）
     * - リソース
     * - アロー関数によるクロージャ
     *
     * オブジェクトは「リフレクションを用いてコンストラクタなしで生成してプロパティを代入する」という手法で復元する。
     * のでクラスによってはおかしな状態で復元されることがある（大体はリソース型のせいだが…）。
     * sleep, wakeup, Serializable などが実装されているとそれはそのまま機能する。
     * set_state だけは呼ばれないので注意。
     *
     * クロージャはコード自体を引っ張ってきて普通に function (){} として埋め込む。
     * クラス名のエイリアスや use, $this バインドなど可能な限り復元するが、おそらくあまりに複雑なことをしてると失敗する。
     *
     * 軽くベンチを取ったところ、オブジェクトを含まない純粋な配列の場合、serialize の 200 倍くらいは速い（それでも var_export の方が速いが…）。
     * オブジェクトを含めば含むほど遅くなり、全要素がオブジェクトになると serialize と同程度になる。
     * 大体 var_export:var_export3:serialize が 1:5:1000 くらい。
     *
     * @package ryunosuke\Functions\Package\var
     *
     * @param mixed $value エクスポートする値
     * @param bool|array $return 返り値として返すなら true. 配列を与えるとオプションになる
     * @return string エクスポートされた文字列
     */
    public static function var_export3($value, $return = false)
    {
        // 原則として var_export に合わせたいのでデフォルトでは bool: false で単に出力するのみとする
        if (is_bool($return)) {
            $return = [
                'return' => $return,
            ];
        }
        $options = $return;
        $options += [
            'format'  => 'pretty', // pretty or minify
            'outmode' => null,     // null: 本体のみ, 'eval': return ...;, 'file': <?php return ...;
        ];
        $options['return'] ??= !!$options['outmode'];

        $var_manager = new class() {
            private $vars = [];
            private $refs = [];

            private function arrayHasReference($array)
            {
                foreach ($array as $k => $v) {
                    $ref = \ReflectionReference::fromArrayElement($array, $k);
                    if ($ref) {
                        return true;
                    }
                    if (is_array($v) && $this->arrayHasReference($v)) {
                        return true;
                    }
                }
                return false;
            }

            public function varId($var)
            {
                // オブジェクトは明確な ID が取れる（closure/object の区分けに処理的な意味はない）
                if (is_object($var)) {
                    $id = ($var instanceof \Closure ? 'closure' : 'object') . (spl_object_id($var) + 1);
                    $this->vars[$id] = $var;
                    return $id;
                }
                // 配列は明確な ID が存在しないので、貯めて検索して ID を振る（参照さえ含まなければ ID に意味はないので参照込みのみ）
                if (is_array($var) && $this->arrayHasReference($var)) {
                    $id = array_search($var, $this->vars, true);
                    if (!$id) {
                        $id = 'array' . (count($this->vars) + 1);
                    }
                    $this->vars[$id] = $var;
                    return $id;
                }
            }

            public function refId($array, $k)
            {
                static $ids = [];
                $ref = \ReflectionReference::fromArrayElement($array, $k);
                if ($ref) {
                    $refid = $ref->getId();
                    $ids[$refid] = ($ids[$refid] ?? count($ids) + 1);
                    $id = 'reference' . $ids[$refid];
                    $this->refs[$id] = $array[$k];
                    return $id;
                }
            }

            public function orphan()
            {
                foreach ($this->refs as $rid => $var) {
                    $vid = array_search($var, $this->vars, true);
                    yield $rid => [!!$vid, $vid, $var];
                }
            }
        };

        // 再帰用クロージャ
        $vars = [];
        $export = function ($value, $nest = 0) use (&$export, &$vars, $var_manager) {
            $spacer0 = str_repeat(" ", 4 * ($nest + 0));
            $spacer1 = str_repeat(" ", 4 * ($nest + 1));
            $raw_export = fn($v) => $v;
            $var_export = fn($v) => var_export($v, true);
            $neighborToken = function ($n, $d, $tokens) {
                for ($i = $n + $d; isset($tokens[$i]); $i += $d) {
                    if ($tokens[$i][0] !== T_WHITESPACE) {
                        return $tokens[$i];
                    }
                }
            };
            $resolveSymbol = function ($token, $prev, $next, $ref) use ($var_export) {
                if ($token[0] === T_STRING) {
                    if ($prev[0] === T_NEW || $next[0] === T_DOUBLE_COLON || $next[0] === T_VARIABLE || $next[1] === '{') {
                        $token[1] = \ryunosuke\SimpleCache\Utils::resolve_symbol($token[1], $ref->getFileName(), 'alias') ?? $token[1];
                    }
                    elseif ($next[1] === '(') {
                        $token[1] = \ryunosuke\SimpleCache\Utils::resolve_symbol($token[1], $ref->getFileName(), 'function') ?? $token[1];
                    }
                    else {
                        $token[1] = \ryunosuke\SimpleCache\Utils::resolve_symbol($token[1], $ref->getFileName(), 'const') ?? $token[1];
                    }
                }

                // マジック定数の解決（__CLASS__, __TRAIT__ も書き換えなければならないが、非常に大変なので下記のみ）
                if ($token[0] === T_FILE) {
                    $token[1] = $var_export($ref->getFileName());
                }
                if ($token[0] === T_DIR) {
                    $token[1] = $var_export(dirname($ref->getFileName()));
                }
                if ($token[0] === T_NS_C) {
                    $token[1] = $var_export($ref->getNamespaceName());
                }
                return $token;
            };

            $vid = $var_manager->varId($value);
            if ($vid) {
                if (isset($vars[$vid])) {
                    return "\$this->$vid";
                }
                $vars[$vid] = $value;
            }

            if (is_array($value)) {
                $hashed = \ryunosuke\SimpleCache\Utils::is_hasharray($value);
                if (!$hashed && \ryunosuke\SimpleCache\Utils::array_all($value, fn(...$args) => \ryunosuke\SimpleCache\Utils::is_primitive(...$args))) {
                    [$begin, $middle, $end] = ["", ", ", ""];
                }
                else {
                    [$begin, $middle, $end] = ["\n{$spacer1}", ",\n{$spacer1}", ",\n{$spacer0}"];
                }

                $keys = array_map($var_export, array_combine($keys = array_keys($value), $keys));
                $maxlen = max(array_map('strlen', $keys ?: ['']));
                $kvl = [];
                foreach ($value as $k => $v) {
                    $refid = $var_manager->refId($value, $k);
                    $keystr = $hashed ? $keys[$k] . str_repeat(" ", $maxlen - strlen($keys[$k])) . " => " : '';
                    $valstr = $refid ? "&\$this->$refid" : $export($v, $nest + 1);
                    $kvl[] = $keystr . $valstr;
                }
                $kvl = implode($middle, $kvl);
                $declare = $vid ? "\$this->$vid = " : "";
                return "{$declare}[$begin{$kvl}$end]";
            }
            if ($value instanceof \Closure) {
                $ref = new \ReflectionFunction($value);
                $bind = $ref->getClosureThis();
                $class = $ref->getClosureScopeClass() ? $ref->getClosureScopeClass()->getName() : null;
                $statics = $ref->getStaticVariables();

                // 内部由来はきちんと fromCallable しないと差異が出てしまう
                if ($ref->isInternal()) {
                    $receiver = $bind ?? $class;
                    $callee = $receiver ? [$receiver, $ref->getName()] : $ref->getName();
                    return "\$this->$vid = \\Closure::fromCallable({$export($callee, $nest)})";
                }

                [$meta, $body] = \ryunosuke\SimpleCache\Utils::callable_code($value);
                $arrow = \ryunosuke\SimpleCache\Utils::starts_with($meta, 'fn') ? ' => ' : ' ';
                $tokens = array_slice(\ryunosuke\SimpleCache\Utils::parse_php("$meta{$arrow}$body;", TOKEN_PARSE), 1, -1);

                $uses = [];
                $context = [
                    'class' => 0,
                    'brace' => 0,
                ];
                foreach ($tokens as $n => $token) {
                    $prev = $neighborToken($n, -1, $tokens) ?? [null, null, null];
                    $next = $neighborToken($n, +1, $tokens) ?? [null, null, null];

                    // クロージャは何でもかける（クロージャ・無名クラス・ジェネレータ etc）のでネスト（ブレース）レベルを記録しておく
                    if ($token[1] === '{') {
                        $context['brace']++;
                    }
                    if ($token[1] === '}') {
                        $context['brace']--;
                    }

                    // 無名クラスは色々厄介なので読み飛ばすために覚えておく
                    if ($prev[0] === T_NEW && $token[0] === T_CLASS) {
                        $context['class'] = $context['brace'];
                    }
                    // そして無名クラスは色々かける上に終了条件が自明ではない（シンタックスエラーでない限りは {} が一致するはず）
                    if ($token[1] === '}' && $context['class'] === $context['brace']) {
                        $context['class'] = 0;
                    }

                    // fromCallable 由来だと名前がついてしまう
                    if (!$context['class'] && $prev[0] === T_FUNCTION && $token[0] === T_STRING) {
                        unset($tokens[$n]);
                        continue;
                    }

                    // use 変数の導出
                    if ($token[0] === T_VARIABLE) {
                        $varname = substr($token[1], 1);
                        // クロージャ内クロージャの use に反応してしまうので存在するときのみとする
                        if (array_key_exists($varname, $statics) && !isset($uses[$varname])) {
                            $recurself = $statics[$varname] === $value ? '&' : '';
                            $uses[$varname] = "$spacer1\$$varname = $recurself{$export($statics[$varname], $nest + 1)};\n";
                        }
                    }

                    $tokens[$n] = $resolveSymbol($token, $prev, $next, $ref);
                }

                $code = \ryunosuke\SimpleCache\Utils::indent_php(implode('', array_column($tokens, 1)), [
                    'indent'   => $spacer1,
                    'baseline' => -1,
                ]);
                if ($bind) {
                    $scope = $var_export($class === 'Closure' ? 'static' : $class);
                    $code = "\Closure::bind($code, {$export($bind, $nest + 1)}, $scope)";
                }
                elseif (!\ryunosuke\SimpleCache\Utils::is_bindable_closure($value)) {
                    $code = "static $code";
                }

                return "\$this->$vid = (function () {\n{$raw_export(implode('', $uses))}{$spacer1}return $code;\n$spacer0})->call(\$this)";
            }
            if (is_object($value)) {
                $ref = new \ReflectionObject($value);

                // ジェネレータはどう頑張っても無理
                if ($value instanceof \Generator) {
                    throw new \DomainException('Generator Class is not support.');
                }

                // 無名クラスは定義がないのでパースが必要
                // さらにコンストラクタを呼ぶわけには行かない（引数を検出するのは不可能）ので潰す必要もある
                if ($ref->isAnonymous()) {
                    $fname = $ref->getFileName();
                    $sline = $ref->getStartLine();
                    $eline = $ref->getEndLine();
                    $tokens = \ryunosuke\SimpleCache\Utils::parse_php(implode('', array_slice(file($fname), $sline - 1, $eline - $sline + 1)));

                    $block = [];
                    $starting = false;
                    $constructing = 0;
                    $nesting = 0;
                    foreach ($tokens as $n => $token) {
                        $prev = $neighborToken($n, -1, $tokens) ?? [null, null, null];
                        $next = $neighborToken($n, +1, $tokens) ?? [null, null, null];

                        // 無名クラスは new class で始まるはず
                        if ($token[0] === T_NEW && $next[0] === T_CLASS) {
                            $starting = true;
                        }
                        if (!$starting) {
                            continue;
                        }

                        // コンストラクタの呼び出し引数はスキップする
                        if ($constructing !== null) {
                            if ($token[1] === '(') {
                                $constructing++;
                            }
                            if ($token[1] === ')') {
                                $constructing--;
                                if ($constructing === 0) {
                                    $constructing = null;          // null を終了済みマークとして変数を再利用している
                                    $block[] = [null, '()', null]; // for psr-12
                                    continue;
                                }
                            }
                            if ($constructing) {
                                continue;
                            }
                        }

                        // コンストラクタは呼ばないのでリネームしておく
                        if ($token[1] === '__construct') {
                            $token[1] = "replaced__construct";
                        }

                        $block[] = $resolveSymbol($token, $prev, $next, $ref);

                        if ($token[1] === '{') {
                            $nesting++;
                        }
                        if ($token[1] === '}') {
                            $nesting--;
                            if ($nesting === 0) {
                                break;
                            }
                        }
                    }

                    $code = \ryunosuke\SimpleCache\Utils::indent_php(implode('', array_column($block, 1)), [
                        'indent'   => $spacer1,
                        'baseline' => -1,
                    ]);
                    $classname = "(function () {\n{$spacer1}return $code;\n{$spacer0}})";
                }
                else {
                    $classname = "\\" . get_class($value) . "::class";
                }

                $privates = [];

                // __serialize があるならそれに従う
                if (method_exists($value, '__serialize')) {
                    $fields = $value->__serialize();
                }
                // __sleep があるならそれをプロパティとする
                elseif (method_exists($value, '__sleep')) {
                    $fields = array_intersect_key(\ryunosuke\SimpleCache\Utils::get_object_properties($value, $privates), array_flip($value->__sleep()));
                }
                // それ以外は適当に漁る
                else {
                    $fields = \ryunosuke\SimpleCache\Utils::get_object_properties($value, $privates);
                }

                return "\$this->new(\$this->$vid, $classname, (function () {\n{$spacer1}return {$export([$fields, $privates], $nest + 1)};\n{$spacer0}}))";
            }

            return is_null($value) || is_resource($value) ? 'null' : $var_export($value);
        };

        $exported = $export($value, 1);
        $others = "";
        $vars = [];
        foreach ($var_manager->orphan() as $rid => [$isref, $vid, $var]) {
            $declare = $isref ? "&\$this->$vid" : $export($var, 1);
            $others .= "    \$this->$rid = $declare;\n";
        }
        $result = "(function () {
{$others}    return $exported;
" . '})->call(new class() {
    public function new(&$object, $class, $provider)
    {
        if ($class instanceof \\Closure) {
            $object = $class();
            $reflection = $this->reflect(get_class($object));
        }
        else {
            $reflection = $this->reflect($class);
            $object = $reflection["self"]->newInstanceWithoutConstructor();
        }
        [$fields, $privates] = $provider();

        if ($reflection["unserialize"]) {
            $object->__unserialize($fields);
            return $object;
        }

        foreach ($reflection["parents"] as $parent) {
            foreach ($this->reflect($parent->name)["properties"] as $name => $property) {
                if (isset($privates[$parent->name][$name])) {
                    $property->setValue($object, $privates[$parent->name][$name]);
                }
                if (isset($fields[$name]) || array_key_exists($name, $fields)) {
                    $property->setValue($object, $fields[$name]);
                    unset($fields[$name]);
                }
            }
        }
        foreach ($fields as $name => $value) {
            $object->$name = $value;
        }

        if ($reflection["wakeup"]) {
            $object->__wakeup();
        }

        return $object;
    }

    private function reflect($class)
    {
        static $cache = [];
        if (!isset($cache[$class])) {
            $refclass = new \ReflectionClass($class);
            $cache[$class] = [
                "self"        => $refclass,
                "parents"     => [],
                "properties"  => [],
                "unserialize" => $refclass->hasMethod("__unserialize"),
                "wakeup"      => $refclass->hasMethod("__wakeup"),
            ];
            for ($current = $refclass; $current; $current = $current->getParentClass()) {
                $cache[$class]["parents"][$current->name] = $current;
            }
            foreach ($refclass->getProperties() as $property) {
                if (!$property->isStatic()) {
                    $property->setAccessible(true);
                    $cache[$class]["properties"][$property->name] = $property;
                }
            }
        }
        return $cache[$class];
    }
})';

        if ($options['format'] === 'minify') {
            $tmp = tempnam(sys_get_temp_dir(), 've3');
            file_put_contents($tmp, "<?php $result;");
            $result = substr(php_strip_whitespace($tmp), 6, -1);
        }

        if ($options['outmode'] === 'eval') {
            $result = "return $result;";
        }
        if ($options['outmode'] === 'file') {
            $result = "<?php return $result;\n";
        }

        if (!$options['return']) {
            echo $result;
        }
        return $result;
    }

    /**
     * 値に複数のハッシュアルゴリズムを適用させて結合して返す
     *
     * $data は何らかの方法で文字列化される（この「何らかの方法」は互換性を担保しない）。
     * 文字長がかなり増えるため、 $base64 に true を与えるとバイナリ変換してその結果を base64（url セーフ）して返す。
     * さらに false を与えると 16進数文字列で返し、 null を与えるとバイナリ文字列で返す。
     *
     * Example:
     * ```php
     * // 配列をハッシュ化する
     * that(var_hash(['a', 'b', 'c']))->isSame('7BDgx6NE2hkXAKtKzhpeJm6-mheMOQWNgrCe7768OiFeoWgA');
     * // オブジェクトをハッシュ化する
     * that(var_hash(new \ArrayObject(['a', 'b', 'c'])))->isSame('-zR2rZ58CzuYhhdHn1Oq90zkYSaxMS-dHUbmb0MTRM4gBpj2');
     * ```
     *
     * @package ryunosuke\Functions\Package\var
     *
     * @param mixed $var ハッシュ化する値
     * @param string[] $algos ハッシュアルゴリズム
     * @param ?bool $base64 結果を base64 化するか
     * @return string ハッシュ文字列
     */
    public static function var_hash($var, $algos = ['md5', 'sha1'], $base64 = true)
    {
        if (!is_string($var)) {
            $var = serialize($var);
        }

        $algos = \ryunosuke\SimpleCache\Utils::arrayize($algos);
        assert($algos);

        $hash = '';
        foreach ($algos as $algo) {
            $hash .= hash($algo, "$var", $base64 !== false);
        }

        if ($base64 !== true) {
            return $hash;
        }

        return rtrim(strtr(base64_encode($hash), ['+' => '-', '/' => '_']));
    }
}
