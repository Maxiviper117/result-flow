<?php

use Maxiviper117\ResultFlow\Output\Serialization;
use Maxiviper117\ResultFlow\Result;

describe('Result Output', function () {

    it('toJson returns valid JSON string for success result', function () {
        $result = Result::ok(['user_id' => 123], ['timestamp' => 1000]);
        $json = Serialization::toJson($result);

        $decoded = json_decode($json, true);
        expect($decoded['ok'])->toBeTrue();
        expect($decoded['value']['user_id'])->toBe(123);
        expect($decoded['meta']['timestamp'])->toBe(1000);
    });

    it('toJson returns valid JSON string for failure result', function () {
        $result = Result::fail('Something went wrong', ['code' => 500]);
        $json = Serialization::toJson($result);

        $decoded = json_decode($json, true);
        expect($decoded['ok'])->toBeFalse();
        expect($decoded['error'])->toBe('Something went wrong');
        expect($decoded['meta']['code'])->toBe(500);
    });

    it('toJson accepts encoding options', function () {
        $result = Result::ok(['html' => '<p>test</p>']);
        // JSON_HEX_TAG converts < > to \u003C \u003E
        $json = Serialization::toJson($result, JSON_HEX_TAG);

        expect($json)->toContain('\u003C');
    });

    it('toXml returns valid XML string for success result', function () {
        $result = Result::ok(['id' => 123, 'name' => 'Test'], ['version' => 1]);
        $xmlString = Serialization::toXml($result);

        $xml = new SimpleXMLElement($xmlString);
        expect((string) $xml->ok)->toBe('1');
        expect((string) $xml->value->id)->toBe('123');
        expect((string) $xml->value->name)->toBe('Test');
    });

    it('toXml handles numeric keys by prefixing with item', function () {
        $result = Result::ok(['a', 'b']);
        $xmlString = Serialization::toXml($result);

        $xml = new SimpleXMLElement($xmlString);
        expect((string) $xml->value->item0)->toBe('a');
        expect((string) $xml->value->item1)->toBe('b');
    });

    it('toXml allows custom root element', function () {
        $result = Result::ok('val');
        $xmlString = Serialization::toXml($result, 'api-response');

        expect($xmlString)->toContain('<api-response>');
    });

    it('toXml normalizes invalid child element names', function () {
        $result = Result::ok([
            'bad key' => 'space',
            '123name' => 'leading-digit',
            'xmlroot' => 'reserved',
            'meta.value' => 'punctuation',
        ]);

        $xml = new SimpleXMLElement(Serialization::toXml($result));

        expect((string) $xml->value->bad_key)->toBe('space');
        expect((string) $xml->value->item_123name)->toBe('leading-digit');
        expect((string) $xml->value->item_xmlroot)->toBe('reserved');
        expect((string) $xml->value->meta_value)->toBe('punctuation');
    });

    it('toXml normalizes invalid custom root element names', function () {
        $result = Result::ok('val');
        $xml = new SimpleXMLElement(Serialization::toXml($result, 'bad root'));

        expect($xml->getName())->toBe('bad_root');
        expect((string) $xml->value)->toBe('val');
    });

});

it('rejects invalid XML characters and malformed UTF-8', function () {
    expect(fn () => Serialization::toXml(Result::ok("A\x01B")))->toThrow(InvalidArgumentException::class)
        ->and(fn () => Serialization::toXml(Result::ok("\xC3\x28")))->toThrow(InvalidArgumentException::class);
});

it('preserves valid Unicode and XML-sensitive text', function () {
    $xml = new SimpleXMLElement(Serialization::toXml(Result::ok('café & <tag>')));
    expect((string) $xml->value)->toBe('café & <tag>');
});

it('reports a clear error when SimpleXML is unavailable', function () {
    $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/Support/XmlWithoutExtension.php');
    exec($command, $output, $status);
    expect($status)->toBe(0)->and(implode("\n", $output))->toBe('XML output requires the SimpleXML extension');
});
