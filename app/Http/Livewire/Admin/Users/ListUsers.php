<?php

namespace App\Http\Livewire\Admin\Users;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ListUsers extends Component
{
    public $search = '';
    public $isModalOpen = false;

    // Propiedades del formulario
    public $user_id, $names, $surnames, $email, $role, $password, $active = true;

    protected $updatesQueryString = ['search'];

    public function render()
    {
        $users = User::query()
            ->where(function($query) {
                $query->where('names', 'like', '%' . $this->search . '%')
                      ->orWhere('surnames', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.users.list-users', [
            'users' => $users
        ]);
    }

    public function create()
    {
        $this->reset(['names', 'surnames', 'email', 'role', 'password', 'user_id']);
        $this->active = true;
        $this->openModal();
    }

    public function edit(User $user)
    {
        $this->user_id = $user->id;
        $this->names = $user->names;
        $this->surnames = $user->surnames;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->active = $user->active;
        $this->openModal();
    }

    public function store()
    {
        $rules = [
            'names' => 'required|string|max:100',
            'surnames' => 'required|string|max:100',
            'role' => 'required',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->user_id)],
        ];

        // Password obligatorio solo en creación
        if (!$this->user_id) {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules);

        $data = [
            'names' => $this->names,
            'surnames' => $this->surnames,
            'name' => $this->names . ' ' . $this->surnames, // Sincronizamos campo name
            'email' => $this->email,
            'role' => $this->role,
            'active' => $this->active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        User::updateOrCreate(['id' => $this->user_id], $data);

        session()->flash('message', $this->user_id ? 'Usuario actualizado.' : 'Usuario creado.');
        $this->closeModal();
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; }
}