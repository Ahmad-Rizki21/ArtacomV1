<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User; // Tambahkan import ini

class EditProfilePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'Edit Profil';
    protected static ?string $title = 'Edit Profil';
    protected static string $view = 'filament.pages.edit-profile';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama'),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(table: 'users', column: 'email', ignoreRecord: true)
                    ->label('Email'),
                TextInput::make('current_password')
                    ->password()
                    ->label('Password Saat Ini')
                    ->required()
                    ->rules(['required_with:new_password']),
                TextInput::make('new_password')
                    ->password()
                    ->label('Password Baru')
                    ->nullable()
                    ->rules([
                        'sometimes',
                        'confirmed',
                        Password::defaults()
                    ])
                    ->autocomplete('new-password'),
                TextInput::make('new_password_confirmation')
                    ->password()
                    ->label('Konfirmasi Password Baru')
                    ->nullable()
                    ->rules([
                        'sometimes',
                        'required_with:new_password'
                    ])
                    ->autocomplete('new-password')
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        // Validasi password saat ini
        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        // Persiapkan data untuk diupdate
        $userModel = User::find($user->id);
        $userModel->name = $data['name'];
        $userModel->email = $data['email'];

        // Update password jika diisi
        if (!empty($data['new_password'])) {
            $userModel->password = Hash::make($data['new_password']);
        }

        // Simpan perubahan
        $userModel->save();

        Notification::make()
            ->success()
            ->title('Profil Berhasil Diperbarui')
            ->body('Informasi profil Anda telah diupdate.')
            ->send();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}