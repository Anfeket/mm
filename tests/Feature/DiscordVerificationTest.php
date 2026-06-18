<?php

use Illuminate\Testing\TestResponse;

use function Pest\Laravel\postJson;

function discordRequest(array $payload): TestResponse
{
    $publicKey = sodium_crypto_sign_keypair();
    $privateKey = sodium_crypto_sign_secretkey($publicKey);
    $pubKeyHex = bin2hex(sodium_crypto_sign_publickey($publicKey));

    config(['services.discord.public_key' => $pubKeyHex]);

    $body = json_encode($payload);
    $timestamp = (string) time();
    $signature = bin2hex(sodium_crypto_sign_detached($timestamp.$body, $privateKey));

    return postJson('/discord/interactions', $payload, [
        'X-Signature-Ed25519' => $signature,
        'X-Signature-Timestamp' => $timestamp,
    ]);
}

test('ping handshake returns pong', function () {
    discordRequest(['type' => 1])
        ->assertOk()
        ->assertJson(['type' => 1]);
});

test('rejects missing signature headers', function () {
    postJson('/discord/interactions', ['type' => 1])
        ->assertUnauthorized();
});

test('rejects invalid signature', function () {
    postJson('/discord/interactions', ['type' => 1], [
        'X-Signature-Ed25519' => str_repeat('a', 128),
        'X-Signature-Timestamp' => (string) time(),
    ])->assertUnauthorized();
});

test('ping command returns pong content', function () {
    discordRequest([
        'type' => 2,
        'data' => ['name' => 'ping'],
    ])
        ->assertOk()
        ->assertJsonPath('data.content', 'Pong!');
});

test('unknown command returns ephemeral error', function () {
    discordRequest([
        'type' => 2,
        'data' => ['name' => 'doesnotexist'],
    ])
        ->assertOk()
        ->assertJsonPath('data.flags', 64);
});
