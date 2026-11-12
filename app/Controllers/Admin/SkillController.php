<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\Skill;
use App\Validator;

final class SkillController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/skills', [
            'title' => 'Keahlian',
            'skills' => Skill::all(),
            'grouped' => Skill::grouped(),
        ], 'layouts/admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();

        $data = [
            'name' => $this->inputString('name'),
            'category' => $this->skillCategory(),
            'level' => (int) $this->input('level', 80),
        ];

        $validator = new Validator(
            $data,
            ['name' => 'required|min:2|max:80', 'category' => 'max:60'],
            ['name' => 'Nama keahlian', 'category' => 'Kategori']
        );

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data, '/admin/skills');
        }

        Skill::create($data['name'], $data['category'] !== '' ? $data['category'] : 'Umum', $data['level']);
        \flash('success', 'Keahlian berhasil ditambahkan.');
        $this->redirect('/admin/skills');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();

        $data = [
            'name' => $this->inputString('name'),
            'category' => $this->inputString('category', 'Umum'),
            'level' => (int) $this->input('level', 80),
        ];

        $validator = new Validator(
            $data,
            ['name' => 'required|min:2|max:80', 'category' => 'max:60'],
            ['name' => 'Nama keahlian', 'category' => 'Kategori']
        );

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data, '/admin/skills');
        }

        Skill::update((int) $id, $data['name'], $data['category'] !== '' ? $data['category'] : 'Umum', $data['level']);
        \flash('success', 'Keahlian diperbarui.');
        $this->redirect('/admin/skills');
    }

    private function skillCategory(): string
    {
        $category = $this->inputString('category', 'Umum');

        if ($category === '__custom') {
            $category = $this->inputString('category_custom', 'Umum');
        }

        return $category !== '' ? mb_substr($category, 0, 60, 'UTF-8') : 'Umum';
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();

        Skill::delete((int) $id);
        \flash('success', 'Keahlian dihapus.');
        $this->redirect('/admin/skills');
    }
}
