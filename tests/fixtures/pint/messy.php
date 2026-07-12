<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Commenting.FileExpectedTags
// phpcs:disable moodle.Commenting.MissingDocblock
// phpcs:disable moodle.NamingConventions.ValidFunctionName
// phpcs:disable moodle.NamingConventions.ValidVariableName
// phpcs:disable moodle.NamingConventions.ValidVariableName
// phpcs:disable PSR1.Classes.ClassDeclaration
// phpcs:disable moodle.Commenting.VariableComment

declare(strict_types = 1);

namespace local_devkit\tests\fixtures;

use core\exception\coding_exception;
use dml_exception;
use invalid_import\should_not_exist;
use moodle_exception;
use stdClass;
use function array_filter as arr_filter;
use function array_map;
use const PHP_EOL;
use const PHP_INT_MAX;

/**
 * Messy fixture for testing Pint config.
 */

define('MESSY_GLOBAL_CONST', 42);

/**
 * Messy interface
 */
interface MessyInterface
{
    public function doSomething(string $name): string;

        public function doOtherThing(int $count): int;

    public function withDefault(string $value = 'default'): string;
}

/**
 * Messy trait
 */
trait MessyTrait
{

    private string $traitProp = 'hello';

    public function traitMethod(string $input ): string
    {
        return strtoupper( $input );
    }

        protected function traitHelper( array $items): array
    {
        $result=[];
            foreach($items as $item){
            $result[]=$item*2;
        }
    return $result;
    }

}

/**
 * Messy abstract class
 */
abstract class MessyAbstract
{
    abstract public function process(string $data): string;

    protected static function utility(): void {
            echo "utility\n";
    }

}

enum MessyStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
        case Pending = 'pending';

    public function label(): string
    {
        return match($this){
            self::Active=>'Active',
            self::Inactive=> 'Inactive',
            self::Pending =>'Pending',
        };
    }
}


class MessyParent
{
    public const CONST_A=1;
    public const CONST_B='two';
    private const CONST_C=[1,2,3];

    public int $publicProp=0;
    protected string $protectedProp='test';
    private array $privateProp=[];
    static string $staticProp = 'static';

    public function __construct(
        public string $name,
        protected ?int $age = null,
        private array $tags=[],
        bool $legacy = false,
    ) {
        $this->name =trim($name);
            if($age !== null){
            $this->age=$age;
        }
                $this->tags = $tags;
    }

    public function getInfo(): string
    {
        $parts=[];
        $parts[]="Name: {$this->name}";
        if($this->age !==null){
            $parts[]='Age: '.$this->age;
        }
            if(!empty($this->tags)){
            $parts[]='Tags: '.implode(', ',$this->tags);
        }
        return implode(' | ',$parts);
    }

    public function processItems(array $items): array{
        $results=array_map(function($item){
            return $item*2;
        },$items);
            return arr_filter($results,fn($r)=>$r>5);
    }

    protected function helper(string $input): string
    {
        return match (true) {
                strlen($input) > 10 => 'long',
            strlen($input) > 5 => 'medium',
                default => 'short',
        };
    }

    private function complex(array $data): stdClass
    {

        $obj=new stdClass();
        $obj->name=$data['name']??'unknown';
        $obj->count=isset($data['count'])?$data['count']:0;
        $obj->items =array_values(array_filter($data['items']??[],fn($i)=>$i!==null));
        return $obj;
    }

    final public function finalMethod(): void
    {
            echo "final\n";
    }

}


final class MessyChild extends MessyParent implements MessyInterface
{
    use MessyTrait;

    public function doSomething(string $name): string
    {
            return "Hello, $name!";
    }

    public function doOtherThing(int $count): int
    {
        return $count*2;
    }

    public function withDefault(string $value = 'default'): string
    {
        return $value;
    }

    public function getInfo(): string
    {
        $base=parent::getInfo();
        return "[$base]";
    }

}


function messy_global_function(string $input, ?int $limit =null, bool $flag=false): ?string
{
    if($input===''){
        return null;
    }
        $result=trim($input);
    if($limit!==null){
        $result=substr($result,0,$limit);
    }
    if($flag){
        $result=strtoupper($result);
    }
    return $result;
}


class MessyAttributes
{
    public function __construct(
        #[\SensitiveParameter]
        public string $secret,
    ) {
    }

    #[Deprecated('use something else')]
    public function oldMethod(): void
    {
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->secret;
    }
}


abstract class MessyAbstractChild extends MessyAbstract
{
    public function process(string $data): string
    {
        return strtolower($data);
    }
}


