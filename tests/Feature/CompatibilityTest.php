<?php

use App\Logic\GeoIpLocator;
use App\Models\Country as ModelsCountry;
use App\Models\Installation;
use App\Models\Version;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Record\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

uses(RefreshDatabase::class);

test('cannot send empty request')
    ->postJson('/')
    ->assertUnprocessable()
    ->assertInvalid(['uuid', 'release', 'type']);

test('cannot insert invalid type')
    ->postJson('/', ['type' => 'hello'])
    ->assertUnprocessable()
    ->assertInvalid(['type' => 'in']);

test('cannot insert invalid version', function (string $tag) {
    $this->postJson('/', ['release' => $tag])
        ->assertUnprocessable()
        ->assertInvalid(['release' => 'format']);
})->with([
    'hello',
    'x.y.z',
    'x.y',
    'x.',
    '99.99.99x'
]);

it('can handle post data', function () {
    $country = $this->mock(Country::class);
    $country->name = 'Italy';
    $country->isoCode = 'IT';

    $this->mock(GeoIpLocator::class, function (MockInterface $mock) use ($country) {
        $mock->shouldReceive('locate')
            ->once()
            ->andReturn($country);
    });

    $installation = Installation::factory()->make();
    /** @var Tests\TestCase $this */
    $this->postJson(
        '/',
        [
            'method' => 'add_info',
            'uuid' => $installation->uuid,
            'release' => $installation->version->tag,
            'type' => $installation->type
        ]
    )->assertStatus(200);
    // TODO: CHECK DATABASE
});

it('can handle if ip is not found', function () {
    /** @var Tests\TestCase $this */
    $this->mock(GeoIpLocator::class, function (MockInterface $mock) {
        $mock->shouldReceive('locate')
            ->with('127.0.0.1')
            ->once()
            ->andThrow(new AddressNotFoundException());
    });

    $installation = Installation::factory()->make();
    $this->withoutExceptionHandling()->postJson(
        '/',
        [
            'method' => 'add_info',
            'uuid' => $installation->uuid,
            'release' => $installation->version->tag,
            'type' => $installation->type
        ]
    );
})->throws(UnprocessableEntityHttpException::class);

it('can show installations', function() {
    $version7 = Version::factory()->create(['tag' => '7.9.2009']);
    $version6 = Version::factory()->create(['tag' => '6.10']);

    $countryIt = ModelsCountry::factory()->create([
        'code' => 'IT',
        'name' => 'Italy',
    ]);
    $countryGb = ModelsCountry::factory()->create([
        'code' => 'GB',
        'name' => 'United Kingdom of Great Britain and Northern Ireland (the)',
    ]);
    $countryDe = ModelsCountry::factory()->create([
        'code' => 'DE',
        'name' => 'Germany',
    ]);

    // 3 installations in DE
    Installation::factory()->create([
        'version_id' => $version6,
        'country_id' => $countryDe
    ]);
    Installation::factory()->create([
        'version_id' => $version6,
        'country_id' => $countryDe
    ]);
    Installation::factory()->create([
        'version_id' => $version7,
        'country_id' => $countryDe
    ]);

    // 1 installations in GB
    Installation::factory()->create([
        'version_id' => $version7,
        'country_id' => $countryGb
    ]);

    // 5 installations in IT
    Installation::factory()->create([
        'version_id' => $version7,
        'country_id' => $countryIt
    ]);
    Installation::factory()->create([
        'version_id' => $version7,
        'country_id' => $countryIt
    ]);
    Installation::factory()->create([
        'version_id' => $version7,
        'country_id' => $countryIt
    ]);
    Installation::factory()->create([
        'version_id' => $version6,
        'country_id' => $countryIt
    ]);
    Installation::factory()->create([
        'version_id' => $version6,
        'country_id' => $countryIt
    ]);

    $this->getJson('/api/installation')
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) =>
            $json->has(3)
                ->has('0', fn (AssertableJson $json) =>
                    $json->where('country_name', $countryDe->name)
                        ->where('country_code', $countryDe->code)
                        ->has('installations')
                )->has('1', fn (AssertableJson $json) =>
                $json->where('country_name', $countryGb->name)
                    ->where('country_code', $countryGb->code)
                    ->has('installations')
                )->has('2', fn (AssertableJson $json) =>
                    $json->where('country_name', $countryIt->name)
                        ->where('country_code', $countryIt->code)
                        ->has('installations')
                )
        );
})->skip(fn() => config('database.default') == 'sqlite', 'Cannot run on sqlite.');
