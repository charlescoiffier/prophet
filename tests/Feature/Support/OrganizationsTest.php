<?php

use App\Models\Affiliation;
use App\Rules\NotNearDuplicateOrganization;
use App\Support\Organizations;
use Illuminate\Support\Facades\Validator;

it('repère une faute de frappe sur un nom d’organisation connu', function () {
    Affiliation::factory()->create(['organization' => 'Rassemblement Fictif']);

    expect(Organizations::nearDuplicates('Rassemblemnt Fictif'))->toBe(['Rassemblement Fictif'])
        ->and(Organizations::nearDuplicates('Rassemblement Fictif'))->toBe([])
        ->and(Organizations::nearDuplicates('Mouvement Imaginaire'))->toBe([]);
});

it('ne confond pas deux organisations courtes qui diffèrent d’une lettre', function () {
    Affiliation::factory()->create(['organization' => 'Parti A']);

    expect(Organizations::nearDuplicates('Parti B'))->toBe([]);
});

it('bloque un quasi-doublon sauf confirmation explicite', function () {
    Affiliation::factory()->create(['organization' => 'Rassemblement Fictif']);

    $refused = Validator::make(['name' => 'Rassemblemnt Fictif'], ['name' => [new NotNearDuplicateOrganization]]);
    $confirmed = Validator::make(['name' => 'Rassemblemnt Fictif'], ['name' => [new NotNearDuplicateOrganization(confirmedDistinct: true)]]);

    expect($refused->errors()->first('name'))->toContain('« Rassemblement Fictif »')
        ->and($confirmed->passes())->toBeTrue();
});

it('propose pendant la saisie les organisations connues, puis le texte tapé comme nouvelle organisation', function () {
    Affiliation::factory()->create(['organization' => 'Rassemblement Fictif']);

    expect(Organizations::suggestions('rassemblement'))->toBe(['Rassemblement Fictif' => 'Rassemblement Fictif', 'rassemblement' => 'rassemblement (nouvelle organisation)'])
        ->and(Organizations::suggestions('rassemblement fictif'))->toBe(['Rassemblement Fictif' => 'Rassemblement Fictif'])
        ->and(Validator::make(['name' => 'Rassemblement Fictif'], ['name' => [new NotNearDuplicateOrganization]])->passes())->toBeTrue();
});
