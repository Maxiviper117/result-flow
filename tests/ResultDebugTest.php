<?php

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Tests\Support\ConfigStub;

final class DebugTestPublicDto
{
    public function __construct(
        public string $api_key,
        public string $safe,
    ) {}
}

final class DebugTestJsonDto implements JsonSerializable
{
    public function __construct(
        private string $password,
        private string $note,
    ) {}

    public function jsonSerialize(): mixed
    {
        return ['password' => $this->password, 'note' => $this->note];
    }
}

final class DebugTestCycleNode
{
    public ?self $next = null;

    public function __construct(public string $token) {}
}

describe('toDebugArray()', function () {
    it('sanitizes sensitive keys and truncates long strings with defaults', function () {
        ConfigStub::reset();

        $meta = [
            'password' => 'super-secret',
            'nested' => ['api_key' => 'abc123', 'note' => 'safe'],
            'long' => str_repeat('a', 250),
        ];

        $result = Result::fail(new RuntimeException('Boom'), $meta);
        $debug = $result->toDebugArray();

        expect($debug['error_message'])->toBe('Boom');
        expect($debug['meta']['password'])->toBe('***REDACTED***');
        expect($debug['meta']['nested']['api_key'])->toBe('***REDACTED***');
        expect($debug['meta']['nested']['note'])->toBe('safe');
        expect(mb_strlen($debug['meta']['long']))->toBe(201); // 200 chars + ellipsis
        expect($debug['meta']['long'])->toEndWith('…');
    });

    it('allows custom sanitizer to override default behavior', function () {
        $result = Result::fail('oops', ['secret' => '123']);

        $debug = $result->toDebugArray(fn () => 'clean');

        expect($debug['error_message'])->toBe('clean');
        expect($debug['meta'])->toBe('clean');
    });

    it('reads sanitizer settings from Laravel-style config when available', function () {
        ConfigStub::set('result-flow.debug', [
            'redaction' => '[redacted]',
            'sensitive_keys' => ['secretstuff'],
            'max_string_length' => 5,
            'truncate_strings' => true,
        ]);

        $result = Result::fail('helloworld', ['secretstuff' => 'token-value', 'note' => 'abcdef']);
        $debug = $result->toDebugArray();

        expect($debug['error_message'])->toBe('hello…'); // truncated to 5 + ellipsis
        expect($debug['meta']['secretstuff'])->toBe('[redacted]');
        expect($debug['meta']['note'])->toBe('abcde…');
    });

    it('can disable sanitization via config', function () {
        ConfigStub::set('result-flow.debug', [
            'enabled' => false,
            'max_string_length' => 5, // should be ignored
        ]);

        $meta = ['password' => 'secret', 'long' => str_repeat('x', 50)];
        $result = Result::fail('helloworld', $meta);
        $debug = $result->toDebugArray();

        expect($debug['meta']['password'])->toBe('secret'); // not redacted
        expect($debug['meta']['long'])->toBe(str_repeat('x', 50)); // not truncated
        expect($debug['error_message'])->toBe('helloworld'); // not truncated
    });

    it('can disable string truncation via config', function () {
        ConfigStub::set('result-flow.debug', [
            'truncate_strings' => false,
            'max_string_length' => 5,
            'sensitive_keys' => [], // ensure no redaction occurs
        ]);

        $result = Result::fail('helloworld', ['token' => 'abcdefghij']);
        $debug = $result->toDebugArray();

        expect($debug['error_message'])->toBe('helloworld'); // full length
        expect($debug['meta']['token'])->toBe('abcdefghij'); // not truncated
    });

    it('keeps object internals untouched when object sanitization is disabled', function () {
        $dto = new DebugTestPublicDto('raw-secret', 'visible');
        $result = Result::fail('oops', ['payload' => $dto]);

        $debug = $result->toDebugArray();

        expect($debug['meta']['payload'])->toBeInstanceOf(DebugTestPublicDto::class);
        expect($debug['meta']['payload']->api_key)->toBe('raw-secret');
    });

    it('sanitizes JsonSerializable and public-property objects when enabled', function () {
        ConfigStub::set('result-flow.debug', [
            'sanitize_objects' => true,
            'sensitive_keys' => ['password', 'api_key'],
            'truncate_strings' => true,
            'max_string_length' => 5,
        ]);

        $result = Result::fail('oops', [
            'dto' => new DebugTestPublicDto('abc123', 'safe-value'),
            'json' => new DebugTestJsonDto('very-secret', 'abcdef'),
        ]);

        $debug = $result->toDebugArray();

        expect($debug['meta']['dto']['api_key'])->toBe('***REDACTED***');
        expect($debug['meta']['dto']['safe'])->toBe('safe-…');
        expect($debug['meta']['json']['password'])->toBe('***REDACTED***');
        expect($debug['meta']['json']['note'])->toBe('abcde…');
    });

    it('applies depth and cycle guards for object traversal when enabled', function () {
        ConfigStub::set('result-flow.debug', [
            'sanitize_objects' => true,
            'object_max_depth' => 3,
            'sensitive_keys' => ['token'],
        ]);

        $first = new DebugTestCycleNode('first-secret');
        $second = new DebugTestCycleNode('second-secret');
        $third = new DebugTestCycleNode('third-secret');
        $first->next = $second;
        $second->next = $third;
        $third->next = $first;

        $result = Result::fail('oops', ['graph' => $first]);
        $debug = $result->toDebugArray();

        expect($debug['meta']['graph']['token'])->toBe('***REDACTED***');
        expect($debug['meta']['graph']['next']['token'])->toBe('***REDACTED***');
        expect($debug['meta']['graph']['next']['next'])->toBe('[object_depth_exceeded]');
    });
});

