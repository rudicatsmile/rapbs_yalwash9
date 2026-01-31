<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create department', function () {
    $department = Department::create([
        'name' => 'IT',
        'urut' => 1,
        'description' => 'Information Technology',
    ]);

    expect($department)->toBeInstanceOf(Department::class);
    expect($department->name)->toBe('IT');
});

test('can assign user to department', function () {
    $department = Department::create([
        'name' => 'HR',
        'urut' => 2,
    ]);

    $user = User::factory()->create([
        'department_id' => $department->id,
    ]);

    expect($user->department_id)->toBe($department->id);
    expect($user->department->name)->toBe('HR');
});
