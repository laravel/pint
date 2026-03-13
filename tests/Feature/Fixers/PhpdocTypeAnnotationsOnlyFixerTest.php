<?php

use App\Fixers\TypeAnnotationsOnlyFixer;
use PhpCsFixer\Tokenizer\Tokens;

beforeEach(function () {
    $this->fixer = new TypeAnnotationsOnlyFixer;
});

function fixCode(TypeAnnotationsOnlyFixer $fixer, string $code, ?string $filePath = null): string
{
    $tokens = Tokens::fromCode($code);
    $fixer->fix(new SplFileInfo($filePath ?? __FILE__), $tokens);

    return $tokens->generateCode();
}

it('removes comments that do not contain annotations', function (string $input, string $expected) {
    expect(fixCode($this->fixer, $input))->toBe($expected);
})->with([
    'single-line double-slash comment' => [
        <<<'PHP'
        <?php

        // This is a comment
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'hash comment' => [
        <<<'PHP'
        <?php

        # This is a hash comment
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'inline block comment' => [
        <<<'PHP'
        <?php

        /* This is a block comment */
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'trailing comment' => [
        <<<'PHP'
        <?php

        $x = 1; // trailing comment
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP.' ',
    ],
    'multiline block comment without annotations' => [
        <<<'PHP'
        <?php

        /*
         * Multi-line
         * comment.
         */
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'pure-prose docblock' => [
        <<<'PHP'
        <?php

        /**
         * Just a description.
         * No annotations.
         */
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'TODO comment' => [
        <<<'PHP'
        <?php

        // TODO: refactor this
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'commented-out code' => [
        <<<'PHP'
        <?php

        // $old = new Thing();
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'docblock with no annotation lines' => [
        <<<'PHP'
        <?php

        /**
         * This class is amazing.
         *
         * It handles everything.
         */
        class Foo {}
        PHP,
        <<<'PHP'
        <?php

        class Foo {}
        PHP,
    ],
    'empty docblock' => [
        <<<'PHP'
        <?php

        /**
         *
         */
        class Foo {}
        PHP,
        <<<'PHP'
        <?php

        class Foo {}
        PHP,
    ],
    'multiple sequential comments' => [
        <<<'PHP'
        <?php

        // First comment
        // Second comment
        // Third comment
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'controller with prose-only docblock' => [
        <<<'PHP'
        <?php

        class UserController extends Controller
        {
            /**
             * Display a listing of users.
             */
            public function index()
            {
                return User::all();
            }
        }
        PHP,
        "<?php\n\nclass UserController extends Controller\n{\n    public function index()\n    {\n        return User::all();\n    }\n}",
    ],
    'service provider with prose docblocks' => [
        <<<'PHP'
        <?php

        class AppServiceProvider extends ServiceProvider
        {
            /**
             * Register any application services.
             */
            public function register()
            {
            }

            /**
             * Bootstrap any application services.
             */
            public function boot()
            {
            }
        }
        PHP,
        "<?php\n\nclass AppServiceProvider extends ServiceProvider\n{\n    public function register()\n    {\n    }\n\n    public function boot()\n    {\n    }\n}",
    ],
    'migration with section comments' => [
        <<<'PHP'
        <?php

        return new class extends Migration
        {
            /**
             * Run the migrations.
             */
            public function up()
            {
                // Create the users table
                Schema::create('users', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->timestamps();
                });
            }
        };
        PHP,
        "<?php\n\nreturn new class extends Migration\n{\n    public function up()\n    {\n        Schema::create('users', function (Blueprint \$table) {\n            \$table->id();\n            \$table->string('name');\n            \$table->timestamps();\n        });\n    }\n};",
    ],
    'config file with block and inline comments' => [
        <<<'PHP'
        <?php

        return [
            /*
            |--------------------------------------------------------------------------
            | Application Name
            |--------------------------------------------------------------------------
            |
            | This value is the name of your application.
            |
            */

            'name' => env('APP_NAME', 'Laravel'),

            // The application URL
            'url' => env('APP_URL', 'http://localhost'),
        ];
        PHP,
        "<?php\n\nreturn [\n    'name' => env('APP_NAME', 'Laravel'),\n\n    'url' => env('APP_URL', 'http://localhost'),\n];",
    ],
    'route grouping comments' => [
        <<<'PHP'
        <?php

        // Authentication routes
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // User management
        Route::resource('users', UserController::class);
        PHP,
        <<<'PHP'
        <?php

        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::resource('users', UserController::class);
        PHP,
    ],
    'section divider comments' => [
        <<<'PHP'
        <?php

        class UserController extends Controller
        {
            // ==================
            // Authentication
            // ==================

            public function login() {}

            // ==================
            // Profile
            // ==================

            public function profile() {}
        }
        PHP,
        "<?php\n\nclass UserController extends Controller\n{\n    public function login() {}\n\n    public function profile() {}\n}",
    ],
]);

it('preserves comments that contain annotations', function (string $input, string $expected) {
    expect(fixCode($this->fixer, $input))->toBe($expected);
})->with([
    'single-line comment with @phpstan-ignore' => [
        <<<'PHP'
        <?php

        // @phpstan-ignore-next-line
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        // @phpstan-ignore-next-line
        $x = 1;
        PHP,
    ],
    'inline @phpstan-ignore-line' => [
        <<<'PHP'
        <?php

        $x = someDynamicCall(); // @phpstan-ignore-line
        PHP,
        <<<'PHP'
        <?php

        $x = someDynamicCall(); // @phpstan-ignore-line
        PHP,
    ],
    'single-line docblock with @var' => [
        <<<'PHP'
        <?php

        /** @var array<int, string> $items */
        $items = [];
        PHP,
        <<<'PHP'
        <?php

        /** @var array<int, string> $items */
        $items = [];
        PHP,
    ],
    'annotation-only docblock is unchanged' => [
        <<<'PHP'
        <?php

        /**
         * @param  string  $value
         */
        function clean($value) {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @param  string  $value
         */
        function clean($value) {}
        PHP,
    ],
    '@phpstan-type annotation' => [
        <<<'PHP'
        <?php

        /**
         * @phpstan-type UserData array{name: string, age: int}
         */
        class User {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @phpstan-type UserData array{name: string, age: int}
         */
        class User {}
        PHP,
    ],
    '@psalm-type annotation' => [
        <<<'PHP'
        <?php

        /**
         * @psalm-type UserId = int
         */
        class User {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @psalm-type UserId = int
         */
        class User {}
        PHP,
    ],
    'inline {@inheritDoc}' => [
        <<<'PHP'
        <?php

        /**
         * {@inheritDoc}
         */
        function greet() {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * {@inheritDoc}
         */
        function greet() {}
        PHP,
    ],
    'eloquent model with @property annotations' => [
        <<<'PHP'
        <?php

        /**
         * @property int $id
         * @property string $name
         * @property string $email
         * @property \Carbon\Carbon $created_at
         */
        class User extends Model {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @property int $id
         * @property string $name
         * @property string $email
         * @property \Carbon\Carbon $created_at
         */
        class User extends Model {}
        PHP,
    ],
    'model with @mixin' => [
        <<<'PHP'
        <?php

        /**
         * @mixin \Illuminate\Database\Eloquent\Builder<User>
         */
        class User extends Model {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @mixin \Illuminate\Database\Eloquent\Builder<User>
         */
        class User extends Model {}
        PHP,
    ],
    '@property-read and @property-write' => [
        <<<'PHP'
        <?php

        /**
         * @property-read float $total
         * @property-read string $status_label
         * @property-write string $raw_status
         */
        class Order extends Model {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @property-read float $total
         * @property-read string $status_label
         * @property-write string $raw_status
         */
        class Order extends Model {}
        PHP,
    ],
    '@template generics' => [
        <<<'PHP'
        <?php

        /**
         * @template TModel of \Illuminate\Database\Eloquent\Model
         */
        abstract class Repository {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @template TModel of \Illuminate\Database\Eloquent\Model
         */
        abstract class Repository {}
        PHP,
    ],
    '@extends and @implements' => [
        <<<'PHP'
        <?php

        /**
         * @extends Repository<User>
         * @implements Countable
         */
        class UserRepository extends Repository implements Countable {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @extends Repository<User>
         * @implements Countable
         */
        class UserRepository extends Repository implements Countable {}
        PHP,
    ],
    'collection @var inside method' => [
        <<<'PHP'
        <?php

        class UserService
        {
            public function getActive()
            {
                /** @var \Illuminate\Support\Collection<int, User> $users */
                $users = User::where('active', true)->get();

                return $users;
            }
        }
        PHP,
        <<<'PHP'
        <?php

        class UserService
        {
            public function getActive()
            {
                /** @var \Illuminate\Support\Collection<int, User> $users */
                $users = User::where('active', true)->get();

                return $users;
            }
        }
        PHP,
    ],
    '@phpstan-assert' => [
        <<<'PHP'
        <?php

        /**
         * @phpstan-assert string $value
         */
        function assertString($value) {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @phpstan-assert string $value
         */
        function assertString($value) {}
        PHP,
    ],
    '@phpstan-param and @phpstan-return' => [
        <<<'PHP'
        <?php

        /**
         * @phpstan-param callable(TValue, TKey): TMapValue $callback
         * @phpstan-return static<TKey, TMapValue>
         */
        function map(callable $callback) {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @phpstan-param callable(TValue, TKey): TMapValue $callback
         * @phpstan-return static<TKey, TMapValue>
         */
        function map(callable $callback) {}
        PHP,
    ],
    '@param with multi-line array shape' => [
        <<<'PHP'
        <?php

        /**
         * @param  array{
         *     name?: string,
         *     email?: string,
         *     email_verified_at?: DateTimeInterface|null,
         *     has_ever_verified_email?: bool
         * }  $attributes
         * @return User
         */
        function updateUser(User $user, array $attributes): User {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @param  array{
         *     name?: string,
         *     email?: string,
         *     email_verified_at?: DateTimeInterface|null,
         *     has_ever_verified_email?: bool
         * }  $attributes
         * @return User
         */
        function updateUser(User $user, array $attributes): User {}
        PHP,
    ],
    '@param with multi-line array shape and prose' => [
        <<<'PHP'
        <?php

        /**
         * Update the user attributes.
         *
         * @param  array{
         *     name?: string,
         *     email?: string,
         * }  $attributes
         */
        function updateUser(User $user, array $attributes): User {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @param  array{
         *     name?: string,
         *     email?: string,
         * }  $attributes
         */
        function updateUser(User $user, array $attributes): User {}
        PHP,
    ],
]);

it('strips prose but keeps annotation lines in mixed docblocks', function (string $input, string $expected) {
    expect(fixCode($this->fixer, $input))->toBe($expected);
})->with([
    'docblock with prose and @param/@return' => [
        <<<'PHP'
        <?php

        /**
         * A helper.
         *
         * It does math.
         *
         * @param  int  $a
         * @param  int  $b
         * @return int
         */
        function add($a, $b) {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @param  int  $a
         * @param  int  $b
         * @return int
         */
        function add($a, $b) {}
        PHP,
    ],
    'indented docblock inside a class' => [
        <<<'PHP'
        <?php

        class Foo
        {
            /**
             * This does something.
             *
             * @param  string  $name
             * @return void
             */
            public function bar($name) {}
        }
        PHP,
        <<<'PHP'
        <?php

        class Foo
        {
            /**
             * @param  string  $name
             * @return void
             */
            public function bar($name) {}
        }
        PHP,
    ],
    'eloquent model prose stripped, @property kept' => [
        <<<'PHP'
        <?php

        /**
         * The User model.
         *
         * @property int $id
         * @property string $name
         * @property string $email
         * @property \Carbon\Carbon $created_at
         */
        class User extends Model {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @property int $id
         * @property string $name
         * @property string $email
         * @property \Carbon\Carbon $created_at
         */
        class User extends Model {}
        PHP,
    ],
    'model prose stripped, @mixin kept' => [
        <<<'PHP'
        <?php

        /**
         * The User model represents an authenticated user.
         *
         * @mixin \Illuminate\Database\Eloquent\Builder<User>
         */
        class User extends Model {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @mixin \Illuminate\Database\Eloquent\Builder<User>
         */
        class User extends Model {}
        PHP,
    ],
    'relationship with prose and @return' => [
        <<<'PHP'
        <?php

        class User extends Model
        {
            /**
             * Get the posts for the user.
             *
             * @return \Illuminate\Database\Eloquent\Relations\HasMany<Post, $this>
             */
            public function posts()
            {
                return $this->hasMany(Post::class);
            }
        }
        PHP,
        <<<'PHP'
        <?php

        class User extends Model
        {
            /**
             * @return \Illuminate\Database\Eloquent\Relations\HasMany<Post, $this>
             */
            public function posts()
            {
                return $this->hasMany(Post::class);
            }
        }
        PHP,
    ],
    'form request with prose and @return' => [
        <<<'PHP'
        <?php

        class StoreUserRequest extends FormRequest
        {
            /**
             * Determine if the user is authorized.
             *
             * @return bool
             */
            public function authorize()
            {
                return true;
            }

            /**
             * Get the validation rules.
             *
             * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
             */
            public function rules()
            {
                return [
                    'name' => 'required|string|max:255',
                ];
            }
        }
        PHP,
        <<<'PHP'
        <?php

        class StoreUserRequest extends FormRequest
        {
            /**
             * @return bool
             */
            public function authorize()
            {
                return true;
            }

            /**
             * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
             */
            public function rules()
            {
                return [
                    'name' => 'required|string|max:255',
                ];
            }
        }
        PHP,
    ],
    'facade with prose, @method, and @see' => [
        <<<'PHP'
        <?php

        /**
         * The Cache facade.
         *
         * @method static bool has(string $key)
         * @method static mixed get(string $key, mixed $default = null)
         * @method static bool put(string $key, mixed $value, int $ttl = null)
         *
         * @see \Illuminate\Cache\CacheManager
         */
        class Cache extends Facade {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @method static bool has(string $key)
         * @method static mixed get(string $key, mixed $default = null)
         * @method static bool put(string $key, mixed $value, int $ttl = null)
         * @see \Illuminate\Cache\CacheManager
         */
        class Cache extends Facade {}
        PHP,
    ],
    'middleware with prose and Closure type' => [
        <<<'PHP'
        <?php

        class EnsureTokenIsValid
        {
            /**
             * Handle an incoming request.
             *
             * Check if the token is valid before proceeding.
             * This is critical for API security.
             *
             * @param  \Illuminate\Http\Request  $request
             * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
             * @return \Symfony\Component\HttpFoundation\Response
             */
            public function handle($request, $next)
            {
                return $next($request);
            }
        }
        PHP,
        <<<'PHP'
        <?php

        class EnsureTokenIsValid
        {
            /**
             * @param  \Illuminate\Http\Request  $request
             * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
             * @return \Symfony\Component\HttpFoundation\Response
             */
            public function handle($request, $next)
            {
                return $next($request);
            }
        }
        PHP,
    ],
    '@throws with prose' => [
        <<<'PHP'
        <?php

        class PaymentService
        {
            /**
             * Process a payment.
             *
             * @param  float  $amount
             * @throws \App\Exceptions\PaymentFailedException
             * @throws \InvalidArgumentException
             */
            public function charge($amount)
            {
            }
        }
        PHP,
        <<<'PHP'
        <?php

        class PaymentService
        {
            /**
             * @param  float  $amount
             * @throws \App\Exceptions\PaymentFailedException
             * @throws \InvalidArgumentException
             */
            public function charge($amount)
            {
            }
        }
        PHP,
    ],
    '@deprecated with prose' => [
        <<<'PHP'
        <?php

        class LegacyService
        {
            /**
             * Use NewService::handle() instead.
             *
             * @deprecated
             * @return void
             */
            public function oldMethod()
            {
            }
        }
        PHP,
        <<<'PHP'
        <?php

        class LegacyService
        {
            /**
             * @deprecated
             * @return void
             */
            public function oldMethod()
            {
            }
        }
        PHP,
    ],
    '@template with prose on generic repository' => [
        <<<'PHP'
        <?php

        /**
         * A generic repository pattern.
         *
         * @template TModel of \Illuminate\Database\Eloquent\Model
         */
        abstract class Repository
        {
            /**
             * Find a model by its primary key.
             *
             * @param  int  $id
             * @return TModel|null
             */
            abstract public function find($id);
        }
        PHP,
        <<<'PHP'
        <?php

        /**
         * @template TModel of \Illuminate\Database\Eloquent\Model
         */
        abstract class Repository
        {
            /**
             * @param  int  $id
             * @return TModel|null
             */
            abstract public function find($id);
        }
        PHP,
    ],
    '@extends and @implements with prose' => [
        <<<'PHP'
        <?php

        /**
         * User repository implementation.
         *
         * @extends Repository<User>
         * @implements Countable
         */
        class UserRepository extends Repository implements Countable {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @extends Repository<User>
         * @implements Countable
         */
        class UserRepository extends Repository implements Countable {}
        PHP,
    ],
    '@property-read/@property-write with prose' => [
        <<<'PHP'
        <?php

        /**
         * The order model with computed properties.
         *
         * @property-read float $total
         * @property-read string $status_label
         * @property-write string $raw_status
         */
        class Order extends Model {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @property-read float $total
         * @property-read string $status_label
         * @property-write string $raw_status
         */
        class Order extends Model {}
        PHP,
    ],
    'closure type with prose' => [
        <<<'PHP'
        <?php

        class Pipeline
        {
            /**
             * Send the item through the pipeline.
             *
             * @param  \Closure(mixed): mixed  $callback
             * @return $this
             */
            public function pipe($callback)
            {
                return $this;
            }
        }
        PHP,
        <<<'PHP'
        <?php

        class Pipeline
        {
            /**
             * @param  \Closure(mixed): mixed  $callback
             * @return $this
             */
            public function pipe($callback)
            {
                return $this;
            }
        }
        PHP,
    ],
    '@phpstan-assert with prose' => [
        <<<'PHP'
        <?php

        class TypeGuard
        {
            /**
             * Assert the value is a string.
             *
             * @phpstan-assert string $value
             */
            public static function string($value) {}
        }
        PHP,
        <<<'PHP'
        <?php

        class TypeGuard
        {
            /**
             * @phpstan-assert string $value
             */
            public static function string($value) {}
        }
        PHP,
    ],
    '@phpstan-param and @phpstan-return with prose' => [
        <<<'PHP'
        <?php

        class Collection
        {
            /**
             * Map the collection.
             *
             * @phpstan-param callable(TValue, TKey): TMapValue $callback
             * @phpstan-return static<TKey, TMapValue>
             */
            public function map(callable $callback) {}
        }
        PHP,
        <<<'PHP'
        <?php

        class Collection
        {
            /**
             * @phpstan-param callable(TValue, TKey): TMapValue $callback
             * @phpstan-return static<TKey, TMapValue>
             */
            public function map(callable $callback) {}
        }
        PHP,
    ],
]);

it('handles spacing edge cases in comments', function (string $input, string $expected) {
    expect(fixCode($this->fixer, $input))->toBe($expected);
})->with([
    'no space after double-slash delimiter' => [
        <<<'PHP'
        <?php

        //no space after delimiter
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'many spaces after double-slash delimiter' => [
        <<<'PHP'
        <?php

        //    lots of spaces
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'empty double-slash comment' => [
        <<<'PHP'
        <?php

        //
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'empty hash comment' => [
        <<<'PHP'
        <?php

        #
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'empty block comment' => [
        <<<'PHP'
        <?php

        /* */
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'empty single-line docblock' => [
        <<<'PHP'
        <?php

        /** */
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'compact single-line docblock without spaces' => [
        <<<'PHP'
        <?php

        /**@var int $x*/
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        /**@var int $x*/
        $x = 1;
        PHP,
    ],
    'tight single-line docblock' => [
        <<<'PHP'
        <?php

        /**@var int*/
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        /**@var int*/
        $x = 1;
        PHP,
    ],
]);

it('preserves blank line separators between code blocks when removing comments', function (string $input, string $expected) {
    expect(fixCode($this->fixer, $input))->toBe($expected);
})->with([
    'blank line before comment between statements' => [
        <<<'PHP'
        <?php

        $ecrUri = sprintf('%s.dkr.ecr.%s', $ecrAccountId, $ecrRegion);

        // While today, the ECR Account is shared across
        // organizations, we may want to change this in the
        // future. As such we are storing it per application.

        $application->update([
            'ecr_account_id' => $ecrAccountId,
        ]);
        PHP,
        <<<'PHP'
        <?php

        $ecrUri = sprintf('%s.dkr.ecr.%s', $ecrAccountId, $ecrRegion);

        $application->update([
            'ecr_account_id' => $ecrAccountId,
        ]);
        PHP,
    ],
    'blank line before single comment between statements' => [
        <<<'PHP'
        <?php

        $usageBytes = DataSize::from($usage);

        // This is to make sure we don't exceed the limit
        if ($usageBytes > $allowedBytes) {
            $allowanceBytes = $allowedBytes;
        }
        PHP,
        <<<'PHP'
        <?php

        $usageBytes = DataSize::from($usage);

        if ($usageBytes > $allowedBytes) {
            $allowanceBytes = $allowedBytes;
        }
        PHP,
    ],
    'no blank line before comment stays collapsed' => [
        <<<'PHP'
        <?php

        $x = 1;
        // a comment
        $y = 2;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        $y = 2;
        PHP,
    ],
    'blank lines between methods with docblock comments' => [
        <<<'PHP'
        <?php

        class Foo
        {
            /**
             * Create something.
             */
            public function create() {}

            /**
             * Update something.
             */
            public function update() {}
        }
        PHP,
        <<<'PHP'
        <?php

        class Foo
        {
            public function create() {}

            public function update() {}
        }
        PHP,
    ],
]);

it('handles @ signs in non-annotation contexts', function (string $input, string $expected) {
    expect(fixCode($this->fixer, $input))->toBe($expected);
})->with([
    'email address in single-line comment is preserved' => [
        <<<'PHP'
        <?php

        // Contact john@example.com for help
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        // Contact john@example.com for help
        $x = 1;
        PHP,
    ],
    'email address in block comment is preserved' => [
        <<<'PHP'
        <?php

        /* Author: jane@company.org */
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        /* Author: jane@company.org */
        $x = 1;
        PHP,
    ],
    'URL with @ in comment is preserved' => [
        <<<'PHP'
        <?php

        // See https://user@github.com/repo
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        // See https://user@github.com/repo
        $x = 1;
        PHP,
    ],
    'bare @ sign alone is preserved' => [
        <<<'PHP'
        <?php

        // @
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        // @
        $x = 1;
        PHP,
    ],
    '@ sign at end of comment is preserved' => [
        <<<'PHP'
        <?php

        // something @
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        // something @
        $x = 1;
        PHP,
    ],
    'email in docblock prose line is kept alongside annotations' => [
        <<<'PHP'
        <?php

        /**
         * Contact support@example.com
         * @param  string  $email
         */
        function notify($email) {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * Contact support@example.com
         * @param  string  $email
         */
        function notify($email) {}
        PHP,
    ],
]);

it('preserves less common but valid annotation tags', function (string $input, string $expected) {
    expect(fixCode($this->fixer, $input))->toBe($expected);
})->with([
    '@todo in docblock' => [
        <<<'PHP'
        <?php

        /**
         * @todo Refactor this method.
         */
        function foo() {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @todo Refactor this method.
         */
        function foo() {}
        PHP,
    ],
    '@codeCoverageIgnore' => [
        <<<'PHP'
        <?php

        /**
         * @codeCoverageIgnore
         */
        class Foo {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @codeCoverageIgnore
         */
        class Foo {}
        PHP,
    ],
    '@api tag' => [
        <<<'PHP'
        <?php

        /**
         * @api
         */
        function publicApi() {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @api
         */
        function publicApi() {}
        PHP,
    ],
    '@internal tag' => [
        <<<'PHP'
        <?php

        /**
         * @internal
         */
        class Internal {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @internal
         */
        class Internal {}
        PHP,
    ],
    '@SuppressWarnings' => [
        <<<'PHP'
        <?php

        /**
         * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
         */
        function longMethod() {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
         */
        function longMethod() {}
        PHP,
    ],
    'multiple bare tags' => [
        <<<'PHP'
        <?php

        /**
         * @deprecated
         * @internal
         * @api
         */
        function foo() {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @deprecated
         * @internal
         * @api
         */
        function foo() {}
        PHP,
    ],
    '@phpstan-ignore with message' => [
        <<<'PHP'
        <?php

        // @phpstan-ignore argument.type (expected strict type)
        $x = something();
        PHP,
        <<<'PHP'
        <?php

        // @phpstan-ignore argument.type (expected strict type)
        $x = something();
        PHP,
    ],
]);

it('handles complex structural scenarios', function (string $input, string $expected) {
    expect(fixCode($this->fixer, $input))->toBe($expected);
})->with([
    'blank lines between annotations are stripped' => [
        <<<'PHP'
        <?php

        /**
         * @param  int  $a
         *
         *
         * @return int
         */
        function foo($a) { return $a; }
        PHP,
        <<<'PHP'
        <?php

        /**
         * @param  int  $a
         * @return int
         */
        function foo($a) { return $a; }
        PHP,
    ],
    'annotations with blank separators' => [
        <<<'PHP'
        <?php

        /**
         * @param  string  $name
         *
         * @return void
         */
        function greet($name) {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @param  string  $name
         * @return void
         */
        function greet($name) {}
        PHP,
    ],
    'deeply nested closure inside method inside class' => [
        "<?php\n\nclass Foo\n{\n    public function bar()\n    {\n        \$fn = function () {\n            /**\n             * Some docs.\n             *\n             * @var int \$val\n             */\n            \$val = 42;\n        };\n    }\n}",
        "<?php\n\nclass Foo\n{\n    public function bar()\n    {\n        \$fn = function () {\n            /**\n             * @var int \$val\n             */\n            \$val = 42;\n        };\n    }\n}",
    ],
    'two docblocks before one element — prose removed, annotations kept' => [
        <<<'PHP'
        <?php

        /**
         * First docblock.
         */
        /**
         * @param  int  $x
         */
        function foo($x) {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @param  int  $x
         */
        function foo($x) {}
        PHP,
    ],
    'class property with @var and prose' => [
        <<<'PHP'
        <?php

        class Foo
        {
            /**
             * The name.
             *
             * @var string
             */
            protected $name;
        }
        PHP,
        <<<'PHP'
        <?php

        class Foo
        {
            /**
             * @var string
             */
            protected $name;
        }
        PHP,
    ],
    'class constant with @var and prose' => [
        <<<'PHP'
        <?php

        class Foo
        {
            /**
             * Max retry count.
             *
             * @var int
             */
            const MAX_RETRIES = 3;
        }
        PHP,
        <<<'PHP'
        <?php

        class Foo
        {
            /**
             * @var int
             */
            const MAX_RETRIES = 3;
        }
        PHP,
    ],
    'trait use with comment is removed' => [
        <<<'PHP'
        <?php

        class Foo
        {
            // Include soft deletes
            use SoftDeletes;
        }
        PHP,
        "<?php\n\nclass Foo\n{\n    use SoftDeletes;\n}",
    ],
    'consecutive inline @var annotations' => [
        <<<'PHP'
        <?php

        /** @var int $a */
        $a = 1;

        /** @var string $b */
        $b = "hello";

        /** @var bool $c */
        $c = true;
        PHP,
        <<<'PHP'
        <?php

        /** @var int $a */
        $a = 1;

        /** @var string $b */
        $b = "hello";

        /** @var bool $c */
        $c = true;
        PHP,
    ],
    'mixed comment types before one element' => [
        <<<'PHP'
        <?php

        // A note
        /* Another note */
        /**
         * @param  int  $x
         */
        function foo($x) {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @param  int  $x
         */
        function foo($x) {}
        PHP,
    ],
    'interleaved prose and annotations' => [
        <<<'PHP'
        <?php

        /**
         * Description line.
         * @param  int  $a
         * More prose here.
         * @param  int  $b
         * Final prose.
         * @return int
         */
        function foo($a, $b) { return $a + $b; }
        PHP,
        <<<'PHP'
        <?php

        /**
         * @param  int  $a
         * @param  int  $b
         * @return int
         */
        function foo($a, $b) { return $a + $b; }
        PHP,
    ],
    'tab-indented docblock with prose and annotations' => [
        "<?php\n\nclass Foo\n{\n\t/**\n\t * Some description.\n\t *\n\t * @param  string  \$x\n\t * @return void\n\t */\n\tpublic function bar(\$x) {}\n}",
        "<?php\n\nclass Foo\n{\n\t/**\n\t * @param  string  \$x\n\t * @return void\n\t */\n\tpublic function bar(\$x) {}\n}",
    ],
]);

it('preserves empty body placeholder comments', function (string $input, string $expected) {
    expect(fixCode($this->fixer, $input))->toBe($expected);
})->with([
    'empty interface body' => [
        <<<'PHP'
        <?php

        interface DatabaseSize
        {
            //
        }
        PHP,
        <<<'PHP'
        <?php

        interface DatabaseSize
        {
            //
        }
        PHP,
    ],
    'empty method body' => [
        <<<'PHP'
        <?php

        class Foo
        {
            public function bar()
            {
                //
            }
        }
        PHP,
        <<<'PHP'
        <?php

        class Foo
        {
            public function bar()
            {
                //
            }
        }
        PHP,
    ],
    'empty class body' => [
        <<<'PHP'
        <?php

        class Foo
        {
            //
        }
        PHP,
        <<<'PHP'
        <?php

        class Foo
        {
            //
        }
        PHP,
    ],
    'empty closure body' => [
        <<<'PHP'
        <?php

        $fn = function () {
            //
        };
        PHP,
        <<<'PHP'
        <?php

        $fn = function () {
            //
        };
        PHP,
    ],
    'empty hash body placeholder' => [
        <<<'PHP'
        <?php

        interface Foo
        {
            #
        }
        PHP,
        <<<'PHP'
        <?php

        interface Foo
        {
            #
        }
        PHP,
    ],
    'non-empty body comment is still removed when not sole statement' => [
        <<<'PHP'
        <?php

        class Foo
        {
            // some comment
            public function bar() {}
        }
        PHP,
        "<?php\n\nclass Foo\n{\n    public function bar() {}\n}",
    ],
    'body comment with text becomes empty placeholder' => [
        <<<'PHP'
        <?php

        class Migration
        {
            public function up(): void
            {
                // Moved to a seeder...
            }
        }
        PHP,
        <<<'PHP'
        <?php

        class Migration
        {
            public function up(): void
            {
                //
            }
        }
        PHP,
    ],
    'body hash comment with text becomes empty placeholder' => [
        <<<'PHP'
        <?php

        function foo()
        {
            # not needed anymore
        }
        PHP,
        <<<'PHP'
        <?php

        function foo()
        {
            #
        }
        PHP,
    ],
    'already empty body placeholder is unchanged' => [
        <<<'PHP'
        <?php

        function foo()
        {
            //
        }
        PHP,
        <<<'PHP'
        <?php

        function foo()
        {
            //
        }
        PHP,
    ],
    'service provider with empty body placeholders' => [
        <<<'PHP'
        <?php

        class AppServiceProvider extends ServiceProvider
        {
            /**
             * Register any application services.
             */
            public function register()
            {
                //
            }

            /**
             * Bootstrap any application services.
             */
            public function boot()
            {
                //
            }
        }
        PHP,
        "<?php\n\nclass AppServiceProvider extends ServiceProvider\n{\n    public function register()\n    {\n        //\n    }\n\n    public function boot()\n    {\n        //\n    }\n}",
    ],
]);

it('does not modify files without comments', function (string $code) {
    expect(fixCode($this->fixer, $code))->toBe($code);
})->with([
    'simple variable assignment' => [
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'eloquent model without comments' => [
        <<<'PHP'
        <?php

        class User extends Model
        {
            protected $fillable = ['name', 'email'];

            public function posts()
            {
                return $this->hasMany(Post::class);
            }
        }
        PHP,
    ],
]);

it('skips files in the config directory', function (string $filePath) {
    $code = <<<'PHP'
    <?php

    // This comment should be preserved in config files
    return [
        'name' => env('APP_NAME', 'Laravel'),
    ];
    PHP;

    $fixer = new TypeAnnotationsOnlyFixer;

    expect($fixer->supports(new SplFileInfo($filePath)))->toBeFalse();
    expect(fixCode($fixer, $code, $filePath))->toBe($code);
})->with([
    'config/app.php' => ['/project/config/app.php'],
    'config/database.php' => ['/project/config/database.php'],
    'config/nested/services.php' => ['/project/config/nested/services.php'],
]);

it('does not skip files outside the config directory', function (string $filePath) {
    $fixer = new TypeAnnotationsOnlyFixer;

    expect($fixer->supports(new SplFileInfo($filePath)))->toBeTrue();
})->with([
    'app/Models/User.php' => ['/project/app/Models/User.php'],
    'app/Configurable.php' => ['/project/app/Configurable.php'],
    'src/config.php' => ['/project/src/config.php'],
]);

it('preserves single-line comments when ignore_single_line_comments is true', function (string $input, string $expected) {
    $this->fixer->configure(['ignore_single_line_comments' => true]);
    expect(fixCode($this->fixer, $input))->toBe($expected);
})->with([
    'double-slash comment is preserved' => [
        <<<'PHP'
        <?php

        // This is a comment
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        // This is a comment
        $x = 1;
        PHP,
    ],
    'hash comment is preserved' => [
        <<<'PHP'
        <?php

        # This is a hash comment
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        # This is a hash comment
        $x = 1;
        PHP,
    ],
    'trailing comment is preserved' => [
        <<<'PHP'
        <?php

        $x = 1; // trailing comment
        PHP,
        <<<'PHP'
        <?php

        $x = 1; // trailing comment
        PHP,
    ],
    'multiple sequential comments are preserved' => [
        <<<'PHP'
        <?php

        // First comment
        // Second comment
        // Third comment
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        // First comment
        // Second comment
        // Third comment
        $x = 1;
        PHP,
    ],
    'TODO comment is preserved' => [
        <<<'PHP'
        <?php

        // TODO: refactor this
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        // TODO: refactor this
        $x = 1;
        PHP,
    ],
    'block comment is still removed' => [
        <<<'PHP'
        <?php

        /* This is a block comment */
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'docblock without annotations is still removed' => [
        <<<'PHP'
        <?php

        /**
         * Just a description.
         */
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'docblock prose is still stripped keeping annotations' => [
        <<<'PHP'
        <?php

        /**
         * A helper.
         *
         * @param  int  $a
         * @return int
         */
        function add($a) {}
        PHP,
        <<<'PHP'
        <?php

        /**
         * @param  int  $a
         * @return int
         */
        function add($a) {}
        PHP,
    ],
    'body placeholder comment still works' => [
        <<<'PHP'
        <?php

        class Foo
        {
            public function bar()
            {
                // Moved to a seeder...
            }
        }
        PHP,
        <<<'PHP'
        <?php

        class Foo
        {
            public function bar()
            {
                //
            }
        }
        PHP,
    ],
    'section divider comments are preserved' => [
        <<<'PHP'
        <?php

        class UserController extends Controller
        {
            // ==================
            // Authentication
            // ==================

            public function login() {}

            // ==================
            // Profile
            // ==================

            public function profile() {}
        }
        PHP,
        <<<'PHP'
        <?php

        class UserController extends Controller
        {
            // ==================
            // Authentication
            // ==================

            public function login() {}

            // ==================
            // Profile
            // ==================

            public function profile() {}
        }
        PHP,
    ],
]);

it('still removes single-line comments when ignore_single_line_comments is false', function (string $input, string $expected) {
    $this->fixer->configure(['ignore_single_line_comments' => false]);
    expect(fixCode($this->fixer, $input))->toBe($expected);
})->with([
    'double-slash comment is removed' => [
        <<<'PHP'
        <?php

        // This is a comment
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
    'hash comment is removed' => [
        <<<'PHP'
        <?php

        # This is a hash comment
        $x = 1;
        PHP,
        <<<'PHP'
        <?php

        $x = 1;
        PHP,
    ],
]);
