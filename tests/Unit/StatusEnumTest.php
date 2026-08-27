<?php

use App\Enums\InquiryStatus;
use App\Enums\MilestoneStatus;
use App\Enums\ProjectStatus;

/**
 * The enums are the one place a status is defined, and both the admin panel and
 * the client portal render whatever they ship. A case added without a label or
 * a colour would reach the browser as an empty badge, and no feature test would
 * notice because none of them enumerate the cases.
 *
 * These need no database and no framework, which is why they live here.
 */
dataset('every status case', [
    'project' => [ProjectStatus::class],
    'milestone' => [MilestoneStatus::class],
    'inquiry' => [InquiryStatus::class],
]);

it('gives every case a label and a colour', function (string $enum) {
    foreach ($enum::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
        expect($case->color())->toBeString()->not->toBeEmpty();
    }
})->with('every status case');

it('gives every case a distinct label', function (string $enum) {
    $labels = array_map(fn ($case) => $case->label(), $enum::cases());

    expect($labels)->toHaveCount(count(array_unique($labels)));
})->with('every status case');

it('only uses colours the frontends know how to render', function (string $enum) {
    // Both clients map these to class names written out in full, so a colour
    // outside this set renders unstyled rather than failing loudly.
    $known = ['slate', 'blue', 'amber', 'emerald', 'rose'];

    foreach ($enum::cases() as $case) {
        expect($case->color())->toBeIn($known);
    }
})->with('every status case');

it('keeps the stored values lowercase and underscored', function (string $enum) {
    // They are persisted, so a change here is a migration, not a rename.
    foreach ($enum::cases() as $case) {
        expect($case->value)->toMatch('/^[a-z]+(_[a-z]+)*$/');
    }
})->with('every status case');
