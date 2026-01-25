<?php

use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Tests\Support\ConfigStub;

describe('sensitive key glob matching', function () {
    it('matches leading wildcard "*token" for keys ending with token', function () {
        ConfigStub::set('result-flow.debug', [
            'sensitive_keys' => ['*token'],
            'truncate_strings' => false,
        ]);

        $result = Result::fail('err', ['mytoken' => 'secret', 'tokenmy' => 'safe']);
        $debug = $result->toDebugArray();

        expect($debug['meta']['mytoken'])->toBe('***REDACTED***');
        expect($debug['meta']['tokenmy'])->toBe('safe');
    });

    it('matches trailing wildcard "token*" for keys starting with token', function () {
        ConfigStub::set('result-flow.debug', [
            'sensitive_keys' => ['token*'],
            'truncate_strings' => false,
        ]);

        $result = Result::fail('err', ['token123' => 'secret', '123token' => 'safe']);
        $debug = $result->toDebugArray();

        expect($debug['meta']['token123'])->toBe('***REDACTED***');
        expect($debug['meta']['123token'])->toBe('safe');
    });

    it('matches surround wildcard "*token*" for keys containing token', function () {
        ConfigStub::set('result-flow.debug', [
            'sensitive_keys' => ['*token*'],
            'truncate_strings' => false,
        ]);

        $result = Result::fail('err', ['a_token_b' => 'secret', 'ak' => 'safe']);
        $debug = $result->toDebugArray();

        expect($debug['meta']['a_token_b'])->toBe('***REDACTED***');
        expect($debug['meta']['ak'])->toBe('safe');
    });

    it('matches single-char wildcard "?id"', function () {
        ConfigStub::set('result-flow.debug', [
            'sensitive_keys' => ['?id'],
            'truncate_strings' => false,
        ]);

        $result = Result::fail('err', ['xid' => 'secret', 'id' => 'safe']);
        $debug = $result->toDebugArray();

        expect($debug['meta']['xid'])->toBe('***REDACTED***');
        expect($debug['meta']['id'])->toBe('safe');
    });

    it('matches prefix/suffix globs "api_*" and "*_key"', function () {
        ConfigStub::set('result-flow.debug', [
            'sensitive_keys' => ['api_*', '*_key'],
            'truncate_strings' => false,
        ]);

        $result = Result::fail('err', ['api_user' => 'secret', 'session_key' => 'secret2', 'apikey' => 'safe']);
        $debug = $result->toDebugArray();

        expect($debug['meta']['api_user'])->toBe('***REDACTED***');
        expect($debug['meta']['session_key'])->toBe('***REDACTED***');
        expect($debug['meta']['apikey'])->toBe('safe');
    });

    it('is case-insensitive for patterns', function () {
        ConfigStub::set('result-flow.debug', [
            'sensitive_keys' => ['ToKeN'],
            'truncate_strings' => false,
        ]);

        $result = Result::fail('err', ['token' => 'secret', 'TOKEN' => 'secret2']);
        $debug = $result->toDebugArray();

        expect($debug['meta']['token'])->toBe('***REDACTED***');
        expect($debug['meta']['TOKEN'])->toBe('***REDACTED***');
    });

    it('does not treat numeric keys as strings', function () {
        ConfigStub::set('result-flow.debug', [
            'sensitive_keys' => ['123'],
            'truncate_strings' => false,
        ]);

        $result = Result::fail('err', [123 => 'secret', 'x123' => 'secret2']);
        $debug = $result->toDebugArray();

        expect($debug['meta'][123])->toBe('secret');
        expect($debug['meta']['x123'])->toBe('***REDACTED***');
    });

    it('plain words remain substring matches', function () {
        ConfigStub::set('result-flow.debug', [
            'sensitive_keys' => ['password'],
            'truncate_strings' => false,
        ]);

        $result = Result::fail('err', ['user_password' => 'secret', 'pass' => 'safe']);
        $debug = $result->toDebugArray();

        expect($debug['meta']['user_password'])->toBe('***REDACTED***');
        expect($debug['meta']['pass'])->toBe('safe');
    });
});