function messy_match_example(int $value): string
{
    return match($value){
        1=>'one',
        2,3=>'two or three',
        4,5,6=>'four to six',
        default=>'other',
    };
}


class MessyNested
{
    private array $nested = [];

    public function add(string $key, mixed $value): void
    {
        $this->nested[$key] = $value;
    }

    public function getNested(): array
    {
        return $this->nested;
    }

    public function getOrThrow(string $key): mixed
    {
        return $this->nested[$key]??throw new \RuntimeException("Key not found: $key");
    }
}


function messy_long_function(
    string $param1,
    ?int $param2 = null,
    array $param3 = [],
    bool $param4 = false,
    string $param5 = 'default',
    float $param6 = 0.0,
    mixed $param7 = null,
): array {
    return [
        'param1' => $param1,
        'param2' => $param2,
        'param3' => $param3,
        'param4' => $param4,
        'param5' => $param5,
        'param6' => $param6,
        'param7' => $param7,
    ];
}


$anonymous = new class {
    public function run(): string {
        return 'anonymous';
    }
};


function messy_arrow_examples(array $items): array
{
    return array_map(
        fn($item) => $item *2,
        $items,
    );
}


function messy_closure_examples(array $items): array
{
    $multiplier=3;
    return array_map(function($item)use($multiplier){
        return $item*$multiplier;
    },
            $items
    );
}


function messy_heredoc_example(string $name): string
{
    $greeting=<<<TEXT
Hello, $name!
This is a heredoc.
TEXT;
    return $greeting;
}


function messy_nowdoc_example(): string
{
    $text=<<<'TEXT'
This is a nowdoc.
No variable $expansion.
TEXT;
    return $text;
}


function messy_nullsafe(string $input): ?string
{
    $obj = new stdClass();
    $obj->nested = new stdClass();
    $obj->nested->value = $input;
    return $obj?->nested?->value;
}


class MessyPromotion
{
    public function __construct(
        private string $name,
        private int $count =0,
        protected ?array $data = null,
        public readonly bool $active = false,
    ) {
    }

    public function display(): string {
        return "{$this->name}: {$this->count}";
    }
}


trait MessySecondTrait
{
    abstract public function requiredMethod(): void;

    public function secondTraitMethod(): string
    {
        return 'from second trait';
    }
}


class MessyMultiTrait
{
    use MessyTrait;
    use MessySecondTrait;

    public function requiredMethod(): void
    {
            echo "implemented\n";
    }

    public function traitMethod(string $input): string
    {
        return strtolower($input);
    }

}


function messy_type_hints(): array
{
    $items = ['a','b','c'];
    $map = [
        'key1' => 'value1',
        'key2' => 'value2',
    ];
    return [
        'items' => $items,
        'map' => $map,
        'count' => count($items),
    ];
}


function messy_named_args(): string
{
    return substr(
        string: 'Hello World',
        offset: 0,
        length: 5,
    );
}


class MessyMagic
{
    private array $data = [];

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return null;
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        return null;
    }

}


interface SecondInterface
{
public function secondMethod(): void;
}

interface ThirdInterface
{
    public function thirdMethod(): string;
}


class MessyMultiInterface implements
    MessyInterface,
    SecondInterface,
    ThirdInterface
{
    public function doSomething(string $name): string
    {
        return $name;
    }

    public function doOtherThing(int $count): int
    {
        return $count;
    }

    public function withDefault(string $value = 'default'): string
    {
        return $value;
    }

    public function secondMethod(): void
    {
            echo "second\n";
    }

    public function thirdMethod(): string
    {
        return 'third';
    }
}


function messy_switch(int $value): string
{
    switch($value){
        case 1:
            return 'one';
        case 2:
        case 3:
            return 'two or three';
        default:
            return 'other';
    }
}


function messy_try_catch(): void
{
    try{
        $result=10/0;
    }catch(\DivisionByZeroError $e){
        echo "caught: {$e->getMessage()}";
    }catch(\Throwable $e){
        echo "error: {$e->getMessage()}";
    }finally{
        echo "done";
    }
}


$messy_global_var = 'global';

const MESSY_NAMESPACED_CONST = 'value';


function messy_array_shapes(): array
{
    $users = [
        [
            'id' => 1,
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'roles' => ['admin','user'],
            'meta' => [
                'last_login' => '2024-01-01',
                'login_count' => 42,
            ],
        ],
        [
            'id' => 2,
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'roles' => ['user'],
            'meta' => [
                'last_login' => '2024-01-02',
                'login_count' => 10,
            ],
        ],
    ];
    return $users;
}
