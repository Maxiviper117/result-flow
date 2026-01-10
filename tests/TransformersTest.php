<?php

use Maxiviper117\ResultFlow\Result;

describe('Result Transformers', function () {

    it('toJson returns valid JSON string for success result', function () {
        $result = Result::ok(['user_id' => 123], ['timestamp' => 1000]);
        $json = $result->toJson();

        $decoded = json_decode($json, true);
        expect($decoded['ok'])->toBeTrue();
        expect($decoded['value']['user_id'])->toBe(123);
        expect($decoded['meta']['timestamp'])->toBe(1000);
    });

    it('toJson returns valid JSON string for failure result', function () {
        $result = Result::fail('Something went wrong', ['code' => 500]);
        $json = $result->toJson();

        $decoded = json_decode($json, true);
        expect($decoded['ok'])->toBeFalse();
        expect($decoded['error'])->toBe('Something went wrong');
        expect($decoded['meta']['code'])->toBe(500);
    });

    it('toJson accepts encoding options', function () {
        $result = Result::ok(['html' => '<p>test</p>']);
        // JSON_HEX_TAG converts < > to \u003C \u003E
        $json = $result->toJson(JSON_HEX_TAG);

        expect($json)->toContain('\u003C');
    });

    it('toXml returns valid XML string for success result', function () {
        $result = Result::ok(['id' => 123, 'name' => 'Test'], ['version' => 1]);
        $xmlString = $result->toXml();

        $xml = new SimpleXMLElement($xmlString);
        expect((string) $xml->ok)->toBe('1');
        expect((string) $xml->value->id)->toBe('123');
        expect((string) $xml->value->name)->toBe('Test');
    });

    it('toXml handles numeric keys by prefixing with item', function () {
        $result = Result::ok(['a', 'b']);
        $xmlString = $result->toXml();

        $xml = new SimpleXMLElement($xmlString);
        expect((string) $xml->value->item0)->toBe('a');
        expect((string) $xml->value->item1)->toBe('b');
    });

    it('toXml allows custom root element', function () {
        $result = Result::ok('val');
        $xmlString = $result->toXml('api-response');

        expect($xmlString)->toContain('<api-response>');
    });

    it('toResponse returns array structure by default (without Laravel)', function () {
        // We assume 'response' helper does not exist in this test environment context normally,
        // unless defined in Pest.php or similar.
        // In our Pest.php we only saw config() helper.

        $result = Result::ok(['data' => 'ok']);
        $response = $result->toResponse();

        expect($response)->toBeArray();
        expect($response['status'])->toBe(200);
        expect($response['headers']['Content-Type'])->toBe('application/json');

        $body = json_decode($response['body'], true);
        expect($body['ok'])->toBeTrue();
    });

    it('toResponse returns status 400 for failure by default', function () {
        $result = Result::fail('error');
        $response = $result->toResponse();

        expect($response['status'])->toBe(400);

        $body = json_decode($response['body'], true);
        expect($body['ok'])->toBeFalse();
    });

});
