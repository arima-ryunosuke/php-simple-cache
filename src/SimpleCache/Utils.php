<?php
# Don't touch this code. This is auto generated.
namespace ryunosuke\SimpleCache;

// @formatter:off

/**
 * @codeCoverageIgnore
 */
class Utils
{


    /**
     * 全要素が true になるなら true を返す（1つでも false なら false を返す）
     *
     * $callback が要求するならキーも渡ってくる。
     *
     * Example:
     * ```php
     * that(array_and([true, true]))->isTrue();
     * that(array_and([true, false]))->isFalse();
     * that(array_and([false, false]))->isFalse();
     * ```
     *
     * @package ryunosuke\Functions\Package\array
     *
     * @param iterable $array 対象配列
     * @param ?callable $callback 評価クロージャ。 null なら値そのもので評価
     * @param bool|mixed $default 空配列の場合のデフォルト値
     * @return bool 全要素が true なら true
     */
    public static function array_and($array, $callback = null, $default = true)
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
     * that(array_find_first(['a', '8', '9'], 'ctype_digit'))->isSame(1);
     * that(array_find_first(['a', 'b', 'b'], fn($v) => $v === 'b'))->isSame(1);
     * // 最初に見つかったコールバック結果を返す（最初の数字の2乗を返す）
     * $ifnumeric2power = fn($v) => ctype_digit($v) ? $v * $v : false;
     * that(array_find_first(['a', '8', '9'], $ifnumeric2power, false))->isSame(64);
     * ```
     *
     * @package ryunosuke\Functions\Package\array
     *
     * @param iterable $array 調べる配列
     * @param callable $callback 評価コールバック
     * @param bool $is_key キーを返すか否か
     * @return mixed コールバックが true を返した最初のキー。存在しなかったら null
     */
    public static function array_find_first($array, $callback, $is_key = true)
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
        return null;
    }

    /**
     * 引数の配列を生成する。
     *
     * 配列以外を渡すと配列化されて追加される。
     * 配列を渡してもそのままだが、連番配列の場合はマージ、連想配列の場合は結合となる。
     * iterable や Traversable は考慮せずあくまで「配列」としてチェックする。
     *
     * Example:
     * ```php
     * // 値は配列化される
     * that(arrayize(1, 2, 3))->isSame([1, 2, 3]);
     * // 配列はそのまま
     * that(arrayize([1], [2], [3]))->isSame([1, 2, 3]);
     * // 連想配列、連番配列の挙動
     * that(arrayize([1, 2, 3], [4, 5, 6], ['a' => 'A1'], ['a' => 'A2']))->isSame([1, 2, 3, 4, 5, 6, 'a' => 'A1']);
     * // stdClass は foreach 可能だがあくまで配列としてチェックする
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
            elseif ($result && !\ryunosuke\SimpleCache\Utils::is_hasharray($arg)) {
                $result = array_merge($result, $arg);
            }
            else {
                // array_merge に合わせるなら $result = $arg + $result で後方上書きの方がいいかも
                // 些細な変更だけど後方互換性が完全に壊れるのでいったん保留（可変引数なんてほとんど使ってないと思うけど…）
                $result += $arg; // for compatible
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
        if (function_exists('array_is_list')) {
            return !array_is_list($array); // @codeCoverageIgnore
        }

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
     * クラス定数が存在するか調べる
     *
     * グローバル定数も調べられる。ので実質的には defined とほぼ同じで違いは下記。
     *
     * - defined は単一引数しか与えられないが、この関数は2つの引数も受け入れる
     * - defined は private const で即死するが、この関数はきちんと調べることができる
     * - ClassName::class は常に true を返す
     *
     * あくまで存在を調べるだけで実際にアクセスできるかは分からないので注意（`property_exists` と同じ）。
     *
     * Example:
     * ```php
     * // クラス定数が調べられる（1引数、2引数どちらでも良い）
     * that(const_exists('ArrayObject::STD_PROP_LIST'))->isTrue();
     * that(const_exists('ArrayObject', 'STD_PROP_LIST'))->isTrue();
     * that(const_exists('ArrayObject::UNDEFINED'))->isFalse();
     * that(const_exists('ArrayObject', 'UNDEFINED'))->isFalse();
     * // グローバル（名前空間）もいける
     * that(const_exists('PHP_VERSION'))->isTrue();
     * that(const_exists('UNDEFINED'))->isFalse();
     * ```
     *
     * @package ryunosuke\Functions\Package\classobj
     *
     * @param string|object $classname 調べるクラス
     * @param string $constname 調べるクラス定数
     * @return bool 定数が存在するなら true
     */
    public static function const_exists($classname, $constname = '')
    {
        $colonp = strpos($classname, '::');
        if ($colonp === false && strlen($constname) === 0) {
            return defined($classname);
        }
        if (strlen($constname) === 0) {
            $constname = substr($classname, $colonp + 2);
            $classname = substr($classname, 0, $colonp);
        }

        try {
            $refclass = new \ReflectionClass($classname);
            if (strcasecmp($constname, 'class') === 0) {
                return true;
            }
            return $refclass->hasConstant($constname);
        }
        catch (\Throwable) {
            return false;
        }
    }

    /**
     * オブジェクトのプロパティを可視・不可視を問わず取得する
     *
     * get_object_vars + no public プロパティを返すイメージ。
     * クロージャだけは特別扱いで this + use 変数を返す。
     *
     * Example:
     * ```php
     * $object = new #[\AllowDynamicProperties] class('something', 42) extends \Exception{};
     * $object->oreore = 'oreore';
     *
     * // get_object_vars はそのスコープから見えないプロパティを取得できない
     * // var_dump(get_object_vars($object));
     *
     * // array キャストは全て得られるが null 文字を含むので扱いにくい
     * // var_dump((array) $object);
     *
     * // この関数を使えば不可視プロパティも取得できる
     * that(object_properties($object))->subsetEquals([
     *     'message' => 'something',
     *     'code'    => 42,
     *     'oreore'  => 'oreore',
     * ]);
     *
     * // クロージャは this と use 変数を返す
     * that(object_properties(fn() => $object))->is([
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
    public static function object_properties($object, &$privates = [])
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
        return function (...$args) use ($callback, $plength) {
            if (is_infinite($plength)) {
                return $callback(...$args);
            }
            return $callback(...array_slice($args, 0, $plength));
        };
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
     * that(namespace_parse(sys_get_temp_dir() . '/namespace.php'))->isSame([
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
    public static function namespace_parse($filename, $options = [])
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
                return trim(implode('', array_column(array_filter($tokens, function ($token) {
                    return in_array($token->id, [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE, T_STRING], true);
                }), 'text')), '\\');
            };

            $keys = [
                null        => 'alias', // for use
                T_CLASS     => 'alias',
                T_INTERFACE => 'alias',
                T_TRAIT     => 'alias',
                T_STRING    => 'const', // for define
                T_CONST     => 'const',
                T_FUNCTION  => 'function',
            ];

            $contents = file_get_contents($filename);
            $namespace = '';
            $tokens = [-1 => null];
            $result = [];
            while (true) {
                $tokens = \ryunosuke\SimpleCache\Utils::php_parse($contents, [
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
                if ($token->id === T_STRING && $token->text === "define") {
                    $tokens = \ryunosuke\SimpleCache\Utils::php_parse($contents, [
                        'flags'  => TOKEN_PARSE,
                        'begin'  => [T_CONSTANT_ENCAPSED_STRING],
                        'end'    => [T_CONSTANT_ENCAPSED_STRING],
                        'offset' => \ryunosuke\SimpleCache\Utils::last_key($tokens),
                    ]);
                    $cname = substr(implode('', array_column($tokens, 'text')), 1, -1);
                    $define = trim(json_decode("\"$cname\""), '\\');
                    [$ns, $nm] = \ryunosuke\SimpleCache\Utils::namespace_split($define);
                    if (!isset($result[$ns])) {
                        $result[$ns] = [
                            'const'    => [],
                            'function' => [],
                            'alias'    => [],
                        ];
                    }
                    $result[$ns][$keys[$token->id]][$nm] = $define;
                }
                switch ($token->id) {
                    case T_NAMESPACE:
                        $namespace = $stringify($tokens);
                        $result[$namespace] = [
                            'const'    => [],
                            'function' => [],
                            'alias'    => [],
                        ];
                        break;
                    case T_USE:
                        $tokenCorF = \ryunosuke\SimpleCache\Utils::array_find_first($tokens, fn($token) => ($token->id === T_CONST || $token->id === T_FUNCTION) ? $token->id : 0, false);

                        $prefix = '';
                        if (end($tokens)->text === '{') {
                            $prefix = $stringify($tokens);
                            $tokens = \ryunosuke\SimpleCache\Utils::php_parse($contents, [
                                'flags'  => TOKEN_PARSE,
                                'begin'  => ['{'],
                                'end'    => ['}'],
                                'offset' => \ryunosuke\SimpleCache\Utils::last_key($tokens),
                            ]);
                        }

                        $multi = \ryunosuke\SimpleCache\Utils::array_explode($tokens, fn($token) => $token->text === ',');
                        foreach ($multi as $ttt) {
                            $as = \ryunosuke\SimpleCache\Utils::array_explode($ttt, fn($token) => $token->id === T_AS);

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
                            $result[$namespace][$keys[$token->id]][$alias] = \ryunosuke\SimpleCache\Utils::concat($namespace, '\\') . $alias;
                        }
                        // ブロック内に興味はないので進めておく（function 内 function などはあり得るが考慮しない）
                        if ($token->id !== T_CONST) {
                            $tokens = \ryunosuke\SimpleCache\Utils::php_parse($contents, [
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
     * that(namespace_resolve('AO', sys_get_temp_dir() . '/symbol.php'))->isSame('ArrayObject');
     * that(namespace_resolve('SL', sys_get_temp_dir() . '/symbol.php'))->isSame('strlen');
     * that(namespace_resolve('InnerFunc', sys_get_temp_dir() . '/symbol.php'))->isSame('vendor\\NS\\InnerFunc');
     * that(namespace_resolve('InnerClass', sys_get_temp_dir() . '/symbol.php'))->isSame('vendor\\NS\\InnerClass');
     * ```
     *
     * @package ryunosuke\Functions\Package\misc
     *
     * @param string $shortname エイリアス名
     * @param string|array $nsfiles ファイル名 or [ファイル名 => 名前空間名]
     * @param array $targets エイリアスタイプ（'const', 'function', 'alias' のいずれか）
     * @return string|null 完全修飾名。解決できなかった場合は null
     */
    public static function namespace_resolve(string $shortname, $nsfiles, $targets = ['const', 'function', 'alias'])
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
            foreach (\ryunosuke\SimpleCache\Utils::namespace_parse($filename) as $namespace => $ns) {
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
     * that(php_indent($phpcode, 8))->isSame('
     *         echo 123;
     *
     *         if (true) {
     *             echo 456;
     *         }
     * ');
     * // 文字列を指定すればそれが使用される
     * that(php_indent($phpcode, "  "))->isSame('
     *   echo 123;
     *
     *   if (true) {
     *       echo 456;
     *   }
     * ');
     * // オプション指定
     * that(php_indent($phpcode, [
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
    public static function php_indent($phpcode, $options = [])
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

        $tmp = \PhpToken::tokenize("<?php $phpcode");
        array_shift($tmp);

        // トークンの正規化
        $tokens = [];
        for ($i = 0; $i < count($tmp); $i++) {
            if ($options['heredoc']) {
                // 行コメントと同じ（T_START_HEREDOC には改行が含まれている）
                if ($tmp[$i]->id === T_START_HEREDOC && preg_match('@^(<<<).*?(\\R)@um', $tmp[$i]->text, $matches)) {
                    $tmp[$i]->text = trim($tmp[$i]->text);
                    if (($tmp[$i + 1]->id ?? null) === T_ENCAPSED_AND_WHITESPACE) {
                        $tmp[$i + 1]->text = $matches[2] . $tmp[$i + 1]->text;
                    }
                    else {
                        array_splice($tmp, $i + 1, 0, [new \PhpToken(T_ENCAPSED_AND_WHITESPACE, $matches[2])]);
                    }
                }
                // php 7.3 において T_END_HEREDOC は必ず単一行になる
                if ($tmp[$i]->id === T_ENCAPSED_AND_WHITESPACE) {
                    if (($tmp[$i + 1]->id ?? null) === T_END_HEREDOC && preg_match('@^(\\s+)(.*)@um', $tmp[$i + 1]->text, $matches)) {
                        $tmp[$i]->text = $tmp[$i]->text . $matches[1];
                        $tmp[$i + 1]->text = $matches[2];
                    }
                }
            }

            $tokens[] = $tmp[$i];
        }

        // 改行を置換してインデント
        $hereing = false;
        foreach ($tokens as $i => $token) {
            if ($options['heredoc']) {
                if ($token->id === T_START_HEREDOC) {
                    $hereing = true;
                }
                if ($token->id === T_END_HEREDOC) {
                    $hereing = false;
                }
            }
            if (in_array($token->id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true) || ($hereing && $token->id === T_ENCAPSED_AND_WHITESPACE)) {
                $token->text = preg_replace("@(\\R)$indent@um", '$1' . $options['indent'], $token->text);
            }
            if ($options['trimempty']) {
                if ($token->id === T_WHITESPACE) {
                    $token->text = preg_replace("@(\\R)[ \\t]+(\\R)@um", '$1$2', $token->text);
                }
            }

            $tokens[$i] = $token;
        }
        return implode('', array_column($tokens, 'text'));
    }

    /**
     * php のコード断片をパースする
     *
     * @todo そもそも何がしたいのかよくわからない関数になってきたので動作の洗い出しが必要
     *
     * Example:
     * ```php
     * $phpcode = '<?php
     * namespace Hogera;
     * class Example
     * {
     *     // something
     * }';
     *
     * // namespace ～ ; を取得
     * $part = php_parse($phpcode, [
     *     'begin' => T_NAMESPACE,
     *     'end'   => ';',
     * ]);
     * that(implode('', array_column($part, 'text')))->isSame('namespace Hogera;');
     *
     * // class ～ { を取得
     * $part = php_parse($phpcode, [
     *     'begin' => T_CLASS,
     *     'end'   => '{',
     * ]);
     * that(implode('', array_column($part, 'text')))->isSame("class Example\n{");
     * ```
     *
     * @package ryunosuke\Functions\Package\misc
     *
     * @param string $phpcode パースする php コード
     * @param array|int $option パースオプション
     * @return \PhpToken[] トークン配列
     */
    public static function php_parse($phpcode, $option = [])
    {
        if (is_int($option)) {
            $option = ['flags' => $option];
        }

        $default = [
            'short_open_tag' => null, // ショートオープンタグを扱うか（null だと余計なことはせず ini に従う）
            'line'           => [],   // 行の範囲（以上以下）
            'position'       => [],   // 文字位置の範囲（以上以下）
            'begin'          => [],   // 開始トークン
            'end'            => [],   // 終了トークン
            'offset'         => 0,    // 開始トークン位置
            'flags'          => 0,    // PHPToken の $flags. TOKEN_PARSE を与えると ParseError が出ることがあるのでデフォルト 0
            'cache'          => true, // キャッシュするか否か
            'greedy'         => false,// end と nest か一致したときに処理を継続するか
            'backtick'       => true, // `` もパースするか
            'nest_token'     => [
                ')' => '(',
                '}' => '{',
                ']' => '[',
            ],
        ];
        $option += $default;

        $cachekey = \ryunosuke\SimpleCache\Utils::var_hash($phpcode) . $option['flags'] . '-' . $option['backtick'] . '-' . var_export($option['short_open_tag'], true);
        static $cache = [];
        if (!($option['cache'] && isset($cache[$cachekey]))) {
            $position = 0;
            $backtick = '';
            $backticktoken = null;
            $backticking = false;

            $tokens = [];
            $tmp = \PhpToken::tokenize($phpcode, $option['flags']);
            for ($i = 0; $i < count($tmp); $i++) {
                $token = $tmp[$i];

                // @codeCoverageIgnoreStart
                if ($option['short_open_tag'] === true && $token->id === T_INLINE_HTML && ($p = strpos($token->text, '<?')) !== false) {
                    $newtokens = [];
                    $nlcount = 0;

                    if ($p !== 0) {
                        $html = substr($token->text, 0, $p);
                        $nlcount = preg_match_all('#\r\n|\r|\n#u', $html);
                        $newtokens[] = new \PhpToken(T_INLINE_HTML, $html, $token->line);
                    }

                    $code = substr($token->text, $p + 2);
                    $subtokens = \PhpToken::tokenize("<?php $code");
                    $subtokens[0]->text = '<?';
                    foreach ($subtokens as $subtoken) {
                        $subtoken->line += $token->line + $nlcount - 1;
                        $newtokens[] = $subtoken;
                    }

                    array_splice($tmp, $i + 1, 0, $newtokens);
                    continue;
                }
                if ($option['short_open_tag'] === false && $token->id === T_OPEN_TAG && $token->text === '<?') {
                    for ($j = $i + 1; $j < count($tmp); $j++) {
                        if ($tmp[$j]->id === T_CLOSE_TAG) {
                            break;
                        }
                    }
                    $html = implode('', array_map(fn($token) => $token->text, array_slice($tmp, $i, $j - $i + 1)));
                    array_splice($tmp, $i + 1, $j - $i, [new \PhpToken(T_INLINE_HTML, $html, $token->line)]);
                    continue;
                }
                // @codeCoverageIgnoreEnd

                if (!$option['backtick']) {
                    if ($token->text === '`') {
                        if ($backticking) {
                            $token->text = $backtick . $token->text;
                            $token->line = $backticktoken->line;
                            $token->pos = $backticktoken->pos;
                            $backtick = '';
                        }
                        else {
                            $backticktoken = $token;
                        }
                        $backticking = !$backticking;
                    }
                    if ($backticking) {
                        $backtick .= $token->text;
                        continue;
                    }
                }

                $token->pos = $position;
                $position += strlen($token->text);

                /* PhpToken になりコピーオンライトが効かなくなったので時々書き換えをチェックした方が良い
                $token = new class($token->id, $token->text, $token->line, $token->pos) extends \PhpToken {
                    private array $backup = [];
    
                    public function backup()
                    {
                        $this->backup = [
                            'id'   => $this->id,
                            'text' => $this->text,
                            'line' => $this->line,
                            'pos'  => $this->pos,
                        ];
                    }
    
                    public function __clone(): void
                    {
                        $this->backup = [];
                    }
    
                    public function __destruct()
                    {
                        foreach ($this->backup as $name => $value) {
                            assert($this->$name === $value);
                        }
                    }
                };
                $token->backup();
                 */

                $tokens[] = $token;
            }
            // @codeCoverageIgnoreStart
            if ($option['short_open_tag'] === false) {
                for ($i = 0; $i < count($tokens); $i++) {
                    if ($tokens[$i]->id === T_INLINE_HTML && isset($tokens[$i + 1]) && $tokens[$i + 1]->id === T_INLINE_HTML) {
                        $tokens[$i]->text .= $tokens[$i + 1]->text;
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

            if ($lines[0] > $token->line) {
                continue;
            }
            if ($lines[1] < $token->line) {
                continue;
            }
            if ($positions[0] > $token->pos) {
                continue;
            }
            if ($positions[1] < $token->pos) {
                continue;
            }

            foreach ($begin_tokens as $t) {
                if ($t === $token->id || $t === $token->text) {
                    $starting = true;
                    break;
                }
            }
            if (!$starting) {
                continue;
            }

            $result[$i] = $token;

            foreach ($nest_tokens as $end_nest => $start_nest) {
                if ($token->id === $start_nest || $token->text === $start_nest) {
                    $nesting++;
                }
                if ($token->id === $end_nest || $token->text === $end_nest) {
                    $nesting--;
                }
            }

            foreach ($end_tokens as $t) {
                if ($t === $token->id || $t === $token->text) {
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
     * @param bool $return_token true にすると生のトークン配列で返す
     * @return array ['定義部分', '{処理コード}']
     */
    public static function callable_code($callable, bool $return_token = false)
    {
        $ref = $callable instanceof \ReflectionFunctionAbstract ? $callable : \ryunosuke\SimpleCache\Utils::reflect_callable($callable);
        $contents = file($ref->getFileName());
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();
        $codeblock = implode('', array_slice($contents, $start - 1, $end - $start + 1));

        $meta = \ryunosuke\SimpleCache\Utils::php_parse("<?php $codeblock", [
            'begin' => [T_FN, T_FUNCTION],
            'end'   => ['{', T_DOUBLE_ARROW],
        ]);
        $end = array_pop($meta);

        if ($end->id === T_DOUBLE_ARROW) {
            $body = \ryunosuke\SimpleCache\Utils::php_parse("<?php $codeblock", [
                'begin'  => T_DOUBLE_ARROW,
                'end'    => [';', ',', ')'],
                'offset' => \ryunosuke\SimpleCache\Utils::last_key($meta),
                'greedy' => true,
            ]);
            $body = array_slice($body, 1, -1);
        }
        else {
            $body = \ryunosuke\SimpleCache\Utils::php_parse("<?php $codeblock", [
                'begin'  => '{',
                'end'    => '}',
                'offset' => \ryunosuke\SimpleCache\Utils::last_key($meta),
            ]);
        }

        if ($return_token) {
            return [$meta, $body];
        }

        return [trim(implode('', array_column($meta, 'text'))), trim(implode('', array_column($body, 'text')))];
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
     * 実際には ReflectionFunctionAbstract を下記の独自拡張した Reflection クラスを返す（メソッドのオーバーライド等はしていないので完全互換）。
     * - __invoke: 元となったオブジェクトを $this として invoke する（関数・クロージャは invoke と同義）
     * - call: 実行 $this を指定して invoke する（クロージャ・メソッドのみ）
     *   - 上記二つは __call/__callStatic のメソッドも呼び出せる
     * - getDeclaration: 宣言部のコードを返す
     * - getCode: 定義部のコードを返す
     * - isAnonymous: 無名関数なら true を返す（8.2 の isAnonymous 互換）
     * - isStatic: $this バインド可能かを返す（クロージャのみ）
     * - getUsedVariables: use している変数配列を返す（クロージャのみ）
     * - getClosure: 元となったオブジェクトを $object としたクロージャを返す（メソッドのみ）
     *   - 上記二つは __call/__callStatic のメソッドも呼び出せる
     * - getTraitMethod: トレイト側のリフレクションを返す（メソッドのみ）
     *
     * Example:
     * ```php
     * that(reflect_callable('sprintf'))->isInstanceOf(\ReflectionFunction::class);
     * that(reflect_callable('\Closure::bind'))->isInstanceOf(\ReflectionMethod::class);
     *
     * $x = 1;
     * $closure = function ($a, $b) use (&$x) { return $a + $b; };
     * $reflection = reflect_callable($closure);
     * // 単純実行
     * that($reflection(1, 2))->is(3);
     * // 無名クラスを $this として実行
     * that($reflection->call(new class(){}, 1, 2))->is(3);
     * // 宣言部を返す
     * that($reflection->getDeclaration())->is('function ($a, $b) use (&$x)');
     * // 定義部を返す
     * that($reflection->getCode())->is('{ return $a + $b; }');
     * // static か返す
     * that($reflection->isStatic())->is(false);
     * // use 変数を返す
     * that($reflection->getUsedVariables())->is(['x' => 1]);
     * ```
     *
     * @package ryunosuke\Functions\Package\reflection
     *
     * @param callable $callable 対象 callable
     * @return \ReflectCallable|\ReflectionFunction|\ReflectionMethod リフレクションインスタンス
     */
    public static function reflect_callable($callable)
    {
        // callable チェック兼 $call_name 取得
        if (!is_callable($callable, true, $call_name)) {
            throw new \InvalidArgumentException("'$call_name' is not callable");
        }

        if (is_string($call_name) && strpos($call_name, '::') === false) {
            return new class($callable) extends \ReflectionFunction {
                private $definition;

                public function __invoke(...$args): mixed
                {
                    return $this->invoke(...$args);
                }

                public function getDeclaration(): string
                {
                    return ($this->definition ??= \ryunosuke\SimpleCache\Utils::callable_code($this))[0];
                }

                public function getCode(): string
                {
                    return ($this->definition ??= \ryunosuke\SimpleCache\Utils::callable_code($this))[1];
                }

                public function isAnonymous(): bool
                {
                    return false;
                }
            };
        }
        elseif ($callable instanceof \Closure) {
            return new class($callable) extends \ReflectionFunction {
                private $callable;
                private $definition;

                public function __construct($function)
                {
                    parent::__construct($function);

                    $this->callable = $function;
                }

                public function __invoke(...$args): mixed
                {
                    return $this->invoke(...$args);
                }

                public function call($newThis = null, ...$args): mixed
                {
                    return ($this->callable)->call($newThis ?? $this->getClosureThis(), ...$args);
                }

                public function getDeclaration(): string
                {
                    return ($this->definition ??= \ryunosuke\SimpleCache\Utils::callable_code($this))[0];
                }

                public function getCode(): string
                {
                    return ($this->definition ??= \ryunosuke\SimpleCache\Utils::callable_code($this))[1];
                }

                public function isAnonymous(): bool
                {
                    if (method_exists(\ReflectionFunction::class, 'isAnonymous')) {
                        return parent::isAnonymous(); // @codeCoverageIgnore
                    }

                    return strpos($this->name, '{closure}') !== false;
                }

                public function isStatic(): bool
                {
                    return !\ryunosuke\SimpleCache\Utils::is_bindable_closure($this->callable);
                }

                public function getUsedVariables(): array
                {
                    if (method_exists(\ReflectionFunction::class, 'getClosureUsedVariables')) {
                        return parent::getClosureUsedVariables(); // @codeCoverageIgnore
                    }

                    $uses = \ryunosuke\SimpleCache\Utils::object_properties($this->callable);
                    unset($uses['this']);
                    return $uses;
                }
            };
        }
        else {
            [$class, $method] = explode('::', $call_name, 2);
            // for タイプ 5: 相対指定による静的クラスメソッドのコール (PHP 5.3.0 以降)
            if (strpos($method, 'parent::') === 0) {
                [, $method] = explode('::', $method);
                $class = get_parent_class($class);
            }

            $called_name = '';
            if (!method_exists(is_array($callable) && is_object($callable[0]) ? $callable[0] : $class, $method)) {
                $called_name = $method;
                $method = is_array($callable) && is_object($callable[0]) ? '__call' : '__callStatic';
            }

            return new class($class, $method, $callable, $called_name) extends \ReflectionMethod {
                private $callable;
                private $call_name;
                private $definition;

                public function __construct($class, $method, $callable, $call_name)
                {
                    parent::__construct($class, $method);

                    $this->setAccessible(true); // 8.1 はデフォルトで true になるので模倣する
                    $this->callable = $callable;
                    $this->call_name = $call_name;
                }

                public function __invoke(...$args): mixed
                {
                    if ($this->call_name) {
                        $args = [$this->call_name, $args];
                    }
                    return $this->invoke($this->isStatic() ? null : $this->callable[0], ...$args);
                }

                public function call($newThis = null, ...$args): mixed
                {
                    if ($this->call_name) {
                        $args = [$this->call_name, $args];
                    }
                    return $this->getClosure($newThis ?? ($this->isStatic() ? null : $this->callable[0]))(...$args);
                }

                public function getDeclaration(): string
                {
                    return ($this->definition ??= \ryunosuke\SimpleCache\Utils::callable_code($this))[0];
                }

                public function getCode(): string
                {
                    return ($this->definition ??= \ryunosuke\SimpleCache\Utils::callable_code($this))[1];
                }

                public function isAnonymous(): bool
                {
                    return false;
                }

                public function getClosure(?object $object = null): \Closure
                {
                    $name = strtolower($this->name);

                    if ($this->isStatic()) {
                        if ($name === '__callstatic') {
                            return \Closure::fromCallable([$this->class, $this->call_name]);
                        }
                        return parent::getClosure();
                    }

                    $object ??= $this->callable[0];
                    if ($name === '__call') {
                        return \Closure::fromCallable([$object, $this->call_name]);
                    }
                    return parent::getClosure($object);
                }

                public function getTraitMethod(): ?\ReflectionMethod
                {
                    $name = strtolower($this->name);
                    $class = $this->getDeclaringClass();
                    $aliases = array_change_key_case($class->getTraitAliases(), CASE_LOWER);

                    if (!isset($aliases[$name])) {
                        if ($this->getFileName() === $class->getFileName()) {
                            return null;
                        }
                        else {
                            return $this;
                        }
                    }

                    [$tname, $mname] = explode('::', $aliases[$name]);
                    $result = new self($tname, $mname, $this->callable, $this->call_name);

                    // alias を張ったとしても自身で再宣言はエラーなく可能で、その場合自身が採用されるようだ
                    if (false
                        || $this->getFileName() !== $result->getFileName()
                        || $this->getStartLine() !== $result->getStartLine()
                        || $this->getEndLine() !== $result->getEndLine()
                    ) {
                        return null;
                    }

                    return $result;
                }
            };
        }
    }

    /**
     * strcat の空文字回避版
     *
     * 基本は strcat と同じ。ただし、**引数の内1つでも空文字を含むなら空文字を返す**。
     * さらに*引数の内1つでも null を含むなら null を返す**。
     *
     * 「プレフィックスやサフィックスを付けたいんだけど、空文字の場合はそのままで居て欲しい」という状況はまれによくあるはず。
     * コードで言えば `strlen($string) ? 'prefix-' . $string : '';` のようなもの。
     * 可変引数なので 端的に言えば mysql の CONCAT みたいな動作になる。
     *
     * ```php
     * that(concat('prefix-', 'middle', '-suffix'))->isSame('prefix-middle-suffix');
     * that(concat('prefix-', '', '-suffix'))->isSame('');
     * that(concat('prefix-', null, '-suffix'))->isSame(null);
     * ```
     *
     * @package ryunosuke\Functions\Package\strings
     *
     * @param ?string ...$variadic 結合する文字列（可変引数）
     * @return ?string 結合した文字列
     */
    public static function concat(...$variadic)
    {
        if (count(array_filter($variadic, 'is_null')) > 0) {
            return null;
        }
        $result = '';
        foreach ($variadic as $s) {
            if (strlen($s) === 0) {
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
    public static function namespace_split(?string $string)
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
    public static function starts_with(?string $string, $with, $case_insensitivity = false)
    {
        foreach ((array) $with as $w) {
            $w = (string) $w;

            // All strings end with the empty string
            if ($w === '') {
                return true;
            }

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
     * url safe な base64_encode
     *
     * れっきとした RFC があるのかは分からないが '+' => '-', '/' => '_' がデファクトだと思うのでそのようにしてある。
     * パディングの = も外す。
     *
     * @package ryunosuke\Functions\Package\url
     *
     * @param string $string 変換元文字列
     * @return string base64url 文字列
     */
    public static function base64url_encode($string)
    {
        return rtrim(strtr(base64_encode($string), ['+' => '-', '/' => '_']), '=');
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
                    $cachpath = $this->cachedir . '/' . rawurlencode($namespace) . self::CACHE_EXT;
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
     * @param array|?string $option 設定。文字列指定時はその値を返す
     * @return array|string 設定値
     */
    public static function function_configure($option)
    {
        static $config = [];

        // default
        $config['cachedir'] ??= sys_get_temp_dir() . DIRECTORY_SEPARATOR . strtr(__NAMESPACE__, ['\\' => '%']);
        $config['storagedir'] ??= DIRECTORY_SEPARATOR === '/' ? '/var/tmp/rf' : (getenv('ALLUSERSPROFILE') ?: sys_get_temp_dir()) . '\\rf';
        $config['placeholder'] ??= '';
        $config['var_stream'] ??= 'VarStreamV010000';
        $config['memory_stream'] ??= 'MemoryStreamV010000';
        $config['array.variant'] ??= false;
        $config['chain.version'] ??= 2;
        $config['chain.nullsafe'] ??= false;
        $config['process.autoload'] ??= [];

        // setting
        if (is_array($option)) {
            foreach ($option as $name => $entry) {
                $option[$name] = $config[$name] ?? null;
                switch ($name) {
                    default:
                        $config[$name] = $entry;
                        break;
                    case 'cachedir':
                    case 'storagedir':
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
                            if (!\ryunosuke\SimpleCache\Utils::is_resourcable(constant($entry))) {
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
        if ($option === null) {
            return $config;
        }
        if (is_string($option)) {
            switch ($option) {
                default:
                    return $config[$option] ?? null;
                case 'cachedir':
                case 'storagedir':
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
     * $stdclass = (object) ['key' => 'val'];
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
        return is_scalar($var) || is_null($var) || \ryunosuke\SimpleCache\Utils::is_resourcable($var);
    }

    /**
     * 閉じたリソースでも true を返す is_resource
     *
     * マニュアル（ https://www.php.net/manual/ja/function.is-resource.php ）に記載の通り、 isresource は閉じたリソースで false を返す。
     * リソースはリソースであり、それでは不便なこともあるので、閉じていようとリソースなら true を返す関数。
     *
     * Example:
     * ```php
     * // 閉じたリソースを用意
     * $resource = tmpfile();
     * fclose($resource);
     * // is_resource は false を返すが・・・
     * that(is_resource($resource))->isFalse();
     * // is_resourcable は true を返す
     * that(is_resourcable($resource))->isTrue();
     * ```
     *
     * @package ryunosuke\Functions\Package\var
     *
     * @param mixed $var 調べる値
     * @return bool リソースなら true
     */
    public static function is_resourcable($var)
    {
        if (is_resource($var)) {
            return true;
        }
        // もっといい方法があるかもしれないが、簡単に調査したところ gettype するしか術がないような気がする
        if (strpos(gettype($var), 'resource') === 0) {
            return true;
        }
        return false;
    }

    /**
     * var_export を色々と出力できるようにしたもの
     *
     * php のコードに落とし込むことで serialize と比較してかなり高速に動作する。
     *
     * 各種オブジェクトやクロージャ、循環参照を含む配列など様々なものが出力できる。
     * ただし、下記は不可能あるいは復元不可（今度も対応するかは未定）。
     *
     * - 特定の内部クラス（PDO など）
     * - 大部分のリソース
     *
     * オブジェクトは「リフレクションを用いてコンストラクタなしで生成してプロパティを代入する」という手法で復元する。
     * ただしコンストラクタが必須引数無しの場合はコールされる。
     * のでクラスによってはおかしな状態で復元されることがある（大体はリソース型のせいだが…）。
     * sleep, wakeup, Serializable などが実装されているとそれはそのまま機能する。
     * set_state だけは呼ばれないので注意。
     *
     * Generator は元となった関数/メソッドを再コールすることで復元される。
     * その仕様上、引数があると呼べないし、実行位置はリセットされる。
     *
     * クロージャはコード自体を引っ張ってきて普通に function (){} として埋め込む。
     * クラス名のエイリアスや use, $this バインドなど可能な限り復元するが、おそらくあまりに複雑なことをしてると失敗する。
     *
     * リソースはファイル的なリソースであればメタ情報を出力して復元時に再オープンする。
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
                // オブジェクトは明確な ID が取れる（generator/closure/object の区分けに処理的な意味はない）
                if (is_object($var)) {
                    $id = ($var instanceof \Generator ? 'generator' : ($var instanceof \Closure ? 'closure' : 'object')) . (spl_object_id($var) + 1);
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
                // リソースも一応は ID がある
                if (\ryunosuke\SimpleCache\Utils::is_resourcable($var)) {
                    $id = 'resource' . (int) $var;
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
        $export = function ($value, $nest = 0, $raw = false) use (&$export, &$vars, $var_manager) {
            $spacer0 = str_repeat(" ", 4 * max(0, $nest + 0));
            $spacer1 = str_repeat(" ", 4 * max(0, $nest + 1));
            $raw_export = fn($v) => $v;
            $var_export = fn($v) => var_export($v, true);
            $neighborToken = function ($n, $d, $tokens) {
                for ($i = $n + $d; isset($tokens[$i]); $i += $d) {
                    if ($tokens[$i]->id !== T_WHITESPACE) {
                        return $tokens[$i];
                    }
                }
            };
            $resolveSymbol = function ($token, $prev, $next, $ref) use ($var_export) {
                $text = $token->text;
                if ($token->id === T_STRING) {
                    $namespaces = [$ref->getNamespaceName()];
                    if ($ref instanceof \ReflectionFunctionAbstract) {
                        $namespaces[] = $ref->getClosureScopeClass()?->getNamespaceName();
                    }
                    if ($prev->id === T_NEW || $next->id === T_DOUBLE_COLON || $next->id === T_VARIABLE || $next->text === '{') {
                        $text = \ryunosuke\SimpleCache\Utils::namespace_resolve($text, $ref->getFileName(), 'alias') ?? $text;
                    }
                    elseif ($next->text === '(') {
                        $text = \ryunosuke\SimpleCache\Utils::namespace_resolve($text, $ref->getFileName(), 'function') ?? $text;
                        // 関数・定数は use しなくてもグローバルにフォールバックされる（=グローバルと名前空間の区別がつかない）
                        foreach ($namespaces as $namespace) {
                            if (!function_exists($text) && function_exists($nstext = "\\$namespace\\$text")) {
                                $text = $nstext;
                                break;
                            }
                        }
                    }
                    else {
                        $text = \ryunosuke\SimpleCache\Utils::namespace_resolve($text, $ref->getFileName(), 'const') ?? $text;
                        // 関数・定数は use しなくてもグローバルにフォールバックされる（=グローバルと名前空間の区別がつかない）
                        foreach ($namespaces as $namespace) {
                            if (!\ryunosuke\SimpleCache\Utils::const_exists($text) && \ryunosuke\SimpleCache\Utils::const_exists($nstext = "\\$namespace\\$text")) {
                                $text = $nstext;
                                break;
                            }
                        }
                    }
                }

                // マジック定数の解決（__CLASS__, __TRAIT__ も書き換えなければならないが、非常に大変なので下記のみ）
                if ($token->id === T_FILE) {
                    $text = $var_export($ref->getFileName());
                }
                if ($token->id === T_DIR) {
                    $text = $var_export(dirname($ref->getFileName()));
                }
                if ($token->id === T_NS_C) {
                    $text = $var_export($ref->getNamespaceName());
                }
                if ($text !== null) {
                    $token = clone $token;
                    $token->text = $text;
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
                if (!$hashed && \ryunosuke\SimpleCache\Utils::array_and($value, fn(...$args) => \ryunosuke\SimpleCache\Utils::is_primitive(...$args))) {
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
            if ($value instanceof \Generator) {
                $ref = new \ReflectionGenerator($value);
                $reffunc = $ref->getFunction();

                if ($reffunc->getNumberOfRequiredParameters() > 0) {
                    throw new \DomainException('required argument Generator is not support.');
                }

                $caller = null;
                if ($reffunc instanceof \ReflectionFunction) {
                    if ($reffunc->isClosure()) {
                        $caller = "({$export($reffunc->getClosure(), $nest)})";
                    }
                    else {
                        $caller = $reffunc->name;
                    }
                }
                if ($reffunc instanceof \ReflectionMethod) {
                    if ($reffunc->isStatic()) {
                        $caller = "{$reffunc->class}::{$reffunc->name}";
                    }
                    else {
                        $caller = "{$export($ref->getThis(), $nest)}->{$reffunc->name}";
                    }
                }
                return "\$this->$vid = {$caller}()";
            }
            if ($value instanceof \Closure) {
                $ref = new \ReflectionFunction($value);
                $bind = $ref->getClosureThis();
                $class = $ref->getClosureScopeClass();
                $statics = $ref->getStaticVariables();

                // 内部由来はきちんと fromCallable しないと差異が出てしまう
                if ($ref->isInternal()) {
                    $receiver = $bind ?? $class?->getName();
                    $callee = $receiver ? [$receiver, $ref->getName()] : $ref->getName();
                    return "\$this->$vid = \\Closure::fromCallable({$export($callee, $nest)})";
                }

                [$meta, $body] = \ryunosuke\SimpleCache\Utils::callable_code($value);
                $arrow = \ryunosuke\SimpleCache\Utils::starts_with($meta, 'fn') ? ' => ' : ' ';
                $tokens = array_slice(\ryunosuke\SimpleCache\Utils::php_parse("<?php $meta{$arrow}$body;", TOKEN_PARSE), 1, -1);

                $uses = [];
                $context = [
                    'class' => 0,
                    'brace' => 0,
                ];
                foreach ($tokens as $n => $token) {
                    $prev = $neighborToken($n, -1, $tokens) ?? (object) ['id' => null, 'text' => null, 'line' => null];
                    $next = $neighborToken($n, +1, $tokens) ?? (object) ['id' => null, 'text' => null, 'line' => null];

                    // クロージャは何でもかける（クロージャ・無名クラス・ジェネレータ etc）のでネスト（ブレース）レベルを記録しておく
                    if ($token->text === '{') {
                        $context['brace']++;
                    }
                    if ($token->text === '}') {
                        $context['brace']--;
                    }

                    // 無名クラスは色々厄介なので読み飛ばすために覚えておく
                    if ($prev->id === T_NEW && $token->id === T_CLASS) {
                        $context['class'] = $context['brace'];
                    }
                    // そして無名クラスは色々かける上に終了条件が自明ではない（シンタックスエラーでない限りは {} が一致するはず）
                    if ($token->text === '}' && $context['class'] === $context['brace']) {
                        $context['class'] = 0;
                    }

                    // fromCallable 由来だと名前がついてしまう
                    if (!$context['class'] && $prev->id === T_FUNCTION && $token->id === T_STRING) {
                        unset($tokens[$n]);
                        continue;
                    }

                    // use 変数の導出
                    if ($token->id === T_VARIABLE) {
                        $varname = substr($token->text, 1);
                        // クロージャ内クロージャの use に反応してしまうので存在するときのみとする
                        if (array_key_exists($varname, $statics) && !isset($uses[$varname])) {
                            $recurself = $statics[$varname] === $value ? '&' : '';
                            $uses[$varname] = "$spacer1\$$varname = $recurself{$export($statics[$varname], $nest + 1)};\n";
                        }
                    }

                    $tokens[$n] = $resolveSymbol($token, $prev, $next, $ref);
                }

                $code = \ryunosuke\SimpleCache\Utils::php_indent(implode('', array_column($tokens, 'text')), [
                    'indent'   => $spacer1,
                    'baseline' => -1,
                ]);
                if ($bind) {
                    $instance = $export($bind, $nest + 1);
                    if ($class->isAnonymous()) {
                        $scope = "get_class({$export($bind, $nest + 1)})";
                    }
                    else {
                        $scope = $var_export($class?->getName() === 'Closure' ? 'static' : $class?->getName());
                    }
                    $code = "\Closure::bind($code, $instance, $scope)";
                }
                elseif (!\ryunosuke\SimpleCache\Utils::is_bindable_closure($value)) {
                    $code = "static $code";
                }

                return "\$this->$vid = (function () {\n{$raw_export(implode('', $uses))}{$spacer1}return $code;\n$spacer0})->call(\$this)";
            }
            if (is_object($value)) {
                $ref = new \ReflectionObject($value);

                // enum はリテラルを返せばよい
                if ($value instanceof \UnitEnum) {
                    $declare = "\\$ref->name::$value->name";
                    if ($ref->getConstant($value->name) === $value) {
                        return "\$this->$vid = $declare";
                    }
                    // enum の polyfill で、__callStatic を利用して疑似的にエミュレートしているライブラリは多い
                    // もっとも、「多い」だけであり、そうとは限らないので値は見る必要はある（例外が飛ぶかもしれないので try も必要）
                    if ($ref->hasMethod('__callStatic')) {
                        try {
                            if ($declare() === $value) {
                                return "\$this->$vid = $declare()";
                            }
                        }
                        catch (\Throwable) { // @codeCoverageIgnore
                            // through. treat regular object
                        }
                    }
                }

                // 弱参照系は同時に渡ってきていれば復元できる
                if ($value instanceof \WeakReference) {
                    $weakreference = $value->get();
                    if ($weakreference === null) {
                        $weakreference = new \stdClass();
                    }
                    return "\$this->$vid = \\WeakReference::create({$export($weakreference, $nest)})";
                }
                if ($value instanceof \WeakMap) {
                    $weakmap = "{$spacer1}\$this->$vid = new \\WeakMap();\n";
                    foreach ($value as $object => $data) {
                        $weakmap .= "{$spacer1}\$this->{$vid}[{$export($object)}] = {$export($data)};\n";
                    }
                    return "\$this->$vid = (function () {\n{$weakmap}{$spacer1}return \$this->$vid;\n$spacer0})->call(\$this)";
                }

                // 内部クラスで serialize 出来ないものは __PHP_Incomplete_Class で代替（復元時に無視する）
                try {
                    if ($ref->isInternal()) {
                        serialize($value);
                    }
                }
                catch (\Exception) {
                    return "\$this->$vid = new \\__PHP_Incomplete_Class()";
                }

                // 無名クラスは定義がないのでパースが必要
                // さらにコンストラクタを呼ぶわけには行かない（引数を検出するのは不可能）ので潰す必要もある
                if ($ref->isAnonymous()) {
                    $fname = $ref->getFileName();
                    $sline = $ref->getStartLine();
                    $eline = $ref->getEndLine();
                    $tokens = \ryunosuke\SimpleCache\Utils::php_parse('<?php ' . implode('', array_slice(file($fname), $sline - 1, $eline - $sline + 1)));

                    $block = [];
                    $starting = false;
                    $constructing = 0;
                    $nesting = 0;
                    foreach ($tokens as $n => $token) {
                        $prev = $neighborToken($n, -1, $tokens) ?? [null, null, null];
                        $next = $neighborToken($n, +1, $tokens) ?? [null, null, null];

                        // 無名クラスは new class か new #[Attribute] で始まるはず（new #[A] ClassName は許可されていない）
                        if (($token->id === T_NEW && $next->id === T_CLASS) || ($token->id === T_NEW && $next->id === T_ATTRIBUTE)) {
                            $starting = true;
                        }
                        if (!$starting) {
                            continue;
                        }

                        // コンストラクタの呼び出し引数はスキップする
                        if ($constructing !== null) {
                            if ($token->text === '(') {
                                $constructing++;
                            }
                            if ($token->text === ')') {
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

                        // 引数ありコンストラクタは呼ばないのでリネームしておく
                        if ($token->text === '__construct' && $ref->getConstructor() && $ref->getConstructor()->getNumberOfRequiredParameters()) {
                            $token = clone $token;
                            $token->text = "replaced__construct";
                        }

                        $block[] = $resolveSymbol($token, $prev, $next, $ref);

                        if ($token->text === '{') {
                            $nesting++;
                        }
                        if ($token->text === '}') {
                            $nesting--;
                            if ($nesting === 0) {
                                break;
                            }
                        }
                    }

                    $code = \ryunosuke\SimpleCache\Utils::php_indent(implode('', array_column($block, 'text')), [
                        'indent'   => $spacer1,
                        'baseline' => -1,
                    ]);
                    if ($raw) {
                        return $code;
                    }
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
                    $fields = array_intersect_key(\ryunosuke\SimpleCache\Utils::object_properties($value, $privates), array_flip($value->__sleep()));
                }
                // それ以外は適当に漁る
                else {
                    $fields = \ryunosuke\SimpleCache\Utils::object_properties($value, $privates);
                }

                return "\$this->new(\$this->$vid, $classname, (function () {\n{$spacer1}return {$export([$fields, $privates], $nest + 1)};\n{$spacer0}}))";
            }
            if (\ryunosuke\SimpleCache\Utils::is_resourcable($value)) {
                // スタンダードなリソースなら復元できないこともない
                $meta = stream_get_meta_data($value);
                $stream_type = strtolower($meta['stream_type']);
                if (!in_array($stream_type, ['stdio', 'output', 'temp', 'memory'], true)) {
                    throw new \DomainException('resource is supported stream resource only.');
                }
                $meta['position'] = @ftell($value);
                $meta['context'] = stream_context_get_options($value);
                $meta['buffer'] = null;
                if (in_array($stream_type, ['temp', 'memory'], true)) {
                    $meta['buffer'] = stream_get_contents($value, null, 0);
                }
                return "\$this->$vid = \$this->open({$export($meta, $nest + 1)})";
            }

            return is_null($value) ? 'null' : $var_export($value);
        };

        $exported = $export($value, 1);
        $others = [];
        $vars = [];
        foreach ($var_manager->orphan() as $rid => [$isref, $vid, $var]) {
            $declare = $isref ? "&\$this->$vid" : $export($var, 1);
            $others[] = "\$this->$rid = $declare;";
        }

        static $factory = null;
        if ($factory === null) {
            // @codeCoverageIgnoreStart
            $factory = $export(new #[\AllowDynamicProperties] class() {
                public function new(&$object, $class, $provider)
                {
                    if ($class instanceof \Closure) {
                        $object = $class();
                        $reflection = $this->reflect(get_class($object));
                    }
                    else {
                        $reflection = $this->reflect($class);
                        if ($reflection["constructor"] && $reflection["constructor"]->getNumberOfRequiredParameters() === 0) {
                            $object = $reflection["self"]->newInstance();
                        }
                        else {
                            $object = $reflection["self"]->newInstanceWithoutConstructor();
                        }
                    }
                    [$fields, $privates] = $provider();

                    if ($reflection["unserialize"]) {
                        $object->__unserialize($fields);
                        return $object;
                    }

                    foreach ($reflection["parents"] as $parent) {
                        foreach ($this->reflect($parent->name)["properties"] as $name => $property) {
                            if (isset($privates[$parent->name][$name]) && !$privates[$parent->name][$name] instanceof \__PHP_Incomplete_Class) {
                                $property->setValue($object, $privates[$parent->name][$name]);
                            }
                            if (array_key_exists($name, $fields)) {
                                if (!$fields[$name] instanceof \__PHP_Incomplete_Class) {
                                    $property->setValue($object, $fields[$name]);
                                }
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

                public function open($metadata)
                {
                    $resource = fopen($metadata['uri'], $metadata['mode'], false, stream_context_create($metadata['context']));
                    if ($resource === false) {
                        return null;
                    }
                    if ($metadata['seekable'] && is_string($metadata['buffer'])) {
                        fwrite($resource, $metadata['buffer']);
                    }
                    if ($metadata['seekable'] && is_int($metadata['position'])) {
                        fseek($resource, $metadata['position']);
                    }
                    return $resource;
                }

                private function reflect($class)
                {
                    static $cache = [];
                    if (!isset($cache[$class])) {
                        $refclass = new \ReflectionClass($class);
                        $cache[$class] = [
                            "self"        => $refclass,
                            "constructor" => $refclass->getConstructor(),
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
            }, -1, true);
            // @codeCoverageIgnoreEnd
        }

        $E = fn($v) => $v;
        $result = <<<PHP
            (function () {
                {$E(implode("\n    ", $others))}
                return $exported;
            })->call($factory)
            PHP;

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

        return \ryunosuke\SimpleCache\Utils::base64url_encode($hash);
    }
}