describe('toDebugArray()', function () {
    it('returns debug-safe array for success', function () {
        $result = Result::ok(['sensitive' => 'data'], ['request_id' => 'abc']);
        $debug = $result->toDebugArray();

        expect($debug['ok'])->toBeTrue();
        expect($debug['value_type'])->toBe('array');
        expect($debug['error_type'])->toBeNull();
        expect($debug['error_message'])->toBeNull();
        expect($debug['meta'])->toBe(['request_id' => 'abc']);
    });

    it('returns debug-safe array for failure with string error', function () {
        ConfigStub::reset();
        ConfigStub::set('result-flow.debug', ['max_string_length' => 200]);

        $result = Result::fail('Something went wrong');
        $debug = $result->toDebugArray();

        expect($debug['ok'])->toBeFalse();
        expect($debug['value_type'])->toBeNull();
        expect($debug['error_type'])->toBe('string');
        expect($debug['error_message'])->toBe('Something went wrong');
    });

    it('returns debug-safe array for failure with exception', function () {
        ConfigStub::reset();
        ConfigStub::set('result-flow.debug', ['max_string_length' => 200]);

        $result = Result::fail(new RuntimeException('Connection failed'));
        $debug = $result->toDebugArray();

        expect($debug['ok'])->toBeFalse();
        expect($debug['error_type'])->toBe('RuntimeException');
        expect($debug['error_message'])->toBe('Connection failed');
    });

    it('returns null error_message for non-string non-throwable errors', function () {
        $result = Result::fail(['code' => 500, 'errors' => ['field' => 'invalid']]);
        $debug = $result->toDebugArray();

        expect($debug['ok'])->toBeFalse();
        expect($debug['error_type'])->toBe('array');
        expect($debug['error_message'])->toBeNull();
    });
});

describe('edge cases: toArray and toDebugArray', function () {
    it('toArray includes all fields for success', function () {
        $result = Result::ok('value', ['key' => 'meta']);
        $arr = $result->toArray();

        expect($arr)->toBe([
            'ok' => true,
            'value' => 'value',
            'error' => null,
            'meta' => ['key' => 'meta'],
        ]);
    });

    it('toArray includes all fields for failure', function () {
        $result = Result::fail('error', ['key' => 'meta']);
        $arr = $result->toArray();

        expect($arr)->toBe([
            'ok' => false,
            'value' => null,
            'error' => 'error',
            'meta' => ['key' => 'meta'],
        ]);
    });

    it('toDebugArray shows type for objects', function () {
        $obj = new stdClass;
        $result = Result::ok($obj);
        $debug = $result->toDebugArray();

        expect($debug['value_type'])->toBe('stdClass');
    });

    it('toDebugArray shows int type', function () {
        $result = Result::ok(42);
        $debug = $result->toDebugArray();

        expect($debug['value_type'])->toBe('int');
    });
});
