<?php

declare(strict_types=1);

use Maxiviper117\ResultFlow\Output\DebugOutput;
use Maxiviper117\ResultFlow\Result;
use Maxiviper117\ResultFlow\Tests\Support\ConfigStub;

it('bounds self-referential and mutually recursive arrays while redacting keys', function () {
    $self = ['token' => 'secret'];
    $self['self'] = &$self;
    $left = [];
    $right = ['password' => 'secret'];
    $left['right'] = &$right;
    $right['left'] = &$left;
    $output = DebugOutput::toArray(Result::ok(1, ['self' => $self, 'mutual' => $left]));
    expect($output['meta']['self']['token'])->toBe('***REDACTED***')
        ->and($output['meta']['mutual']['right']['password'])->toBe('***REDACTED***')
        ->and(json_encode($output, JSON_THROW_ON_ERROR))->toContain('[circular_reference]');
});

it('terminates branching cycles without discarding shared non-cyclic arrays', function () {
    $cycle = [];
    $cycle['left'] = &$cycle;
    $cycle['right'] = &$cycle;
    $shared = ['value' => 1];
    $meta = ['cycle' => $cycle, 'first' => &$shared, 'second' => &$shared];
    $output = DebugOutput::toArray(Result::ok(1, $meta))['meta'];
    expect(json_encode($output, JSON_THROW_ON_ERROR))->toContain('[circular_reference]')
        ->and($output['first'])->toBe(['value' => 1])
        ->and($output['second'])->toBe(['value' => 1]);
});

it('bounds deep arrays nested inside sanitizable objects', function () {
    ConfigStub::set('result-flow.debug', ['sanitize_objects' => true, 'object_max_depth' => 10, 'array_max_depth' => 2]);
    $object = (object) ['data' => ['next' => ['secret' => 'hidden', 'nested' => ['more' => 1]]]];
    $meta = DebugOutput::toArray(Result::ok(1, ['object' => $object]))['meta'];
    expect($meta['object']['data']['next'])->toBe('[array_depth_exceeded]');
});

it('bounds deeply nested ordinary arrays using the configured limit', function () {
    ConfigStub::set('result-flow.debug', ['array_max_depth' => 2]);
    $meta = DebugOutput::toArray(Result::ok(1, ['outer' => ['inner' => ['leaf' => 1]]]))['meta'];
    expect($meta['outer']['inner'])->toBe('[array_depth_exceeded]');
});
